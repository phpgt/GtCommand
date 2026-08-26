<?php
namespace GT\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Stream;
use GT\Daemon\Process;
use JsonException;

class InitCommand extends Command {
	/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
	public function run(?ArgumentValueList $arguments = null):int {
		$this->writeLine("Initialising WebEngine in " . getcwd() . "...");

		$exitCode = $this->runComposer("require", "--no-interaction", "phpgt/webengine");
		if($exitCode !== 0) {
			$this->writeLine("Composer could not install WebEngine.", Stream::ERROR);
			return $exitCode;
		}

		if(!$this->addDefaultAutoloadMappings()) {
			return 1;
		}

		$exitCode = $this->runComposer("dump-autoload", "--no-interaction");
		if($exitCode !== 0) {
			$this->writeLine("Composer could not regenerate the autoloader.", Stream::ERROR);
			return $exitCode;
		}

		$this->writeLine();
		$this->writeLine("This directory is now a WebEngine application.");
		$this->writeLine("Run 'gt run' to start it.");

		return 0;
	}

	public function getName():string {
		return "init";
	}

	public function getDescription():string {
		return "Initialise WebEngine in the current directory";
	}

	/** @inheritDoc */
	public function getRequiredNamedParameterList():array {
		return [];
	}

	/** @inheritDoc */
	public function getOptionalNamedParameterList():array {
		return [];
	}

	/** @inheritDoc */
	public function getRequiredParameterList():array {
		return [];
	}

	/** @inheritDoc */
	public function getOptionalParameterList():array {
		return [];
	}

	private function runComposer(string ...$arguments):int {
		$process = new Process("composer", ...$arguments);
		$process->exec();

		do {
			$this->write($process->getOutput());
			$this->write($process->getErrorOutput(), Stream::ERROR);
			usleep(100_000);
		}
		while($process->isRunning());

		$this->write($process->getOutput());
		$this->write($process->getErrorOutput(), Stream::ERROR);

		return $process->getExitCode() ?? 1;
	}

	private function addDefaultAutoloadMappings():bool {
		$composerPath = getcwd() . DIRECTORY_SEPARATOR . "composer.json";
		$composerJson = file_get_contents($composerPath);
		if($composerJson === false) {
			$this->writeLine("Could not read composer.json.", Stream::ERROR);
			return false;
		}

		try {
			/** @var array<string, mixed> $composerConfig */
			$composerConfig = json_decode($composerJson, true, flags: JSON_THROW_ON_ERROR);
		}
		catch(JsonException $exception) {
			$this->writeLine(
				"Could not parse composer.json: " . $exception->getMessage(),
				Stream::ERROR
			);
			return false;
		}

		$composerConfig["autoload"]["psr-4"]["App\\"] ??= "./class";
		$composerConfig["autoload-dev"]["psr-4"]["App\\Test\\"] ??= "./test/phpunit";

		$encodedConfig = json_encode(
			$composerConfig,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
		);

		if(file_put_contents($composerPath, $encodedConfig . PHP_EOL) === false) {
			$this->writeLine("Could not update composer.json.", Stream::ERROR);
			return false;
		}

		return true;
	}
}
