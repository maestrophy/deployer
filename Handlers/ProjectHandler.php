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
	{}

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
	{}
}