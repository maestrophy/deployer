<?php

namespace Services;

use Models\Project;
use Services\Logger;
use Utils\PathUtil;

class ProjectService {

	private $storagePath = "Storage";
	private $projectStorageFileName = "projects.json";
	private static $projectNameRegex = '[a-zA-Z][a-zA-Z0-9\.\-_\s\/]{2,}';
	private static $projectPathRegex = '\/[a-zA-Z\-_\s\.]+((\/[a-zA-Z\-_\s\.]+)+)?\/?';
	private static Logger $logger;

	public static function initLogger()
	{
		self::$logger = new Logger('', __class__);
	}

	public function getProject(string $name): Project | null
	{
		$projectsData = $this->getAllProjectsAsArray();
		$result = array_filter($projectsData, fn ($item) => !empty($item['projectName']) && $item['projectName'] === $name);
		if (count($result) > 0) {
			return new Project($result[0]);
		}
		return null;
	}

	private function getProjectsStoragePath(): string
	{
		$path = PathUtil::makeFullPathFromRelative($this->storagePath);
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}
		return $path . $this->projectStorageFileName;
	}

	public function createNewProject(array $projectData)
	{
		self::validateProjectData($projectData);
		$this->saveNewProject($projectData);
	}

	public function getRawAllProjects(): string {
		$storageFile = $this->getProjectsStoragePath();
		self::$logger->info('Storage file path', $storageFile);
		if (!is_file($storageFile)) {
			return '[]';
		}
		$rawProjectsData = file_get_contents($storageFile);
		return $rawProjectsData;
	}

	public function getAllProjectsAsArray(): array
	{
		$rawProjectsData = $this->getRawAllProjects();
		$projectsData = json_decode($rawProjectsData, true);
		if (json_last_error() == JSON_ERROR_NONE) {
			return $projectsData;
		} else {
			trigger_error($this->getProjectsStoragePath() . ' file is not valid json!', E_USER_WARNING);
			return [];
		}
	}

	private function saveNewProject(array $projectData): void
	{
		$projects = $this->getAllProjectsAsArray();
		$duplicates = array_filter(
			$projects,
			fn ($project) => $project['projectPath'] === $projectData['projectPath']
		);
		if (count($duplicates) > 0) {
			throw new \Exception('Project is existing for that path: ' . array_values($duplicates)[0]['projectPath']);
		}
		$duplicates = array_filter(
			$projects,
			fn ($project) => $project['projectName'] === $projectData['projectName']
		);
		if (count($duplicates) > 0) {
			throw new \Exception('A project is already existing with this name!');
		}
		array_push($projects, $projectData);
		file_put_contents($this->getProjectsStoragePath(), json_encode($projects));
	}

	public function saveModifiedData(array $projects): void
	{
		file_put_contents($this->getProjectsStoragePath(), json_encode($projects));
	}

	/**
	 * Undocumented function
	 *
	 * @return Project[]
	 */
	public function getAllProjects(): array
	{
		return array_map(fn ($projectData) => new Project($projectData), $this->getAllProjectsAsArray());
	}

	public static function validateProjectData(array &$projectData): bool
	{
		self::$logger->info('Project data', $projectData);
		if (empty($projectData['projectPath'])) {
			throw new \Exception('Project path is not defined!', 100002);
		}

		$projectData['projectPath'] = PathUtil::makeFullPathFromRelative($projectData['projectPath']);
		self::$logger->info('Project path', $projectData['projectPath']);
		exec("git -C " . escapeshellarg($projectData['projectPath']) . " rev-parse --is-inside-work-tree 2>/dev/null", $output, $isGitRepo);
		self::$logger->info('Project path', $projectData['projectPath']);

		if (
			empty($projectData['projectName']) ||
			!preg_match(static::getProjectNameRegex(), $projectData['projectName'])) {
			self::$logger->warning('Tried project name', $projectData['projectName']);
			throw new \Exception('Project name is not valid! Please use only letters, numbers and \'-\', \'_\', \'.\' characters', 100001);
		}
		self::$logger->info('Project path', $projectData['projectPath']);

		if (!is_dir($projectData['projectPath'])) {
			throw new \Exception('The given path is not valid, it does not exist on the server!', 100002);
		}
		self::$logger->info('Project path', $projectData['projectPath']);
		if (!is_dir($projectData['projectPath'] . '.git') || $isGitRepo === 0) {
			self::$logger->warning('The given path is not containing any .git directory!', $projectData['projectPath']);
			throw new \Exception('The given path is not a valid git repository!', 100002);
		}
		if (!isset($projectData['scripts'])) {
			$projectData['scripts'] = [];
		} else if (!is_array($projectData['scripts'])) {
			throw new \Exception('Scripts must be an array or string, ' . gettype($projectData['scripts']) . ' given!');
		} else if (
			count(
				array_filter(
					$projectData['scripts'],
					fn ($script) => !self::validateScript($script)
				)
			) > 0
		) {
			throw new \Exception('Scripts must be an array with a strict pattern! ' . var_export($projectData['scripts'], true));
		}
		return true;
	}

	public static function validateScripts(array $scripts): bool
	{
		return !count(
			array_filter(
				$scripts,
				fn ($script) => !self::validateScript($script)
			)
		);
	}

	public static function validateScript(array $script): bool
	{
		return (
			is_array($script) &&
			!empty($script['command']) &&
			is_string($script['command']) &&
			!empty($script['schedule']) &&
			is_string($script['schedule']) &&
			in_array($script['schedule'], ['beforePull', 'afterPull']) &&
			!empty($script['targetPath']) &&
			is_string($script['targetPath']) &&
			PathUtil::validatePath($script['targetPath'])
		);
	}

	public static function validateGitRepository(string $path): bool
	{
		if (empty($path)) {
			return false;
		}
		$path = PathUtil::makeFullPathFromRelative($path, false);
		if (!is_dir($path)) {
			return false;
		}
		if (!is_dir($path . '/.git')) {
			return false;
		}
		try {
			CommandService::runCommandAsUser("git -C " . escapeshellarg($path) . " rev-parse --is-inside-work-tree 2>/dev/null");
			$exitCode = 0;
		} catch(\Exception $e) {
			$exitCode = 1;
			$e->getMessage();
		}
		return ($exitCode === 0);
	}

	public static function getProjectNameRegex(bool $htmlMode = false): string
	{
		if ($htmlMode) {
			return str_replace('\\.', '.', static::$projectNameRegex);
		} else {
			return '/^' . static::$projectNameRegex . '$/';
		}
	}

	public static function getProjectPathRegex(bool $htmlMode = false): string
	{
		if ($htmlMode) {
			return str_replace('\\.', '.', static::$projectPathRegex);
		} else {
			return '/^' . static::$projectPathRegex . '$/';
		}
	}
}