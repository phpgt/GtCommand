<?php
namespace Gt\GtCommand;

class UpdateChecker {
	private int $latestUpdateTimestamp;
	private string $tmpFilePath;

	public function __construct(
		private int $days = 30
	) {
		$this->tmpFilePath = dirname(__DIR__)
			. DIRECTORY_SEPARATOR
			. ".gt-update-check";
		if(!file_exists($this->tmpFilePath)) {
			file_put_contents(
				$this->tmpFilePath,
				"This file is used to track when the last time the 'gt' command was updated."
			);
		}

		$this->latestUpdateTimestamp = filemtime($this->tmpFilePath);
	}

	public function isDue():bool {
		$diff = time() - $this->latestUpdateTimestamp;
		$diffDays = $diff / 60 / 60 / 24;
		return $diffDays >= $this->days;
	}

	public function refresh():void {
		touch($this->tmpFilePath);
	}
}
