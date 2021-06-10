<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;
use Gt\Cli\Stream;
use Gt\Daemon\Process;
use Gt\GtCommand\Blueprint\Blueprint;
use Gt\GtCommand\Blueprint\BlueprintCollection;

class CreateCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
		$name = $arguments->get("projectName", "");

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
			exit(1);
		}

		$this->writeLine();
		$this->writeLine("Creating application '$name' in: " . getcwd() . "/$name");
		$this->writeLine();

		$namespace = $arguments->get("namespace", "");;
		$i = 0;
		while(!$this->isValidNamespace($namespace)) {
			if($i > 0) {
				$this->writeLine("The namespace '$namespace' is not a valid PHP Namespace.", Stream::ERROR);
			}

			$this->writeLine("What namespace would you like to use for autoloaded classes?");
			$namespace = "App";
			$namespace = $this->readLine($namespace);
			$i++;
		}
		$this->writeLine();
		$this->writeLine("Using namespace '$namespace'.");
		$this->writeLine();

		$blueprintCollection = new BlueprintCollection();
		$blueprintInput = "0";
		if($arguments->contains("blueprint")) {
			$blueprintInput = $arguments->get("blueprint")->get();
		}
		else {
			$this->writeLine("What blueprint would you like to start with? (type the number)");

			foreach($blueprintCollection as $i => $blueprint) {
				$title = $blueprint->getTitle();
				$description = $blueprint->getDescription();
				$this->writeLine( " $i: $title - $description");
			}
			$blueprintInput = $this->readLine($blueprintInput);

			if($blueprintInput < 0 || $blueprintInput >= count($blueprintCollection)) {
				$this->writeLine("Cancelling due to invalid blueprint.");
				exit;
			}
		}

		if(is_numeric($blueprintInput)) {
			$selectedBlueprint = $blueprintCollection->getByIndex($blueprintInput);
		}
		else {
			$selectedBlueprint = $blueprintCollection->getByKey($blueprintInput);
		}

		$selectedBlueprintTitle = $selectedBlueprint->getTitle();
		$this->writeLine("Creating project '$name' in namespace '$namespace' with blueprint '$selectedBlueprintTitle'...");
		sleep(1);

		$process = $selectedBlueprint->install($name);
		$process->exec();
		$process->setBlocking(false);

		do {
			$this->write($process->getOutput());
			$this->write($process->getErrorOutput(), Stream::ERROR);
			usleep(100_000);
		}
		while($process->isRunning());

		$this->writeLine();
		$this->writeLine("Your new application is created!");
		$this->writeLine("Would you like to run it now? (Y/N)");

		do {
			$runNow = strtolower($this->readLine("N"));
		}
		while(!$runNow || !in_array($runNow[0], ["y", "n"]));

		if($runNow === "y") {
			$this->writeLine();
			$this->writeLine("Okay - running your new application...");
			sleep(0.5);
			chdir($name);

			$process = new Process("gt", "run");
			$process->exec();
			do {
				$this->write(
					$process->getOutput(),
					Stream::OUT
				);
				$this->write(
					$process->getErrorOutput(),
					Stream::ERROR
				);
				usleep(1_000_000);
			}
			while($process->isRunning());
		}
		else {
			$this->writeLine();
			$this->writeLine("Your new application is in the '$name' directory.");
			$this->writeLine("Docs: https://www.php.gt/webengine/getting-started");
			$this->writeLine("Have fun!");
			$this->writeLine();
		}
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
				"The application's root namespace",
				"MyApp",
			),
			new Parameter(
				true,
				"blueprint",
				"b",
				"A template project to build on",
				"hello-you",
			),
		];
	}

	private function isValidName(string $name):bool {
		if(strlen($name) === 0) {
			return false;
		}

		if(preg_match("/[^a-z0-9_]/i", $name)) {
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
}
