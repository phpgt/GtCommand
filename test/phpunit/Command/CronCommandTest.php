<?php
namespace GT\GtCommand\Test\Command;

use GT\Cli\Argument\ArgumentValueList;
use GT\Cli\Stream;
use GT\GtCommand\Command\CronCommand;
use PHPUnit\Framework\TestCase;

class CronCommandTest extends TestCase {
	private string $project;
	private string $previousDirectory;

	protected function setUp():void {
		$this->previousDirectory = getcwd();
		$this->project = sys_get_temp_dir() . "/gt-cron-" . uniqid();
		mkdir("$this->project/vendor/bin", 0777, true);
		file_put_contents("$this->project/vendor/bin/cron", "<?php");
		file_put_contents("$this->project/crontab", "0 * * * * example\n");
		chdir($this->project);
	}

	protected function tearDown():void {
		chdir($this->previousDirectory);
		foreach(["cron.service", "crontab", "vendor/bin/cron"] as $path) {
			if(is_file("$this->project/$path")) {
				unlink("$this->project/$path");
			}
		}
		rmdir("$this->project/vendor/bin");
		rmdir("$this->project/vendor");
		rmdir($this->project);
	}

	public function testRun_generatesServiceUsingReleasedCron():void {
		$sut = new CronCommand();
		$sut->setStream(new Stream("php://memory", "php://memory", "php://memory"));
		$arguments = new ArgumentValueList();
		$arguments->set("generate-service");
		$arguments->set("user", "www-data");
		$arguments->set("restart-seconds", "15");
		self::assertSame(0, $sut->run($arguments));
		$contents = file_get_contents("$this->project/cron.service");
		self::assertStringContainsString("User=www-data\n", $contents);
		self::assertStringContainsString("WorkingDirectory=$this->project\n", $contents);
		self::assertStringContainsString('vendor/bin/cron" --watch', $contents);
		self::assertStringContainsString("RestartSec=15\n", $contents);
	}

	public function testRun_failedGenerationPropagatesFailureAndDiagnostics():void {
		$sut = new CronCommand();
		$stream = new Stream("php://memory", "php://memory", "php://memory");
		$sut->setStream($stream);
		$arguments = new ArgumentValueList();
		$arguments->set("generate-service");
		$arguments->set("restart-seconds", "-1");
		self::assertSame(2, $sut->run($arguments));
		self::assertFileDoesNotExist("$this->project/cron.service");
		$stream->getErrorStream()->rewind();
		self::assertStringContainsString("Restart seconds must be greater than zero", $stream->getErrorStream()->fread(10000));
	}
}
