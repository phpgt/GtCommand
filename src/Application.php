<?php
namespace Gt\GtCommand;

use Gt\Cli\Application as CliApplication;

/**
 * This extension class will keep track of when the update command was last
 * executed, and will alert the developer if 30 days passes without an update.
 */
class Application extends CliApplication {
	public function run():void {
		$updateChecker = new UpdateChecker();
		if($updateChecker->isDue()) {
			$this->stream->writeLine();
			$this->stream->writeLine("Note: You haven't ran 'gt update' in over 30 days.");
			$this->stream->writeLine();
		}
		parent::run();
	}
}
