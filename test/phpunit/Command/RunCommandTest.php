<?php
namespace GT\GtCommand\Test\Command;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RunCommandTest extends TestCase {
	#[DataProvider("projectErrors")]
	public function testProjectError(
		?string $manifest,
		bool $installed,
		bool $nested,
		string $error,
	):void {
		$projectRoot = sys_get_temp_dir() . "/phpgt-run-command-" . uniqid();
		mkdir($projectRoot);
		try {
			if($manifest !== null) {
				file_put_contents("$projectRoot/composer.json", $manifest);
			}
			if($installed) {
				mkdir("$projectRoot/vendor/phpgt/webengine", recursive: true);
				touch("$projectRoot/vendor/phpgt/webengine/go.php");
			}
			$directory = $projectRoot;
			if($nested) {
				$directory .= "/page/class";
				mkdir($directory, recursive: true);
				file_put_contents("$directory/composer.json", '{}');
			}

			$command = [PHP_BINARY, dirname(__DIR__, 3) . "/bin/gt", "run"];
			$process = proc_open($command, [
				1 => ["pipe", "w"],
				2 => ["pipe", "w"],
			], $pipes, $directory);
			self::assertIsResource($process);
			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			self::assertSame(1, proc_close($process));
			self::assertSame("", $stdout);
			self::assertSame(sprintf($error, $projectRoot) . PHP_EOL, $stderr);
		}
		finally {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST,
			);
			foreach($files as $file) {
				if($file->isDir()) {
					rmdir($file->getPathname());
				}
				else {
					unlink($file->getPathname());
				}
			}
			rmdir($projectRoot);
		}
	}

	public static function projectErrors():array {
		$webEngine = '{"require":{"phpgt/webengine":"^2.0"}}';
		return [
			"root without dependencies" => [
				$webEngine, false, false,
				"Error: WebEngine dependencies are missing. Run 'composer install', then retry 'gt run'.",
			],
			"nested with dependencies" => [
				null, true, true,
				"Error: WebEngine project found at %s. Run 'gt run' from that directory.",
			],
			"nested without dependencies" => [
				$webEngine, false, true,
				"Error: WebEngine project found at %s. Run 'composer install' there, then run 'gt run'.",
			],
			"unrelated Composer project" => [
				'{"require":{"phpgt/cli":"^2.0"}}', false, false,
				"Error: Current directory is not a WebEngine project",
			],
		];
	}
}
