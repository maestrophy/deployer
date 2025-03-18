<?php

include_once 'Models/Project.php';

class ProjectService {

	private $storagePath = "Storage";
	private $projectStorageFileName = "projects.json";

	public function getProject(string $name): Project | null
	{
		$projectsData = $this->getAllProjects();
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
		if (!self::validateProjectData($projectData)) {
			//
		}
	}

	public function getRawAllProjects(): string {
		$storageFile = $this->getProjectsStoragePath();
		if (!is_file($storageFile)) {
			return '[]';
		}
		$rawProjectsData = file_get_contents($storageFile);
		return $rawProjectsData;
	}

	public function getAllProjects(): array {
		$rawProjectsData = $this->getRawAllProjects();
		$projectsData = json_decode($rawProjectsData);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $projectsData;
		} else {
			trigger_error($this->$this->getProjectsStoragePath() . ' file is not valid json!', E_USER_WARNING);
			return [];
		}
	}

	public static function validateProjectData(array &$projectData): bool
	{
		if (empty($projectData['projectPath'])) {
			return false;
		}
		exec("git -C " . escapeshellarg($projectData['projectPath']) . " rev-parse --is-inside-work-tree 2>/dev/null", $output, $isGitRepo);
		PathUtil::getCleanFullPathWithTrailingSlash($projectData['projectPath']);
		if (empty($projectData['projectName']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\-_\.]+$/', $projectData['projectName'])) {
			throw new Exception('Project name is not valid! Please use only letters, numbers and \'-\', \'_\', \'.\' characters');
		}
		if (!is_dir($projectData['projectPath'])) {
			throw new Exception('The given path is not valid, it does not exist on the server!');
		}
		if (!is_dir($projectData['projectPath'] . '.git') || $isGitRepo === 0) {
			throw new Exception('The given path is not a valid git repository!');
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
}