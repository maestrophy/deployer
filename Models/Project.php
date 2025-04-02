<?php

namespace Models;

use Services\CommandService;
use Services\ProjectService;

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

	public function getProjectName(): string
	{
		return $this->name;
	}

	public function getUrlEncodedProjectName(): string
	{
		return urlencode($this->getProjectName());
	}

	public function getBranchList(): array
	{
		CommandService::runCommandsAsUserInFolder(['git fetch', 'git fetch origin'], $this->path);
		$branchesOutput = CommandService::runCommandAsUserInFolder('git branch -r', $this->path);
		return array_map(fn ($branch) => str_replace('origin/', '', $branch), $branchesOutput);
	}

	public function getActiveBranch(): string
	{
		$activeBranch = '';
		$activeBranchesOutput = CommandService::runCommandAsUserInFolder('git branch', $this->path);
		foreach ($activeBranchesOutput as $outputLine) {
			if (substr($outputLine, 0, 2) === '* ') {
				$activeBranch = substr($outputLine, 2);
			}
		}
		return $activeBranch;
	}
}