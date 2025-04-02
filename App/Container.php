<?php

namespace App;

class Container {

	private array $services = [];

	public function set(string $key, $service)
	{
		if (empty($key)) {
			throw new \Exception('You can not set any service in DI with an empty key!');
		}
		$this->services[$key] = $service;
	}

	public function get(string $key)
	{
		return empty($this->services[$key]) ? null : $this->services[$key];
	}
}