<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;

class ServeCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
	}

	public function getName():string {
		return "serve";
	}

	public function getDescription():string {
		return "Run the inbuilt development server";
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
