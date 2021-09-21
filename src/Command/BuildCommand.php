<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;

class BuildCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
	}

	public function getName():string {
		return "build";
	}

	public function getDescription():string {
		return "Compile client-side assets";
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
