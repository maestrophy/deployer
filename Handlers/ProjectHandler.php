<?php

class ProjectHandler {

	private Logger $logger;

	function __construct()
	{
			$this->logger = new Logger('', __class__);
	}

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
	{}

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
	{}

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
		$this->logger->info('Logger works');
		$this->logger->info('View vars', [
			'projectNameRegex' => ProjectService::getProjectNameRegex(true),
			'projectPathRegex' => ProjectService::getProjectPathRegex(true)
		]);
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
		} catch (Throwable $e) {
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