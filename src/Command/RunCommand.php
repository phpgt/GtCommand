<?php
namespace GT\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\Parameter;
use Gt\Cli\Stream;
use Gt\Daemon\Pool;
use Gt\Daemon\Process;

class RunCommand extends Command {
	/**
	 * TODO: Simplify this function.
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function run(?ArgumentValueList $arguments = null):int {
		global $argv;

		$serveArgs = [];
		if($arguments->contains("debug")) {
			array_push($serveArgs, "--debug");
		}
		$bindValue = $arguments->get("bind", "0.0.0.0");
		array_push($serveArgs, "--bind", $bindValue);
		$portValue = $arguments->get("port", "8080");
		array_push($serveArgs, "--port", $portValue);
		$threadsValue = $arguments->get("threads", "8");
		array_push($serveArgs, "--threads", $threadsValue);

		$processList = [
			"serve" => new Process(
				$argv[0],
				"serve",
				...$serveArgs,
			),
		];

		if(!$arguments->contains("no-build")) {
			$processList["build"] = new Process($argv[0], "build", "--watch");
		}

		if(!$arguments->contains("no-cron") && is_file("crontab")) {
			$processList["cron"] = new Process($argv[0], "cron", "--watch");
		}

		$pool = new Pool();
		foreach($processList as $name => $process) {
			$pool->add($name, $process);
		}

		$pool->exec();

		/** @noinspection HttpUrlsUsage */
		$localUrl = "http://";
		if($bindValue == "0.0.0.0" || $bindValue == "127.0.0.1") {
			$localUrl .= "localhost";
		}
		else {
			$localUrl .= $bindValue;
		}

		if($portValue != "80") {
			$localUrl .= ":$portValue";
		}

		$this->write("Starting WebEngine...");
		usleep(500_000);
		if($processList["serve"]->isRunning()) {
			$this->writeLine(" ✅");
			usleep(500_000);
			$this->writeLine();
			$this->writeLine("To view your application in your"
				. " browser, visit: $localUrl");
			$this->writeLine("To stop running, press [CTRL]+[C].");
			$this->writeLine();
			usleep(500_000);
		}
		else {
			$this->writeLine(" ❌");
			$this->write($processList["serve"]->getOutput(Process::PIPE_ERROR), Stream::ERROR);
			return 1;
		}

		$this->write($processList["serve"]->getOutput());
		$this->write($processList["serve"]->getOutput(Process::PIPE_ERROR), Stream::ERROR);

		do {
			$this->write($pool->read());
			$this->write($pool->read(Process::PIPE_ERROR), Stream::ERROR);
			usleep(100_000);
			/** @var bool $isRunning
			 * @noinspection PhpRedundantVariableDocTypeInspection
			 * This is necessary to suppress "Do-while loop condition is always true" error from phpstan. Bug with stan?
			 */
			$isRunning = $processList["serve"]->isRunning();
		}
		while($isRunning);
		$this->writeLine("The server process has ended.");
		return 2;
	}

	public function getName():string {
		return "run";
	}

	public function getDescription():string {
		return "Run all background scripts at once (serve, build, cron)";
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
		return [
			new Parameter(
				true,
				"port",
				"p"
			),
			new Parameter(
				true,
				"bind",
				"b"
			),
			new Parameter(
				false,
				"debug",
				"d"
			),
			new Parameter(
				false,
				"no-build"
			),
			new Parameter(
				false,
				"no-cron"
			)
		];
	}
}
