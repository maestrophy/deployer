<?php

include_once 'Services/CommandService.php';

class Project {

	private string $name;
	private string $path;
	private array $scripts;
	private array $branches;
	private string $activeBranch;

	function __construct(array $data)
	{
		ProjectService::validateProjectData($data);
		$this->name = $data['projectName'];
		$this->path = $data['projectPath'];
		$this->scripts = $data['scripts'];
		CommandService::runCommandsAsUserInFolder(['git fetch', 'git fetch origin'], $this->path);
		$branchesOutput = CommandService::runCommandAsUserInFolder('git branch -r', $this->path);
		$this->branches = array_map(fn ($branch) => str_replace('origin/', '', $branch), $branchesOutput);
		$activeBranchesOutput = CommandService::runCommandAsUserInFolder('git branch', $this->path);
		foreach ($activeBranchesOutput as $outputLine) {
			if (substr($outputLine, 0, 2) === '* ') {
				$this->activeBranch = substr($outputLine, 2);
			}
		}
	}

	public function checkout(string $branchName)
	{
		CommandService::runCommandsAsUserInFolder(['git fetch', 'git fetch origin'], $this->path);
		if ($branchName === $this->activeBranch) {
			$this->pull();
		}
		CommandService::runCommandAsUserInFolder('git checkout -B branch-name origin/' . $branchName, $this->path);
	}

	public function pull()
	{
		CommandService::runCommandAsUserInFolder('git pull', $this->path);
	}
}