<?php

namespace Handlers;

use Services\Logger;
use Services\ProjectService;
use Views\ViewBuilder;

class ProjectHandler extends AbstractHandler {

	private Logger $logger;

	function __construct()
	{
			$this->logger = new Logger('', __class__);
	}

	public function index()
	{}

	/**
	 * Undocumented function
	 * 
	 * route projects/{projectName}
	 * method GET
	 *
	 * @param string $projectName
	 * @return void
	 */
	public function listBranches(string $projectName)
	{
		/**
		 * @var ProjectService
		 */
		$projectService = $this->di->get('projectService');
		$project = $projectService->getProject($projectName);
		$viewBuilder = new ViewBuilder();
		$viewName = 'projectDetails';
		$viewBuilder->pickView($viewName);
		$viewBuilder->addScriptFile('deploy');
		$viewBuilder->setTitle('Project Details');
		$currentlyActiveBranch = $project->getActiveBranch();
		$viewBuilder->addVars(
			$viewName,
			[
				'project' => $project,
				'branches' => $project->getBranchList(),
				'activeBranch' => $currentlyActiveBranch,
				'buildScripts' => $project->getScriptsSeparated()
			]
		);
		$viewBuilder->addJSVars(['currentlyActiveBranch' => $currentlyActiveBranch]);
		$viewBuilder->render();
	}

	/**
	 * Undocumented function
	 * 
	 * route projects/{projectName}/deploy
	 * method POST
	 *
	 * @param string $projectName
	 * @return void
	 */
	public function deploy(string $projectName)
	{
		/**
		 * @var ProjectService
		 */
		$projectService = $this->di->get('projectService');
		$project = $projectService->getProject($projectName);
		$project->setDi($this->di);
		$project->checkout($this->get('branch'));
	}

	/**
	 * Undocumented function
	 * 
	 * route projects/{projectName}/scripts
	 * method POST
	 *
	 * @param string $projectName
	 * @return void
	 */
	public function changeScript(string $projectName)
	{}

	/**
	 * Undocumented function
	 * 
	 * route new-project
	 * method GET
	 *
	 * @param string $projectName
	 * @return void
	 */
	public function newProject()
	{
		$viewBuilder = new ViewBuilder();
		$viewName = 'createProject';
		$viewBuilder->pickComponent($viewName);
		$viewBuilder->setTitle('Creating Project');
		$viewBuilder->addVars(
			$viewName,
			[
				'projectNameRegex' => ProjectService::getProjectNameRegex(true),
				'projectPathRegex' => ProjectService::getProjectPathRegex(true)
			]
		);
		$viewBuilder->render();
	}

	/**
	 * Undocumented function
	 * 
	 * route new-project
	 * method POST
	 *
	 * @param string $projectName
	 * @return void
	 */
	public function addProject()
	{
		try {
			(new ProjectService())->createNewProject($_POST);
			$response['status'] = 'success';
			$response['newProjectName'] = $_POST['projectName'];
		} catch (\Throwable $e) {
			$response['status'] = 'failed';
			$response['message'] = $e->getMessage();
			if (!empty($e->getCode()) && in_array($e->getCode(), [100001, 100002])) {
				$fieldCodePairs = include 'fieldCodePairs.php';
				$response['field'] = $fieldCodePairs[$e->getCode()];
			}
		}
		return $response;
	}

	public function checkPath()
	{
		$projectPath = $_GET['projectPath'];
		$pathValid = (new ProjectService())->validateGitRepository($projectPath);
		return ['pathValid' => $pathValid];
	}

	public function checkProjectName()
	{
		$projectName = $_GET['projectName'];
		$project = (new ProjectService())->getProject($projectName);
		return ['projectExists' => !empty($project)];
	}
}