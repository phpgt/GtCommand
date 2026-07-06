<?php
namespace GT\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Stream;
use GT\Daemon\Process;
use JsonException;

class TestCommand extends Command {
	/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
	public function run(?ArgumentValueList $arguments = null):int {
		unset($arguments);

		$testSuiteList = $this->getTestSuiteList();
		if(!$testSuiteList) {
			$this->writeLine("No test suites were found.", Stream::ERROR);
			$this->writeLine("Checked composer.json and package.json for a test script.", Stream::ERROR);
			return 1;
		}

		$passedCount = 0;
		$failedCount = 0;
		foreach($testSuiteList as $testSuite) {
			if($this->runTestSuite($testSuite)) {
				$passedCount++;
			}
			else {
				$failedCount++;
			}
		}

		$this->writeLine();
		if($failedCount > 0) {
			$this->writeLine("Test result: $passedCount passed, $failedCount failed.", Stream::ERROR);
			return 1;
		}

		$this->writeLine("Test result: $passedCount passed.");
		return 0;
	}

	public function getName():string {
		return "test";
	}

	public function getDescription():string {
		return "Run configured PHP and JavaScript test suites";
	}

	public function getRequiredNamedParameterList():array {
		return [];
	}

	public function getOptionalNamedParameterList():array {
		return [];
	}

	public function getRequiredParameterList():array {
		return [];
	}

	public function getOptionalParameterList():array {
		return [];
	}

	/** @return array<int, array{name: string, source: string, command: array<int, string>}> */
	private function getTestSuiteList():array {
		$testSuiteList = [];

		if($this->hasScript("composer.json", "test")) {
			$testSuiteList[] = [
				"name" => "PHP",
				"source" => "composer.json",
				"command" => ["composer", "test"],
			];
		}

		if($this->hasScript("package.json", "test")) {
			$testSuiteList[] = [
				"name" => "JavaScript",
				"source" => "package.json",
				"command" => ["npm", "run", "test"],
			];
		}

		return $testSuiteList;
	}

	private function hasScript(string $fileName, string $scriptName):bool {
		if(!is_file($fileName)) {
			return false;
		}

		$contents = file_get_contents($fileName);
		if($contents === false) {
			$this->writeLine("Unable to read $fileName.", Stream::ERROR);
			return false;
		}

		try {
			$json = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
		}
		catch(JsonException $exception) {
			$this->writeLine("Unable to parse $fileName: " . $exception->getMessage(), Stream::ERROR);
			return false;
		}

		if(!isset($json["scripts"]) || !is_array($json["scripts"])) {
			return false;
		}

		return isset($json["scripts"][$scriptName]);
	}

	/** @param array{name: string, source: string, command: array<int, string>} $testSuite */
	private function runTestSuite(array $testSuite):bool {
		$this->writeLine();
		$this->writeLine("Running {$testSuite["name"]} tests from {$testSuite["source"]}...");

		$process = new Process(...$testSuite["command"]);
		$process->exec();

		do {
			$this->write($process->getOutput());
			$this->write($process->getErrorOutput(), Stream::ERROR);
			usleep(100_000);
		}
		while($process->isRunning());

		$exitCode = $process->getExitCode();
		if($exitCode === 0) {
			$this->writeLine("{$testSuite["name"]} tests passed.");
			return true;
		}

		$this->writeLine("{$testSuite["name"]} tests failed with exit code $exitCode.", Stream::ERROR);
		return false;
	}
}
