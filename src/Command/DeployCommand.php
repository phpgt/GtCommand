<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;

class DeployCommand extends AbstractProxyCommand {
	/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
	public function __construct() {
		$this->proxyCommand = new class extends Command {
			public function getDescription():string {
				return "Not yet implemented";
			}

			public function run(ArgumentValueList $arguments = null):void {
			}

			public function getName():string {
				return "run";
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
		};
	}

	public function getName():string {
		return "deploy";
	}
}
