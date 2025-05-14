<?php

namespace Handlers;

use App\Container;
use App\Response;
use Services\Logger;
use Services\ProjectService;
use Views\ViewBuilder;

class ProjectHandler extends AbstractHandler {

	private Logger $logger;

	function __construct(Container $di)
	{
		$this->di = $di;
		$this->logger = new Logger('', __class__);
		parent::__construct($di);
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
		$viewBuilder->pickComponent($viewName);
		$viewBuilder->addScriptFile('deploy');
		$viewBuilder->addScriptFile('scripts');
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
		$viewBuilder->addJSVars(
			[
				'currentlyActiveBranch' => $currentlyActiveBranch,
				'currentProjectPath' => $project->getProjectPath(),
				'currentBuildScripts' => $project->getBuildScripts()
			]
		);
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
	{
		/**
		 * @var ProjectService
		 */
		$projectService = $this->di->get('projectService');
		$allProjects = $projectService->getAllProjectsAsArray();
		$filteredArray = array_filter(
			$allProjects,
			fn ($project) => $project['projectName'] === $projectName
		);
		if (count($filteredArray) > 0) {
			$key = array_keys($filteredArray)[0];
			$scripts = $this->get('Scripts');
			if (!ProjectService::validateScripts($scripts)) {
				throw new \Exception('Invalid scripts data!');
			}
			$allProjects[$key]['scripts'] = $scripts;
			$projectService->saveModifiedData($allProjects);
			return new Response([], 200);
		} else {
			throw new \Exception('Project not found!');
		}
	}

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
		$responseContent = []; 
		$projectName = $this->get('projectName');
		$projectPath = $this->get('projectPath');
		try {
			(new ProjectService())->createNewProject(
				[
					'projectName' => $projectName,
					'projectPath' => $projectPath
				]
			);
			$responseContent['status'] = 'success';
			$responseContent['newProjectName'] = $projectName;
		} catch (\Throwable $e) {
			$responseContent['status'] = 'failed';
			$responseContent['message'] = $e->getMessage();
			if (!empty($e->getCode()) && in_array($e->getCode(), [100001, 100002])) {
				$fieldCodePairs = include 'fieldCodePairs.php';
				$responseContent['field'] = $fieldCodePairs[$e->getCode()];
			}
		}
		$response = new Response($responseContent, 201);
		return $response;
	}

	public function checkPath()
	{
		$projectPath = $_GET['projectPath'];
		$pathValid = ProjectService::validateGitRepository($projectPath);
		$response = new Response(['pathValid' => $pathValid], 202);
		return $response;
	}

	public function checkProjectName()
	{
		$projectName = $_GET['projectName'];
		$project = (new ProjectService())->getProject($projectName);
		$response = new Response(['projectExists' => !empty($project)], 202);
		return $response;
	}
}