<?php

class ProjectHandler {

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
		$viewBuilder->pickComponent('createProject');
		$viewBuilder->setTitle('Creating Project');
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