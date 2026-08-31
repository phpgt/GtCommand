<?php
namespace GT\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValue;
use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;
use Gt\Cli\Stream;
use GT\Daemon\CommandNotFoundException;
use GT\Daemon\Process;
use GT\GtCommand\Blueprint\BlueprintCollection;

/** @SuppressWarnings("PHPMD.ExcessiveClassComplexity") */
class CreateCommand extends Command {
	const MIGRATION_DIRECTORY_NOT_FOUND_EXCEPTION
		= "GT\Database\Migration\MigrationDirectoryNotFoundException";

	/**
	 * TODO: Simplify this function
	 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
	 * @SuppressWarnings("PHPMD.NPathComplexity")
	 */
	public function run(?ArgumentValueList $arguments = null):int {
		$name = $this->readValidName($arguments->get("projectName", ""));
		$blueprintCollection = new BlueprintCollection();
		$blueprintInput = $this->readBlueprintInput($arguments, $blueprintCollection);
		$namespace = $this->readNamespaceForArguments($arguments);

		if(is_numeric($blueprintInput)) {
			$selectedBlueprint = $blueprintCollection->getByIndex((int)$blueprintInput);
		}
		else {
			$selectedBlueprint = $blueprintCollection->getByKey($blueprintInput);
		}

		$selectedBlueprintTitle = $selectedBlueprint->getTitle();
		$this->writeLine("Creating project '$name' in namespace '$namespace' with blueprint '$selectedBlueprintTitle'...");
		sleep(1);

		$process = $selectedBlueprint->createProject($name);
		$this->execAndStreamProcess($process);

		$process = $selectedBlueprint->updateDependencies($name);
		$code = $this->execAndStreamProcess($process);

		if($code) {
			$this->writeLine("There was an error installing the blueprint (exit code $code)");
			exit($code); // phpcs:ignore
		}

		$this->runMigration($name);
		$this->runNpmInstall($name);

		$this->writeLine();
		$this->writeLine("Your new application is created!");
		$this->writeLine("Would you like to run it now? (Y/N)");

		do {
			$runNow = strtolower($this->readLine("N"));
		}
		while(!$runNow || !in_array($runNow[0], ["y", "n"]));

		$this->writeLine();
		if($runNow === "y") {
			$this->writeLine("Okay - running your new application...");
			usleep(500_000);
			chdir($name);

			$runCommand = new RunCommand();
			$runCommand->setStream($this->stream);
			$runCommand->run($arguments);
		}
		else {
			$this->writeLine("Your new application is in the '$name' directory.");
			$this->writeLine("Docs: https://www.php.gt/webengine/getting-started");
			$this->writeLine("Have fun!");
			$this->writeLine();
		}

		return 0;
	}

	public function getName():string {
		return "create";
	}

	public function getDescription():string {
		return "Create a new WebEngine application";
	}

	/** @inheritDoc */
	public function getRequiredNamedParameterList():array {
		return [];
	}

	/** @inheritDoc */
	public function getOptionalNamedParameterList():array {
		return [
			new NamedParameter("projectName")
		];
	}

	/** @inheritDoc */
	public function getRequiredParameterList():array {
		return [];
	}

	/** @inheritDoc */
	public function getOptionalParameterList():array {
		return [
			new Parameter(
				true,
				"namespace",
				"n",
				"The application's root namespace"
			),
			new Parameter(
				true,
				"blueprint",
				"b",
				"A template project to build on"
			),
			new Parameter(
				false,
				"empty",
				"e",
				"Use the empty blueprint"
			),
		];
	}

	private function isValidName(string $name):bool {
		if(strlen($name) === 0) {
			return false;
		}

		if(preg_match("/[^a-z0-9_\-]/i", $name)) {
			return false;
		}

		return true;
	}

	private function isValidNamespace(string $namespace):bool {
		if(strlen($namespace) === 0) {
			return false;
		}

		return !preg_match("/[^\w\\\]+/", $namespace);
	}

	private function readValidName(string $name):string {
		$i = 0;

		while(!$this->isValidName($name)) {
			if($i > 0) {
				$this->writeLine("The name '$name' is not a valid directory name.", Stream::ERROR);
				$this->writeLine("Please use only letters, numbers and underscores when naming.", Stream::ERROR);
			}

			$this->writeLine("What is the name of your project? (This will name its directory)");
			$name = $this->readLine();
			$i++;
		}

		if(file_exists($name)) {
			$type = is_file($name) ? "file" : "directory";
			$this->writeLine("Oops - there's already a $type called '$name' in the current directory.");
			exit(1); // phpcs:ignore
		}

		$this->writeLine();
		$this->writeLine("Creating application '$name' in: " . getcwd() . "/$name");
		$this->writeLine();

		return $name;
	}

	private function readValidNamespace(ArgumentValue $namespace):string {
		$i = 0;
		while(!$this->isValidNamespace($namespace)) {
			if($i > 0) {
				$this->writeLine("The namespace '$namespace' is not a valid PHP Namespace.", Stream::ERROR);
			}

			$this->writeLine("What namespace would you like to use for autoloading classes?");
			$namespace = "App";
			$namespace = $this->readLine($namespace);
			$i++;
		}
		$this->writeLine();
		$this->writeLine("Using namespace '$namespace'.");
		$this->writeLine();

		return $namespace;
	}

	private function readBlueprintInput(
		ArgumentValueList $arguments,
		BlueprintCollection $blueprintCollection
	):string {
		if($arguments->contains("blueprint")) {
			return $arguments->get("blueprint")->get();
		}

		if($arguments->contains("empty")) {
			return "empty";
		}

		$this->writeLine("What blueprint would you like to start with? (type the number)");

		foreach($blueprintCollection as $i => $blueprint) {
			$title = $blueprint->getTitle();
			$description = $blueprint->getDescription();
			$this->writeLine( " $i: $title - $description");
		}

		$blueprintInput = $this->readLine("0");
		if($blueprintInput < 0 || $blueprintInput >= count($blueprintCollection)) {
			$this->writeLine("Cancelling due to invalid blueprint.");
			exit; // phpcs:ignore
		}

		return $blueprintInput;
	}

	private function readNamespaceForArguments(ArgumentValueList $arguments):string {
		if($arguments->contains("empty")) {
			$namespace = "App";
			$this->writeLine();
			$this->writeLine("Using namespace '$namespace'.");
			$this->writeLine();
			return $namespace;
		}

		return $this->readValidNamespace($arguments->get("namespace", ""));
	}

	private function runMigration(string $dir):void {
		$this->writeLine();
		$this->writeLine("Running database migrations...");

		$process = new Process("gt", "migrate");
		$process->setExecCwd($dir);
		$code = $this->execAndStreamProcess(
			$process,
			self::MIGRATION_DIRECTORY_NOT_FOUND_EXCEPTION
		);

		if($code) {
			$this->writeLine("There was an error running migrations (exit code $code)");
			exit($code); // phpcs:ignore
		}
	}

	private function runNpmInstall(string $dir):void {
		if(!is_file("$dir/package.json")) {
			return;
		}

		if(!$this->isCommandAvailable("npm")) {
			return;
		}

		$this->writeLine();
		$this->writeLine("Installing npm dependencies...");

		$process = new Process("npm", "install");
		$process->setExecCwd($dir);
		$code = $this->execAndStreamProcess($process);

		if($code) {
			$this->writeLine("There was an error installing npm dependencies (exit code $code)");
			exit($code); // phpcs:ignore
		}
	}

	private function isCommandAvailable(string $command):bool {
		try {
			$process = new Process($command, "--version");
			$process->exec();
		}
		catch(CommandNotFoundException) {
			return false;
		}

		do {
			$process->getOutput();
			$process->getErrorOutput();
			usleep(100_000);
		}
		while($process->isRunning());

		return $process->getExitCode() === 0;
	}

	private function execAndStreamProcess(
		Process $process,
		?string $suppressErrorWhenFirstLineContains = null
	):int {
		$errorBuffer = "";
		$suppressError = null;
		$process->exec();

		do {
			$this->write($process->getOutput());
			$this->writeErrorOutput(
				$process->getErrorOutput(),
				$suppressErrorWhenFirstLineContains,
				$errorBuffer,
				$suppressError
			);
			usleep(100_000);
		}
		while($process->isRunning());

		$this->write($process->getOutput());
		$this->writeErrorOutput(
			$process->getErrorOutput(),
			$suppressErrorWhenFirstLineContains,
			$errorBuffer,
			$suppressError
		);

		$this->flushErrorBuffer(
			$suppressErrorWhenFirstLineContains,
			$errorBuffer,
			$suppressError
		);

		if($suppressError) {
			return 0;
		}

		return $process->getExitCode() ?? 127;
	}

	private function writeErrorOutput(
		string $errorOutput,
		?string $suppressErrorWhenFirstLineContains,
		string &$errorBuffer,
		?bool &$suppressError
	):void {
		if($errorOutput === "") {
			return;
		}

		if(!$suppressErrorWhenFirstLineContains || $suppressError === false) {
			$this->write($errorOutput, Stream::ERROR);
			return;
		}

		if($suppressError === true) {
			return;
		}

		$errorBuffer .= $errorOutput;
		if(!str_contains($errorBuffer, PHP_EOL)) {
			return;
		}

		$suppressError = $this->shouldSuppressErrorOutput(
			$errorBuffer,
			$suppressErrorWhenFirstLineContains
		);

		if(!$suppressError) {
			$this->write($errorBuffer, Stream::ERROR);
		}

		$errorBuffer = "";
	}

	private function flushErrorBuffer(
		?string $suppressErrorWhenFirstLineContains,
		string &$errorBuffer,
		?bool &$suppressError
	):void {
		if($errorBuffer === "") {
			return;
		}

		if(!$suppressErrorWhenFirstLineContains) {
			$this->write($errorBuffer, Stream::ERROR);
			$errorBuffer = "";
			return;
		}

		$suppressError ??= $this->shouldSuppressErrorOutput(
			$errorBuffer,
			$suppressErrorWhenFirstLineContains
		);

		if(!$suppressError) {
			$this->write($errorBuffer, Stream::ERROR);
		}

		$errorBuffer = "";
	}

	private function shouldSuppressErrorOutput(
		string $errorBuffer,
		string $suppressErrorWhenFirstLineContains
	):bool {
		$firstLine = strtok($errorBuffer, PHP_EOL);

		return str_contains(
			$firstLine ?: "",
			$suppressErrorWhenFirstLineContains
		);
	}

}
