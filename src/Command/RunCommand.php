<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Stream;
use Gt\Daemon\Pool;
use Gt\Daemon\Process;

class RunCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
		global $argv;

		$processList = [
			"serve" => new Process($argv[0], "serve"),
			"build" => new Process($argv[0], "build"),
			"cron" => new Process($argv[0], "cron"),
		];

		$pool = new Pool();
		foreach($processList as $name => $process) {
			$pool->add($name, $process);
		}

		$pool->exec();
		do {
			$this->write($pool->read());
			$this->write($pool->read(Process::PIPE_ERROR), Stream::ERROR);
			sleep(1);
		}
		while($pool->numRunning() > 0);
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

// TODO: Pass as many arguments through to the relevant commands.
	public function getOptionalParameterList():array {
		return [];
	}
}
