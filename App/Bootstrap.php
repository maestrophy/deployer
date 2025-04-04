<?php

namespace App;

use App\Container;
use Services\ProjectService;
use App\Request;
use App\Response;

class Bootstrap {

	private Container $di;

	public function __construct()
	{
		$this->setAutoLoader();
		$this->registerServices();
	}

	private function setAutoLoader()
	{
		spl_autoload_register(function ($class) {
			// Convert namespace to full file path
			$file = $_SERVER['DOCUMENT_ROOT'] . '/' . str_replace('\\', '/', $class) . '.php';
		
			// Check if the file exists before including
			if (file_exists($file)) {
				require_once $file;
			} else {
				throw new \Exception("Class file not found: $file");
			}
		});
	}

	private function registerServices()
	{
		$this->di = new Container();
		$projectService = new ProjectService();
		ProjectService::initLogger();
		$router = new Router('routes.php', $this->di);
		$dispatcher = new Dispatcher($this->di);
		$dispatcher->setHandlerPath('Handlers');
		$request = new Request();
		$response = new Response();
		$this->di->set('projectService', $projectService);
		$this->di->set('router', $router);
		$this->di->set('dispatcher', $dispatcher);
		$this->di->set('request', $request);
		$this->di->set('response', $response);
	}

	public function getDi()
	{
		return $this->di;
	}
}