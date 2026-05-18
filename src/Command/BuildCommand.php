<?php
namespace GT\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use GT\Build\Cli\RunCommand as CliRunCommand;

class BuildCommand extends AbstractProxyCommand {
	public function __construct() {
		$this->proxyCommand = new CliRunCommand();
	}

	public function run(?ArgumentValueList $arguments = null):int {
		if(!$arguments->contains("default")) {
			$defaultPathPrefix = "vendor/phpgt/webengine/build.default";
			$defaultPathExtensionPriority = ["ini", "json"];
			foreach($defaultPathExtensionPriority as $ext) {
				$defaultPath = "$defaultPathPrefix.$ext";
				if(file_exists($defaultPath)) {
					$arguments->set("default", $defaultPath);
					break;
				}
			}
		}

		return parent::run($arguments);
	}

	public function getName():string {
		return "build";
	}
}
