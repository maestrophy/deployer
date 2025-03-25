<?php

include_once 'Models/Project.php';

class ProjectService {

	private $storagePath = "Storage";
	private $projectStorageFileName = "projects.json";

	public function getProject(string $name): Project | null
	{
		$projectsData = $this->getAllProjectsAsArray();
		$result = array_filter($projectsData, fn ($item) => !empty($item['projectName']) && $item['projectName'] === $name);
		if (count($result) > 0) {
			return new Project($result);
		}
		return null;
	}

	private function getProjectsStoragePath()
	{
		return PathUtil::getCleanPathWithTrailingSlash($this->storagePath) . $this->projectStorageFileName;
	}

	public function createNewProject(array $projectData)
	{
		self::validateProjectData($projectData);
		$this->saveNewProject($projectData);
	}

	public function getRawAllProjects(): string {
		$storageFile = $this->getProjectsStoragePath();
		if (!is_file($storageFile)) {
			return '[]';
		}
		$rawProjectsData = file_get_contents($storageFile);
		return $rawProjectsData;
	}

	public function getAllProjectsAsArray(): array
	{
		$rawProjectsData = $this->getRawAllProjects();
		$projectsData = json_decode($rawProjectsData);
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
			throw new Exception('Project is existing for that path: ' . array_values($duplicates)[0]['projectPath']);
		}
		$duplicates = array_filter(
			$projects,
			fn ($project) => $project['projectName'] === $projectData['projectName']
		);
		if (count($duplicates) > 0) {
			throw new Exception('A project is already existing with this name!');
		}
		array_push($projects, $projectData);
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
		if (empty($projectData['projectPath'])) {
			throw new Exception('Project path is not defined!', 100002);
		}
		PathUtil::getCleanFullPathWithTrailingSlash($projectData['projectPath']);
		exec("git -C " . escapeshellarg($projectData['projectPath']) . " rev-parse --is-inside-work-tree 2>/dev/null", $output, $isGitRepo);
		if (empty($projectData['projectName']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\-_\.]+$/', $projectData['projectName'])) {
			throw new Exception('Project name is not valid! Please use only letters, numbers and \'-\', \'_\', \'.\' characters', 100001);
		}
		if (!is_dir($projectData['projectPath'])) {
			throw new Exception('The given path is not valid, it does not exist on the server!', 100002);
		}
		if (!is_dir($projectData['projectPath'] . '.git') || $isGitRepo === 0) {
			throw new Exception('The given path is not a valid git repository!', 100002);
		}
		if (!isset($projectData['scripts'])) {
			$projectData['scripts'] = [];
		} else if (is_string($projectData['scripts'])) {
			$projectData['scripts'] = [$projectData['scripts']];
		} else if (!is_array($projectData['scripts'])) {
			throw new Exception('Scripts must be an array or string, ' . gettype($projectData['scripts']) . ' given!');
		}
		return true;
	}

	public static function validateGitRepository(string $path): bool
	{
		if (empty($path)) {
			return false;
		}
		PathUtil::getCleanFullPathWithTrailingSlash($path);
		if (!is_dir($path)) {
			return false;
		}
		if (!is_dir($path . '.git')) {
			return false;
		}
		exec("git -C " . escapeshellarg($path) . " rev-parse --is-inside-work-tree 2>/dev/null", $output, $exitCode);
		return ($exitCode === 0);
	}
}