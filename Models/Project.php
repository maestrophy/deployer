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
		$branchesOutput = CommandService::runCommandAsUser('git branch');
	}
}