<?php
namespace Gt\GtCommand\Command;

use Gt\Server\Cli\StartCommand as StartServerCommand;

class ServeCommand extends AbstractProxyCommand {
	public function __construct() {
		$this->proxyCommand = new StartServerCommand();
	}

	public function getName():string {
		return "serve";
	}
}
