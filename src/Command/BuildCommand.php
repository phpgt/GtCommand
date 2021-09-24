<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;

class BuildCommand extends AbstractProxyCommand {
	public function __construct() {
		$this->proxyCommand = new \Gt\Build\Cli\RunCommand();
	}

	public function getName():string {
		return "build";
	}
}
