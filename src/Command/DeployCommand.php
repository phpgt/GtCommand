<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;

class DeployCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
	}

	public function getName():string {
		return "deploy";
	}

	public function getDescription():string {
		return "Instantly deploy the application to the internet";
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
