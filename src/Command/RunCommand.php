<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;

class RunCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
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
		return [];
	}
}
