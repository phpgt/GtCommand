<?php
namespace Gt\GtCommand\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Stream;
use RuntimeException;

class AddCommand extends Command {
	private const SUPPORTED_TYPES = [
		"page",
		"api",
		"cron",
	];

	public function run(?ArgumentValueList $arguments = null):void {
		$type = (string)$arguments?->get("type", "");
		$name = (string)$arguments?->get("name", "");
		$template = (string)$arguments?->get("template", "");

		$this->assertValidType($type);
		$this->assertValidName($name);

		$templateFileMap = $this->getTemplateFileMap($type, $template);
		$destinationFileMap = $this->getDestinationFileMap($type, $name, $templateFileMap);

		$this->assertDestinationFilesDoNotExist($destinationFileMap);
		$this->ensureDestinationDirectoryExists($type);
		foreach($destinationFileMap as $sourcePath => $destinationPath) {
			$this->copyTemplateFile($sourcePath, $destinationPath, $name);
		}
	}

	public function getName():string {
		return "add";
	}

	public function getDescription():string {
		return "Add a page, API endpoint or cron script from a template";
	}

	public function getRequiredNamedParameterList():array {
		return [
			new NamedParameter("type"),
			new NamedParameter("name"),
		];
	}

	public function getOptionalNamedParameterList():array {
		return [
			new NamedParameter("template"),
		];
	}

	public function getRequiredParameterList():array {
		return [];
	}

	public function getOptionalParameterList():array {
		return [];
	}

	private function assertValidType(string $type):void {
		if(in_array($type, self::SUPPORTED_TYPES)) {
			return;
		}

		$supportedTypes = implode(", ", self::SUPPORTED_TYPES);
		$this->writeLine("Unknown add type '$type'. Supported types: $supportedTypes", Stream::ERROR);
		exit(1); // phpcs:ignore
	}

	private function assertValidName(string $name):void {
		if($name && !preg_match("/[^a-z0-9_-]/i", $name)) {
			return;
		}

		$this->writeLine(
			"Invalid name '$name'. Use only letters, numbers, hyphens and underscores.",
			Stream::ERROR
		);
		exit(1); // phpcs:ignore
	}

	/** @return array<string, string> */
	private function getTemplateFileMap(string $type, string $template):array {
		if($template) {
			return $this->getProjectTemplateFileMap($type, $template);
		}

		return $this->getBuiltInTemplateFileMap($type);
	}

	/** @return array<string, string> */
	private function getBuiltInTemplateFileMap(string $type):array {
		$templateDirectory = dirname(__DIR__) . "/Template/$type";
		$templateFiles = glob($templateDirectory . "/template.*");

		if(!$templateFiles) {
			$this->writeLine("No built-in templates found for type '$type'.", Stream::ERROR);
			exit(1); // phpcs:ignore
		}

		$fileMap = [];
		foreach($templateFiles as $templateFile) {
			$fileMap[$templateFile] = basename($templateFile);
		}

		return $fileMap;
	}

	/** @return array<string, string> */
	private function getProjectTemplateFileMap(string $type, string $template):array {
		$templateDirectory = getcwd() . "/$type/_template";
		$templateFiles = glob($templateDirectory . "/" . $template . ".*");

		if(!$templateFiles) {
			$this->writeLine(
				"Template '$template' was not found in $templateDirectory",
				Stream::ERROR
			);
			exit(1); // phpcs:ignore
		}

		$fileMap = [];
		foreach($templateFiles as $templateFile) {
			$fileMap[$templateFile] = basename($templateFile);
		}

		return $fileMap;
	}

	/** @param array<string, string> $destinationFileMap */
	private function assertDestinationFilesDoNotExist(array $destinationFileMap):void {
		foreach($destinationFileMap as $destinationPath) {
			if(file_exists($destinationPath)) {
				$this->writeLine("Destination already exists: $destinationPath", Stream::ERROR);
				exit(1); // phpcs:ignore
			}
		}
	}

	private function ensureDestinationDirectoryExists(string $type):void {
		$destinationDirectory = getcwd() . DIRECTORY_SEPARATOR . $type;
		if(!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0777, true)) {
			$this->writeLine("Unable to create directory: $destinationDirectory", Stream::ERROR);
			// TODO: Replace with proper exit code when upgrade to phpgt/cli v1.3.5
			exit(1); // phpcs:ignore
		}
	}

	private function copyTemplateFile(
		string $sourcePath,
		string $destinationPath,
		string $name,
	):void {
		$contents = file_get_contents($sourcePath);
		if($contents === false) {
			throw new RuntimeException("Unable to read template file: $sourcePath");
		}

		$contents = str_replace("{{name}}", $name, $contents);
		if(file_put_contents($destinationPath, $contents) === false) {
			throw new RuntimeException("Unable to write file: $destinationPath");
		}

		$this->writeLine("Created $destinationPath");
	}

	/**
	 * @param array<string, string> $templateFileMap
	 * @return array<string, string>
	 */
	private function getDestinationFileMap(
		string $type,
		string $name,
		array $templateFileMap
	):array {
		$destinationFileMap = [];

		foreach($templateFileMap as $sourcePath => $sourceFileName) {
			$extension = pathinfo($sourceFileName, PATHINFO_EXTENSION);
			$destinationFileMap[$sourcePath] = getcwd()
				. DIRECTORY_SEPARATOR
				. $type
				. DIRECTORY_SEPARATOR
				. $name
				. "."
				. $extension;
		}

		return $destinationFileMap;
	}
}
