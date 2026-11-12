<?php
namespace GT\GtCommand\Command;

use GT\Cli\Argument\ArgumentValueList;
use GT\Cli\Command\Command;
use GT\Cli\Parameter\NamedParameter;
use GT\Cli\Parameter\Parameter;

class DeployCommand extends AbstractProxyCommand {
	/** @SuppressWarnings("PHPMD.UnusedFormalParameter") */
	public function __construct() {
		$this->proxyCommand = new class extends Command {
			public function getDescription():string {
				return "Not yet implemented";
			}

			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
			public function run(?ArgumentValueList $arguments = null):int {
				return 0;
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
