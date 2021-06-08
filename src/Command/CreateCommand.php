<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;

class CreateCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
		$name = $arguments->get("projectName", "");
		if(!$name->get()) {
			$this->writeLine("What is the name of your project? (This will name its directory)");
			$name = $this->readLine();

			if(!$name) {
				$this->writeLine("Cancelling due to empty project name.");
				exit;
			}
		}

// TODO: Loop over readLine while entered option is invalid.
		$namespace = "App";
		if($arguments->contains("namespace")) {
			$namespace = $arguments->get("namespace")->get();
		}
		else {
			$this->writeLine("What namespace would you like to use?");
			$namespace = $this->readLine($namespace);

			if(!$namespace) {
				$this->writeLine("Cancelling due to empty namespace.");
				exit;
			}
		}

		$blueprint = "0";
		if($arguments->contains("blueprint")) {
			$blueprint = $arguments->get("blueprint")->get();
		}
		else {
			$this->writeLine("What blueprint would you like to start with? (type the number)");
			$this->writeLine(" 0: 'Empty' - only the basic dependencies, without anything extra.");
			$this->writeLine(" 1: 'Hello, World!' - a single page with the famous greeting.");
			$this->writeLine(" 2: 'Hello, You!' - a single page with basic interactivity.");
			$blueprint = $this->readLine($blueprint);

			if($blueprint < 0 || $blueprint > 2) {
				$this->writeLine("Cancelling due to invalid blueprint.");
				exit;
			}
		}

		$this->writeLine("Creating project '$name' in namespace '$namespace' with blueprint '$blueprint'...");
		sleep(1);
	}

	private function readLine(string $default = null):string {
		$prefix = "";

		if(!is_null($default)) {
			$prefix = "[$default]";
		}

		return readline("$prefix > ") ?: $default ?? "";
	}

	public function getName():string {
		return "create";
	}

	public function getDescription():string {
		return "An example command to prove the Gt command is working.";
	}

	/** @inheritDoc */
	public function getRequiredNamedParameterList():array {
		return [];
	}

	/** @inheritDoc */
	public function getOptionalNamedParameterList():array {
		return [
			new NamedParameter("projectName")
		];
	}

	/** @inheritDoc */
	public function getRequiredParameterList():array {
		return [];
	}

	/** @inheritDoc */
	public function getOptionalParameterList():array {
		return [
			new Parameter(
				true,
				"namespace",
				"n",
				"The application's root namespace",
				"MyApp",
			),
			new Parameter(
				true,
				"blueprint",
				"b",
				"A template project to build on",
				"hello-you",
			),
		];
	}
}
