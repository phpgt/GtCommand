<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;

class CronCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
	}

	public function getName():string {
		return "cron";
	}

	public function getDescription():string {
		return "Invoke scripts or static functions at regular intervals";
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
