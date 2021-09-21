<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Server\Command\StartCommand as StartServerCommand;

class ServeCommand extends Command {
	private StartServerCommand $startServerCommand;

	public function __construct() {
		$this->startServerCommand = new StartServerCommand();
	}

	public function run(ArgumentValueList $arguments = null):void {
		$this->startServerCommand->setStream($this->stream);
		$this->startServerCommand->run($arguments);
	}

	public function getName():string {
		return "serve";
	}

	public function getDescription():string {
		return $this->startServerCommand->getDescription();
	}

	public function getRequiredNamedParameterList():array {
		return $this->startServerCommand->getRequiredNamedParameterList();
	}

	public function getOptionalNamedParameterList():array {
		return $this->startServerCommand->getOptionalNamedParameterList();
	}

	public function getRequiredParameterList():array {
		return $this->startServerCommand->getRequiredParameterList();
	}

	public function getOptionalParameterList():array {
		return $this->startServerCommand->getOptionalParameterList();
	}
}
