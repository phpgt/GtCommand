<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;
use Gt\Database\Cli\ExecuteCommand as ExecuteMigrationCommand;

class MigrateCommand extends Command {
	private ExecuteMigrationCommand $executeMigrationCommand;

	public function __construct() {
		$this->executeMigrationCommand = new ExecuteMigrationCommand();
	}

	public function run(ArgumentValueList $arguments = null):void {
		$this->executeMigrationCommand->setStream($this->stream);
		$this->executeMigrationCommand->run($arguments);
	}

	public function getName():string {
		return "migrate";
	}

	public function getDescription():string {
		return $this->executeMigrationCommand->getDescription();
	}

	public function getRequiredNamedParameterList():array {
		return $this->executeMigrationCommand->getRequiredNamedParameterList();
	}

	public function getOptionalNamedParameterList():array {
		return $this->executeMigrationCommand->getOptionalNamedParameterList();
	}

	public function getRequiredParameterList():array {
		return $this->executeMigrationCommand->getRequiredParameterList();
	}

	public function getOptionalParameterList():array {
		return $this->executeMigrationCommand->getOptionalParameterList();
	}
}
