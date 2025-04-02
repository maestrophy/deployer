<?php

namespace App;

class Application {

	private Container $di;

	function __construct(Container $di)
	{
		$this->di = $di;
	}

	public function run()
	{
		/**
		 * @var Router
		 */
		$router = $this->di->get('router');
		$router->initialize();
		/**
		 * @var Dispatcher
		 */
		$dispatcher = $this->di->get('dispatcher');
		$dispatcher->initialize();
		$dispatcher->dispatch();
		$result = $dispatcher->getResult();
		if (!empty($result)) {
			$result->send();
		}
	}
}