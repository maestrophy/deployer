<?php

namespace Handlers;

use App\Container;
use App\Request;

abstract class AbstractHandler {

	protected Container $di;
	protected Request $request;

	function __construct(Container $di)
	{
		$this->di = $di;
	}

	public abstract function index();

	protected function get(string $key)
	{
		if (empty($this->request)) {
			$this->request = $this->di->get('request');
		}
		return $this->request->get($key);
	}
}