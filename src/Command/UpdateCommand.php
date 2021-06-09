<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;
use Gt\GtCommand\UpdateChecker;

class UpdateCommand extends \Gt\Cli\Command\Command {
	public function run(ArgumentValueList $arguments = null):void {
		$updateChecker = new UpdateChecker();
		$updateChecker->refresh();
	}

	public function getName():string {
		return "update";
	}

	public function getDescription():string {
		return "Updates the 'gt' command using Composer";
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
}
