<?php
namespace GT\GtCommand\Command;

use Closure;
use Composer\Autoload\ClassLoader;
use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\Parameter;
use Gt\Cli\Stream;
use GT\Database\Cli\ExecuteCommand as ExecuteMigrationCommand;
use Throwable;

class MigrateCommand extends Command {
	/** @var Closure():?Command */
	private Closure $ormCommandFactory;

	/** @param null|Closure():?Command $ormCommandFactory */
	public function __construct(
		private readonly Command $sqlCommand = new ExecuteMigrationCommand(),
		private readonly SqlMigrationDetector $sqlMigrationDetector = new SqlMigrationDetector(),
		?Closure $ormCommandFactory = null,
	) {
		$this->ormCommandFactory = $ormCommandFactory
			?? static function():?Command {
				$className = "GT\\Orm\\Cli\\MigrateCommand";
				if(!class_exists($className)
				|| !is_a($className, Command::class, true)) {
					return null;
				}
				return new $className();
			};
	}

	public function run(?ArgumentValueList $arguments = null):int {
		$projectRoot = getcwd();
		if($projectRoot === false) {
			$this->output("Unable to determine the project directory.", streamName: Stream::ERROR);
			return 1;
		}

		try {
			if($this->sqlMigrationDetector->hasMigrations($projectRoot, $arguments)) {
				$this->sqlCommand->setStream($this->stream ?? null);
				$status = $this->sqlCommand->run($arguments);
				if($status !== 0) {
					return $status;
				}
			}

			return $this->runOrm($projectRoot, $arguments);
		}
		catch(Throwable $exception) {
			$this->output(
				"Migration failed: " . $exception->getMessage(),
				streamName: Stream::ERROR,
			);
			return 1;
		}
	}

	private function runOrm(string $projectRoot, ?ArgumentValueList $arguments):int {
		if($arguments?->contains("no-orm")) {
			return 0;
		}
		if(!$this->projectHasOrm($projectRoot)) {
			return 0;
		}
		$ormCommand = ($this->ormCommandFactory)();
		if($ormCommand === null) {
			return 0;
		}
		$ormCommand->setStream($this->stream ?? null);
		return $ormCommand->run($arguments);
	}

	private function projectHasOrm(string $projectRoot):bool {
		$autoload = "$projectRoot/vendor/autoload.php";
		if(!is_file($autoload)) {
			return false;
		}

		// Composer returns the loader even if it has already been registered.
		// Ask this loader directly: class_exists() also searches global packages.
		$loader = require $autoload;
		return $loader instanceof ClassLoader
			&& $loader->findFile("GT\\Orm\\Cli\\MigrateCommand") !== false;
	}

	public function getName():string {
		return "migrate";
	}

	public function getDescription():string {
		return "Perform SQL-file and ORM Entity migrations";
	}

	public function getRequiredNamedParameterList():array {
		return $this->sqlCommand->getRequiredNamedParameterList();
	}

	public function getOptionalNamedParameterList():array {
		return $this->sqlCommand->getOptionalNamedParameterList();
	}

	public function getRequiredParameterList():array {
		return $this->sqlCommand->getRequiredParameterList();
	}

	public function getOptionalParameterList():array {
		return [
			...$this->sqlCommand->getOptionalParameterList(),
			new Parameter(false, "no-orm", null, "Skip ORM Entity migrations"),
			new Parameter(false, "orm-baseline", null, "Record the current Entity schema without changing tables"),
			new Parameter(false, "orm-plan", null, "Display ORM changes without applying them"),
		];
	}
}
