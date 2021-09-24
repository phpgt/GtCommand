<?php
namespace Gt\GtCommand\Command;

use Gt\Database\Cli\ExecuteCommand as ExecuteMigrationCommand;

class MigrateCommand extends AbstractProxyCommand {
	public function __construct() {
		$this->proxyCommand = new ExecuteMigrationCommand();
	}

	public function getName():string {
		return "migrate";
	}
}
