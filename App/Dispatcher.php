<?php

namespace App;

use Handlers\AbstractHandler;
use App\Response;

class Dispatcher {

	private Container $di;
	private string $handlerClassName;
	private AbstractHandler $handler;
	private string $action;
	private Response $result;
	private array $urlParams;
	private string $handlerPath;

	function __construct(Container $di)
	{
		$this->di = $di;
	}

	public function initialize()
	{
		try {
			/**
			 * @var Router
			 */
			$router = $this->di->get('router');
			$this->handlerClassName = $router->getHandlerClassName();
			$this->action = $router->getAction();
			$handlerClass = $this->handlerPath . '\\' . $this->handlerClassName;
			$this->handler = new $handlerClass($this->di);
			$this->urlParams = $router->getUrlParams();
		} catch (\Exception $e) {
			throw new \Exception("Dispatcher could not get action or handler!\n" . $e->getMessage());
		}
	}

	public function dispatch()
	{
		if (empty($this->handler) || empty($this->action)) {
			throw new \Exception("Dispatcher is not initialized yet!");
		}
		$action = $this->action;
		$result = $this->handler->$action(...$this->urlParams);
		if (
			!empty($result) &&
			gettype($result) === 'object' &&
			get_class($result) === "App\\Response"
		) {
			$this->result = $result;
		}
	}

	public function getResult(): Response | null
	{
		return empty($this->result) ? null : $this->result;
	}

	public function setHandlerPath(string $path)
	{
		$this->handlerPath = $path;
	}
}