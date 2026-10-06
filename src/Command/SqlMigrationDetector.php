<?php
namespace GT\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Config\Config;
use Gt\Config\ConfigFactory;

class SqlMigrationDetector {
	public function hasMigrations(
		string $projectRoot,
		?ArgumentValueList $arguments = null,
	):bool {
		$config = $this->loadConfig($projectRoot);
		$queryPath = $arguments?->contains("base-directory")
			? $arguments->get("base-directory")->get()
			: $config->get("database.query_path");
		$queryPath ??= "query";
		$migrationPath = $config->get("database.migration_path") ?? "_migration";
		$directory = $this->resolvePath($projectRoot, $queryPath)
			. DIRECTORY_SEPARATOR . $migrationPath;

		if($this->containsNumberedSqlFile($directory)) {
			return true;
		}
		if(!$arguments?->contains("dev")
		&& !$arguments?->contains("dev-merge")) {
			return false;
		}

		$devPath = $config->get("database.dev_migration_path")
			?? "_migration" . DIRECTORY_SEPARATOR . "dev";
		$devDirectory = $this->resolvePath($projectRoot, $queryPath)
			. DIRECTORY_SEPARATOR . $devPath;
		return $this->containsNumberedSqlFile($devDirectory);
	}

	/** @SuppressWarnings("PHPMD.StaticAccess") */
	private function loadConfig(string $projectRoot):Config {
		$defaultPath = $this->findDefaultConfig($projectRoot);
		if($defaultPath === null && !$this->hasProjectConfig($projectRoot)) {
			return new Config();
		}
		return ConfigFactory::createForProject($projectRoot, $defaultPath);
	}

	private function findDefaultConfig(string $projectRoot):?string {
		$directory = $this->resolvePath($projectRoot, "vendor/phpgt/webengine");
		foreach(["config.default.ini", "default.ini"] as $fileName) {
			$path = "$directory/$fileName";
			if(is_file($path)) {
				return $path;
			}
		}
		return null;
	}

	private function hasProjectConfig(string $projectRoot):bool {
		foreach(["config.default.ini", "config.ini", "config.dev.ini", "config.deploy.ini", "config.production.ini"] as $fileName) {
			if(is_file($this->resolvePath($projectRoot, $fileName))) {
				return true;
			}
		}
		return false;
	}

	private function containsNumberedSqlFile(string $directory):bool {
		$fileList = glob("$directory/*.sql") ?: [];
		foreach($fileList as $file) {
			if(preg_match("/^\\d+.*\\.sql$/", basename($file)) === 1) {
				return true;
			}
		}
		return false;
	}

	private function resolvePath(string $projectRoot, string $path):string {
		if(str_starts_with($path, DIRECTORY_SEPARATOR)) {
			return $path;
		}
		return $projectRoot . DIRECTORY_SEPARATOR . $path;
	}
}
