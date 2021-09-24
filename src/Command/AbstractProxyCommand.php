<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;

abstract class AbstractProxyCommand extends Command {
	protected Command $proxyCommand;

	public function run(ArgumentValueList $arguments = null):void {
		$this->proxyCommand->setStream($this->stream);
		$this->proxyCommand->run($arguments);
	}

	public function getDescription():string {
		return $this->proxyCommand->getDescription();
	}

	public function getRequiredNamedParameterList():array {
		return $this->proxyCommand->getRequiredNamedParameterList();
	}

	public function getOptionalNamedParameterList():array {
		return $this->proxyCommand->getOptionalNamedParameterList();
	}

	public function getRequiredParameterList():array {
		return $this->proxyCommand->getRequiredParameterList();
	}

	public function getOptionalParameterList():array {
		return $this->proxyCommand->getOptionalParameterList();
	}
}
