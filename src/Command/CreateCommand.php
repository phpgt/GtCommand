<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;

class CreateCommand extends Command {
	const BLUEPRINT_LIST = [
		"Empty" => "Only the basic dependencies, without anything extra",
		"Hello, World!" => "A single page with the famous greeting",
		"Hello, You!" => "A single page with basic interactivity",
		"To-do list" => "Basic database-driven application",
	];

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

		$blueprintKeys = array_keys(self::BLUEPRINT_LIST);
		$blueprintNumber = "0";
		if($arguments->contains("blueprint")) {
			$blueprintNumber = $arguments->get("blueprint")->get();
		}
		else {
			$this->writeLine("What blueprint would you like to start with? (type the number)");

			foreach($blueprintKeys as $i => $key) {
				$description = self::BLUEPRINT_LIST[$key];
				$this->writeLine( "$i: $key - $description");
			}
			$blueprintNumber = $this->readLine($blueprintNumber);

			if($blueprintNumber < 0 || $blueprintNumber >= count($blueprintKeys)) {
				$this->writeLine("Cancelling due to invalid blueprint.");
				exit;
			}
		}

		$selectedBlueprintKey = $blueprintKeys[$blueprintNumber];
		$this->writeLine("Creating project '$name' in namespace '$namespace' with blueprint '$selectedBlueprintKey'...");
		sleep(1);
		$this->writeLine("// TODO: Clone project with Composer!");
		$this->writeLine("// TODO: Ask user whether they want project serving.");
	}

	public function getName():string {
		return "create";
	}

	public function getDescription():string {
		return "Create a new WebEngine application";
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
