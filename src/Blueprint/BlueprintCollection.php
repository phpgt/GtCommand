<?php
namespace Gt\GtCommand\Blueprint;

use Countable;
use Iterator;

/** @implements Iterator<int, Blueprint> */
class BlueprintCollection implements Iterator, Countable {
	const KEY_TITLE_DESCRIPTION_DATA = [
		["empty", "Empty", "Only the basic dependencies, without anything extra"],
		["hello-world", "Hello, World!", "A single page with the famous greeting"],
		["hello-you", "Hello, You!", "A single page with basic interactivity"],
		["todo", "To-do list", "Basic database-driven application"],
	];

	/** @var array<int, Blueprint> */
	private array $blueprintArray;
	private int $iteratorKey;

	public function __construct() {
		$this->iteratorKey = 0;

		$this->blueprintArray = [];
		foreach(self::KEY_TITLE_DESCRIPTION_DATA as $data) {
			[$key, $title, $description] = $data;
			array_push(
				$this->blueprintArray,
				new Blueprint(
					$key,
					$title,
					$description
				)
			);
		}
	}

	public function rewind():void {
		$this->iteratorKey = 0;
	}

	public function key():int {
		return $this->iteratorKey;
	}

	public function valid():bool {
		return isset($this->blueprintArray[$this->key()]);
	}

	public function current():Blueprint {
		return $this->blueprintArray[$this->key()];
	}

	public function next():void {
		$this->iteratorKey++;
	}

	public function count():int {
		return count($this->blueprintArray);
	}

	public function getByIndex(int $index):?Blueprint {
		return $this->blueprintArray[$index] ?? null;
	}

	public function getByKey(string $key):?Blueprint {
		foreach($this as $blueprint) {
			if(strcasecmp($blueprint->getKey(), $key) === 0) {
				return $blueprint;
			}
		}

		return null;
	}
}
