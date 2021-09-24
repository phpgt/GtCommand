<?php
namespace Gt\GtCommand\Command;

use Gt\Cron\Cli\RunCommand as CronRunCommand;

class CronCommand extends AbstractProxyCommand {
	public function __construct() {
		$this->proxyCommand = new CronRunCommand();
	}

	public function getName():string {
		return "cron";
	}
}
