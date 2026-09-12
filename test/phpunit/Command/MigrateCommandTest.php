<?php
namespace GT\GtCommand\Test\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;
use GT\GtCommand\Command\MigrateCommand;
use GT\GtCommand\Command\SqlMigrationDetector;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Composer\Autoload\ClassLoader;

class MigrateCommandTest extends TestCase {
	private string $projectRoot;
	private string $previousDirectory;
	/** @var list<callable> */
	private array $previousAutoloaders;

	protected function setUp():void {
		$this->previousAutoloaders = spl_autoload_functions();
		$this->projectRoot = sys_get_temp_dir() . "/phpgt-migrate-command-" . uniqid();
		mkdir($this->projectRoot, recursive: true);
		$this->previousDirectory = getcwd() ?: __DIR__;
		chdir($this->projectRoot);
	}

	protected function tearDown():void {
		foreach(spl_autoload_functions() as $autoload) {
			if(!in_array($autoload, $this->previousAutoloaders, true)) {
				spl_autoload_unregister($autoload);
			}
		}
		chdir($this->previousDirectory);
	}

	public function testGloballyAvailableOrmDoesNotEnableSqlOnlyProject():void {
		$this->createSqlMigration();
		$this->createProjectAutoloader(false);
		$globalLoader = new ClassLoader();
		$globalLoader->addClassMap(["GT\\Orm\\Cli\\MigrateCommand" => __FILE__]);
		$globalLoader->register();
		$sql = new RecordingCommand("sql");
		$command = new MigrateCommand($sql, ormCommandFactory: static function():?Command {
			self::fail("A globally available ORM must not enable project migrations.");
		});

		self::assertSame(0, $command->run());
		self::assertSame(1, $sql->runCount);
	}

	public function testMissingProjectAutoloaderSkipsOrmFactory():void {
		$command = new MigrateCommand(ormCommandFactory: static function():?Command {
			self::fail("ORM must not run without a project autoloader.");
		});

		self::assertSame(0, $command->run());
	}

	public function testProjectAutoloaderIsRegisteredBeforeOrmFactoryAndCanBeLoadedAgain():void {
		$this->createProjectAutoloader();
		$loader = require $this->projectRoot . "/vendor/autoload.php";
		$orm = new RecordingCommand("orm");
		$command = new MigrateCommand(ormCommandFactory: static function() use ($loader, $orm):Command {
			self::assertContains([$loader, "loadClass"], spl_autoload_functions());
			return $orm;
		});

		self::assertSame(0, $command->run());
		self::assertSame(1, $orm->runCount);
	}

	public function testProjectClassesCanBeAutoloadedDuringOrmDiscovery():void {
		$this->createProjectAutoloader();
		$className = "MigrationProjectFixture" . uniqid();
		file_put_contents($this->projectRoot . "/vendor/$className.php", "<?php class $className {}");
		$orm = new RecordingCommand("orm");
		$command = new MigrateCommand(ormCommandFactory: static function() use ($className, $orm):Command {
			self::assertTrue(class_exists($className));
			return $orm;
		});

		self::assertFalse(class_exists($className, false));
		self::assertSame(0, $command->run());
		self::assertSame(1, $orm->runCount);
	}

	public function testNeitherMigrationStyleIsANoOp():void {
		$sql = new RecordingCommand("sql");
		$command = $this->command($sql, null);

		self::assertSame(0, $command->run(new ArgumentValueList()));
		self::assertSame(0, $sql->runCount);
	}

	public function testSqlOnlyRunsSqlPhase():void {
		$this->createSqlMigration();
		$sql = new RecordingCommand("sql");

		self::assertSame(0, $this->command($sql, null)->run(new ArgumentValueList()));
		self::assertSame(1, $sql->runCount);
	}

	public function testOrmOnlyRunsOrmPhase():void {
		$sql = new RecordingCommand("sql");
		$orm = new RecordingCommand("orm");

		self::assertSame(0, $this->command($sql, $orm)->run(new ArgumentValueList()));
		self::assertSame(0, $sql->runCount);
		self::assertSame(1, $orm->runCount);
	}

	public function testNoOrmOptionSkipsOrmPhase():void {
		$orm = new RecordingCommand("orm");
		$arguments = new ArgumentValueList();
		$arguments->set("no-orm");

		self::assertSame(0, $this->command(new RecordingCommand("sql"), $orm)->run($arguments));
		self::assertSame(0, $orm->runCount);
	}

	public function testSqlRunsBeforeOrmWhenBothArePresent():void {
		$this->createSqlMigration();
		$log = new MigrationCallLog();
		$sql = new RecordingCommand("sql", $log);
		$orm = new RecordingCommand("orm", $log);

		self::assertSame(0, $this->command($sql, $orm)->run(new ArgumentValueList()));
		self::assertSame(["sql", "orm"], $log->calls);
	}

	public function testSqlFailureStopsOrmAndIsPropagated():void {
		$this->createSqlMigration();
		$sql = new RecordingCommand("sql", status: 7);
		$orm = new RecordingCommand("orm");

		self::assertSame(7, $this->command($sql, $orm)->run(new ArgumentValueList()));
		self::assertSame(0, $orm->runCount);
	}

	public function testOrmFailureIsPropagated():void {
		$orm = new RecordingCommand("orm", status: 2);

		self::assertSame(2, $this->command(new RecordingCommand("sql"), $orm)->run(new ArgumentValueList()));
		self::assertSame(1, $orm->runCount);
	}

	public function testThrownFailureReturnsNonZeroAndStopsOrm():void {
		$this->createSqlMigration();
		$sql = new RecordingCommand("sql", exception: new RuntimeException("broken"));
		$orm = new RecordingCommand("orm");

		self::assertSame(1, $this->command($sql, $orm)->run(new ArgumentValueList()));
		self::assertSame(0, $orm->runCount);
	}

	public function testOrmOptionsAreAddedToSqlOptions():void {
		$command = $this->command(new RecordingCommand("sql"), null);
		$optionNames = array_map(
			fn(Parameter $parameter):string => $parameter->getLongOption(),
			$command->getOptionalParameterList(),
		);

		self::assertContains("sql-option", $optionNames);
		self::assertContains("no-orm", $optionNames);
		self::assertContains("orm-baseline", $optionNames);
		self::assertContains("orm-plan", $optionNames);
	}

	private function command(RecordingCommand $sql, ?RecordingCommand $orm):MigrateCommand {
		if($orm !== null) {
			$this->createProjectAutoloader();
		}
		return new MigrateCommand(
			$sql,
			new SqlMigrationDetector(),
			static fn():?Command => $orm,
		);
	}

	private function createProjectAutoloader(bool $withOrm = true):void {
		$directory = $this->projectRoot . "/vendor";
		mkdir($directory, recursive: true);
		$classMap = $withOrm ? ["GT\\Orm\\Cli\\MigrateCommand" => __FILE__] : [];
		file_put_contents($directory . "/autoload.php", "<?php\n"
			. '$loader = new \\Composer\\Autoload\\ClassLoader();' . "\n"
			. '$loader->addPsr4("", __DIR__);' . "\n"
			. '$loader->addClassMap(' . var_export($classMap, true) . ");\n"
			. '$loader->register();' . "\n"
			. 'return $loader;' . "\n");
	}

	private function createSqlMigration():void {
		$directory = $this->projectRoot . "/query/_migration";
		mkdir($directory, recursive: true);
		file_put_contents($directory . "/001.sql", "select 1");
	}
}

class MigrationCallLog {
	/** @var list<string> */
	public array $calls = [];
}

class RecordingCommand extends Command {
	public int $runCount = 0;

	public function __construct(
		private readonly string $name,
		private readonly ?MigrationCallLog $log = null,
		private readonly int $status = 0,
		private readonly ?RuntimeException $exception = null,
	) {}

	public function run(?ArgumentValueList $arguments = null):int {
		$this->runCount++;
		if($this->log !== null) {
			$this->log->calls[] = $this->name;
		}
		if($this->exception !== null) {
			throw $this->exception;
		}
		return $this->status;
	}

	public function getName():string {
		return $this->name;
	}

	public function getDescription():string {
		return $this->name;
	}

	/** @return list<NamedParameter> */
	public function getRequiredNamedParameterList():array {
		return [];
	}

	/** @return list<NamedParameter> */
	public function getOptionalNamedParameterList():array {
		return [];
	}

	/** @return list<Parameter> */
	public function getRequiredParameterList():array {
		return [];
	}

	/** @return list<Parameter> */
	public function getOptionalParameterList():array {
		return [new Parameter(false, "sql-option")];
	}
}
