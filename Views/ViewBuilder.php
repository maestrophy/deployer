<?php

namespace Views;

use Services\ProjectService;
use Utils\PathUtil;

class ViewBuilder {

	private array $views = [];
	private array $viewVars = [];
	private array $jsVars = [];
	private array $styles = [];
	private array $scripts = [];
	private string $title = 'Deployer';
	private string $layout;
	private array $layoutVars;

	function __construct(string $layout = 'Views/layout.phtml')
	{
		if (empty($layout) || (file_exists($layout) && $layout !== 'Views/layout.phtml')) {
			$this->layout = $layout;
			$this->layoutVars = [];
			echo "Ide futunk be";
		} else {
			$projectService = new ProjectService();
			$this->layout = 'Views/layout.phtml';
			$this->layoutVars = ['projects' => ($projectService->getAllProjects())];
		}
	}
	public function setLayout(string $layout)
	{
		if (empty($layout) || file_exists($layout)) {
			$this->layout = $layout;
			$this->layoutVars = [];
		}
	}

	public function setLayoutVar(int|string|null $varName, $value, $override = false): bool
	{
		if ($varName === null) {
			array_push($this->layoutVars, $value);
		} else if (!isset($this->layoutVars[$varName]) || $override) {
			$this->layoutVars[$varName] = $value;
		} else {
			trigger_error($varName . ' variable already existing in layout variables!', E_USER_WARNING);
			return false;
		}
		return true;
	}

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

	public function addJSVars(array $vars)
	{
		$acceptableKeys = array_filter(
			array_keys($vars),
			fn ($key) =>
				is_string($key) && preg_match('/^[a-zA-Z_][a-zA-Z_0-9]+$/', $key)
		);
		foreach ($acceptableKeys as $key) {
			$this->jsVars[$key] = $vars[$key];
		}
	}

	public function setViewVar(string $viewName, string $varName, $value)
	{
		if (preg_match('/^[a-zA-Z_][a-zA-Z_0-9]+$/', $varName)) {
			if (!isset($this->viewVars[$viewName])) {
				$this->viewVars[$viewName] = [];
			}
			$this->viewVars[$viewName][$varName] = $value;
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
			$this->styles[$viewName] = 'Views/Styles/' . $extraPath . $viewName . '.css';
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
		$jsVars = $this->jsVars;
		$vars = $this->getViewVarsForRender();
		$addJsVars = function ($vars) {
			if (!empty($vars)) {
				echo "<script>";
				foreach ($vars as $varName => $value) {
					switch (gettype($value)) {
						case 'string':
							echo "var " . $varName . " = '" . $value . "';";
							break;
						case 'integer':
						case 'double':
							echo "var " . $varName . " = " . $value . ";";
							break;
						case 'boolean':
							echo "var " . $varName . " = " . ($value ? 'true' : 'false') . ";";
							break;
						case 'object':
						case 'array':
							echo "var " . $varName . " = " . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ";";
							break;
						case 'NULL':
							echo "var " . $varName . " = null;";
							break;
					}
				}
				echo "</script>";
			}
		};
		if (!empty($this->layout)) {
			foreach ($this->layoutVars as $varName => $value) {
				$$varName = $value;
			}
			include $this->layout;
		} else {
			foreach ($viewsToRender as $view) {
				foreach ($vars[$view] as $varName => $value) {
					$$varName = $value;
				}
				include $view;
			}
		}
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
	public function addScriptFile(string $scriptName, string $extraPath = '')
	{
		if (file_exists('Views/Scripts/' . $extraPath . $scriptName . '.js')) {
			$this->scripts[$scriptName] = 'Views/Scripts/' . $extraPath . $scriptName . '.js';
		}
	}
}