<?php
namespace Gt\GtCommand\Blueprint;

use Gt\Daemon\Process;
use SplFileObject;

class Blueprint {
	public function __construct(
		private string $key,
		private string $title,
		private string $description
	) {
	}

	public function getKey():string {
		return $this->key;
	}

	public function getTitle():string {
		return $this->title;
	}

	public function getDescription():string {
		return $this->description;
	}

	public function install(string $dir):Process {
		return new Process(
			"composer",
			"create-project",
			"--remove-vcs",
			"--stability dev",
			"webengine-blueprints/" . $this->key . ":dev-master",
			$dir
		);
	}
}
