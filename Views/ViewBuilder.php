<?php

class ViewBuilder {

	private array $views = [];
	private array $viewVars = [];
	private array $styles = [];
	private array $scripts = [];
	private string $title = 'Deployer';

	public function pickView(string $viewName, string $extraPath = '')
	{
		if (!empty($extraPath)) {
			PathUtil::getCleanPathWithTrailingSlash($extraPath);
		}
		if (file_exists('Views/' . $extraPath . $viewName . '.phtml')) {
			$this->views[$viewName] = 'Views/' . $extraPath . $viewName . '.phtml';
			$this->viewVars[$viewName] = [];
		}
	}

	public function addVars(string $viewName, array $vars)
	{
		$acceptableKeys = array_filter(
			array_keys($vars),
			fn ($key) =>
				is_string($key) && preg_match('/^[a-zA-Z_][a-zA-Z_0-9]+$/', $key)
		);
		if (!isset($this->viewVars[$viewName])) {
			$this->viewVars[$viewName] = [];
		}
		foreach ($acceptableKeys as $key) {
			$this->viewVars[$viewName][$key] = $vars[$key];
		}
	}

	public function pickComponent(string $viewName, string $extraPath = '')
	{
		if (!empty($extraPath)) {
			PathUtil::getCleanPathWithTrailingSlash($extraPath);
		}
		if (file_exists('Views/' . $extraPath . $viewName . '.phtml')) {
			$this->views[$viewName] = 'Views/' . $extraPath . $viewName . '.phtml';
		}
		if (file_exists('Views/Styles/' . $extraPath . $viewName . '.css')) {
			$this->styles[$viewName] = 'Views/Styles/' . $extraPath . $viewName . '.phtml';
		}
		if (file_exists('Views/Scripts/' . $extraPath . $viewName . '.js')) {
			$this->scripts[$viewName] = 'Views/Scripts/' . $extraPath . $viewName . '.js';
		}
	}

	public function setTitle(string $title)
	{
		$this->title = $title;
	}
	
	public function render()
	{
		$viewsToRender = $this->views;
		$styles = $this->styles;
		$scripts = $this->scripts;
		$title = $this->title;
		$vars = $this->getViewVarsForRender();
		include 'layout.phtml';
	}

	private function getViewVarsForRender(): array
	{
		$vars = [];
		foreach ($this->views as $viewName => $path) {
			if (isset($this->viewVars[$viewName])) {
				$vars[$viewName] = $this->viewVars[$viewName];
			} else {
				$vars[$viewName] = [];
			}
		}
		return $vars;
	}
}