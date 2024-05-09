<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Build\Cli\RunCommand as CliRunCommand;

class BuildCommand extends AbstractProxyCommand {
	public function __construct() {
		$this->proxyCommand = new CliRunCommand();
	}

	public function run(ArgumentValueList $arguments = null):void {
		if(!$arguments->contains("default")) {
			$arguments->set("default", "vendor/phpgt/webengine/build.default.json");
		}

		parent::run($arguments);
	}

	public function getName():string {
		return "build";
	}
}
