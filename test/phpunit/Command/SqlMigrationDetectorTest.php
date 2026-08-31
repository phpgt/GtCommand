<?php
namespace GT\GtCommand\Test\Command;

use Gt\Cli\Argument\ArgumentValueList;
use GT\GtCommand\Command\SqlMigrationDetector;
use PHPUnit\Framework\TestCase;

class SqlMigrationDetectorTest extends TestCase {
	private string $projectRoot;

	protected function setUp():void {
		$this->projectRoot = sys_get_temp_dir() . "/phpgt-migration-detector-" . uniqid();
		mkdir($this->projectRoot, recursive: true);
	}

	public function testProjectWithoutMigrationDirectoryHasNoMigrations():void {
		self::assertFalse((new SqlMigrationDetector())->hasMigrations($this->projectRoot));
	}

	public function testOnlyNumberedSqlFilesCountAsMigrations():void {
		$directory = $this->projectRoot . "/query/_migration";
		mkdir($directory, recursive: true);
		file_put_contents($directory . "/notes.sql", "select 1");
		self::assertFalse((new SqlMigrationDetector())->hasMigrations($this->projectRoot));

		file_put_contents($directory . "/001-create.sql", "select 1");
		self::assertTrue((new SqlMigrationDetector())->hasMigrations($this->projectRoot));
	}

	public function testConfiguredAndOverriddenQueryPathsAreUsed():void {
		file_put_contents($this->projectRoot . "/config.ini", <<<INI
			[database]
			query_path=database-query
			migration_path=changes
			INI);
		$directory = $this->projectRoot . "/database-query/changes";
		mkdir($directory, recursive: true);
		file_put_contents($directory . "/001.sql", "select 1");
		$detector = new SqlMigrationDetector();
		self::assertTrue($detector->hasMigrations($this->projectRoot));

		$arguments = new ArgumentValueList();
		$arguments->set("base-directory", "other-query");
		self::assertFalse($detector->hasMigrations($this->projectRoot, $arguments));
	}

	public function testDevMigrationsOnlyCountWhenRequested():void {
		$directory = $this->projectRoot . "/query/_migration/dev";
		mkdir($directory, recursive: true);
		file_put_contents($directory . "/001-dev.sql", "select 1");
		$detector = new SqlMigrationDetector();
		self::assertFalse($detector->hasMigrations($this->projectRoot));

		$arguments = new ArgumentValueList();
		$arguments->set("dev");
		self::assertTrue($detector->hasMigrations($this->projectRoot, $arguments));
	}
}
