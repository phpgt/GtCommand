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

class MigrateCommandTest extends TestCase {
	private string $projectRoot;
	private string $previousDirectory;

	protected function setUp():void {
		$this->projectRoot = sys_get_temp_dir() . "/phpgt-migrate-command-" . uniqid();
		mkdir($this->projectRoot, recursive: true);
		$this->previousDirectory = getcwd() ?: __DIR__;
		chdir($this->projectRoot);
	}

	protected function tearDown():void {
		chdir($this->previousDirectory);
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
		return new MigrateCommand(
			$sql,
			new SqlMigrationDetector(),
			static fn():?Command => $orm,
		);
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
