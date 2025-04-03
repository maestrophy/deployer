<?php

namespace App;

class Router {

	private Container $di;

	private array $routes;
	private string $defaultUrlParameterPattern = '[a-zA-Z\-\+%_]+';
	const HTTP_METHODS = [
		'GET',
		'PUT',
		'PATCH',
		'POST',
		'DELETE',
		'HEAD',
		'OPTIONS'
	];
	private string $actionName;
	private string $handlerClassName;
	private string $defaultHandler;
	private string $defaultAction;
	private array $urlParams;

	function __construct($routes, Container $di)
	{
		$this->di = $di;
		if (is_string($routes)) {
			if (is_file($_SERVER['DOCUMENT_ROOT'] . '/' . $routes)) {
				$this->processRouteFile($_SERVER['DOCUMENT_ROOT'] . '/' . $routes);
			}
			if (is_file($routes)) {
				$this->processRouteFile($routes);
			}
			$possibleRoutes = json_decode($routes);
			if (json_last_error() === JSON_ERROR_NONE) {
				$this->validateAndSetRoutes($possibleRoutes);
			}
		}
		if (is_array($routes)) {
			$this->validateAndSetRoutes($routes);
		}
	}

	private function processRouteFile(string $path)
	{
		$pathParts = explode('.', $path);
		$ext = end($pathParts);
		if (!$ext) {
			throw new \Exception('Routes file has no extension!');
		}
		if ($ext === 'json') {
			$routes = json_decode(file_get_contents($path));
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new \Error('Invalid json format in routes file!');
			}
			$this->validateAndSetRoutes($routes);
			return;
		}
		if ($ext === 'php') {
			$globals = array_keys($GLOBALS);
			$routes = include $path;
			if ($routes === false) {
				throw new \Exception('Routes file (' . $path . ') can not be included!');
			} else if (is_array($routes)) {
				$this->validateAndSetRoutes($routes);
				return;
			} else {
				$diff = array_values(
					array_diff(
						array_keys($GLOBALS),
						$globals
					)
				);
				if (count($diff) === 2) {
					$this->validateAndSetRoutes($GLOBALS[$diff[1]]);
					return;
				}
			}
		}
		throw new \Exception('Routes are not processable! ' . var_export($routes, true));
	}

	private function validateAndSetRoutes(array $routes)
	{
		if (!is_array($routes)) {
			throw new \Exception('Routes are not array!');
		}
		$this->routes = [];
		foreach ($routes as $key => $route) {
			if ($this->validateRoute($key, $route)) {
				$this->routes[] = $route;
			}
		}
	}

	private function validateRoute(int $key, array $route): bool
	{
		if (!is_array($route)) {
			trigger_error('Route #' . $key . ' is not an array, can not be parsed!', E_USER_WARNING);
			return false;
		}
		if (!isset($route['uri'])) {
			trigger_error('Route #' . $key . ' has no uri member, can not be parsed!', E_USER_WARNING);
			return false;
		}
		if (!isset($route['handler'])) {
			trigger_error('Route #' . $key . ' has no handler member, can not be parsed!', E_USER_WARNING);
			return false;
		}
		if (preg_match_all('/\{([^\}]+)\}/', $route['uri'], $matches)) {
			if (empty($route['patterns']) || !is_array($route['patterns'])) {
				trigger_error('Route #' . $key . ' there is no any pattern defined, default pattern (' . $this->defaultUrlParameterPattern . ') will be used for all the url parameters.', E_USER_NOTICE);
			} else {
				foreach ($matches[1] as $varName) {
					if (empty($route['patterns'][$varName])) {
						trigger_error('Route #' . $key . ' the url parameter \'' . $varName . '\' has no pattern defined, default pattern (' . $this->defaultUrlParameterPattern . ') will be used for it.', E_USER_NOTICE);
					}
				}
			}
		}
		if (!empty($route['patterns']['methods'])) {
			if (
				is_string($route['patterns']['methods']) &&
				!in_array(
					$route['patterns']['methods'],
					self::HTTP_METHODS
				)
			) {
				trigger_error('Route #' . $key . ' has method \'' . $route['patterns']['methods'] . '\', it is not an acceptable http method, no any requests will be allowed for this route!', E_USER_WARNING);
				return false;
			}
			if (is_array($route['patterns']['methods'])) {
				$oddOnesOut = array_values(
					array_filter(
						$route['patterns']['methods'],
						fn ($method) => !is_string($method) || !in_array($method, self::HTTP_METHODS)
					)
				);
				if (count($oddOnesOut) === count($route['patterns']['methods'])) {
					trigger_error('Route #' . $key . ' has only methods, that are not acceptable http methods, no any requests will be allowed for this route!', E_USER_WARNING);
					return false;
				}
				if (count($oddOnesOut) > 0) {
					trigger_error('Route #' . $key . ' has some methods, that are not acceptable http methods, these methods will not be handled!', E_USER_NOTICE);
					$route['patterns']['methods'] = array_values(
						array_filter(
							$route['patterns']['methods'],
							fn ($method) => is_string($method) && in_array($method, self::HTTP_METHODS)
						)
					);
				}
			}
			if (
				!is_array($route['patterns']['methods']) &&
				!is_string($route['patterns']['methods'])
			) {
				trigger_error('Route #' . $key . ' methods can be only array, or string!', E_USER_WARNING);
				return false;
			}
		}
		return true;
	}

	public function initialize()
	{
		if (!isset($this->routes)) {
			throw new \Exception('Routes were not defined yet!');
		}
		$target = $this->formatUri();
		foreach ($this->routes as $options) {
			$endpoint = $options['uri'];
			preg_match_all('/\{([a-zA-Z0-9\-\_]+)\}/', $endpoint, $wildCards);
			$this->urlParams = [];
			if (!empty($wildCards[1])) {
				$varNames = $wildCards[1];
			} else {
				$varNames = [];
			}
			foreach ($varNames as $varName) {
				$pattern =
					empty($options['patterns']) ||
					empty($options['patterns'][$varName]) ?
					$this->defaultUrlParameterPattern :
					$options['patterns'][$varName];
				$endpoint = str_replace('{' . $varName . '}', '(' . $pattern . ')', $endpoint);
			}
			$endpoint = str_replace('/', '\/', $endpoint);
			if (
				preg_match('/^' . $endpoint . '$/', $target, $matches) &&
				(
					empty($options['methods']) ||
					(
						is_string($options['methods']) &&
						$_SERVER['REQUEST_METHOD'] === $options['methods']
					) ||
					(
						is_array($options['methods']) &&
						in_array($_SERVER['REQUEST_METHOD'], $options['methods'])
					)
				)
			) {
				for ($x = 1; $x < count($matches); $x++) {
					$this->urlParams[$varNames[$x - 1]] = urldecode($matches[$x]);
				}
				$this->actionName =
					empty($options['action']) ?
					(
						empty($this->defaultAction) ?
							'index' :
							$this->defaultAction
					) :
					$options['action'];
				$this->handlerClassName = $options['handler'] . 'Handler';
				break;
			}
		}
		if (empty($this->actionName)) {
			$this->actionName = empty($this->defaultAction) ? 'index' : $this->defaultAction;
		}
		if (empty($this->handlerClassName)) {
			$this->handlerClassName = (empty($this->defaultHandler) ? 'NotFound' : $this->defaultHandler) . 'Handler';
		}
	}

	private function formatUri(): string
	{
		$url = $_SERVER['REQUEST_URI'];
		$scheme = $_SERVER['REQUEST_SCHEME'] . '://';
		if (substr($url, 0, strlen($scheme)) === $scheme) {
			$target = substr(
				$url,
				strpos(
					$url,
					'/',
					strlen($scheme)
				) + 1
			);
		} else {
			$target = substr(
				$url,
				strpos(
					$url,
					'/'
				) + 1
			);
		}
		return explode('?', $target)[0];
	}

	public function setDefaultHandler(string $handler)
	{
		$this->defaultHandler = $handler;
	}

	public function getAction(): string
	{
		if (empty($this->actionName)) {
			throw new \Exception('Router is not initialized yet!');
		}
		return $this->actionName;
	}

	public function getHandlerClassName(): string
	{
		if (empty($this->handlerClassName)) {
			throw new \Exception('Router is not initialized yet!');
		}
		return $this->handlerClassName;
	}

	public function getUrlParams(): array
	{
		return empty($this->urlParams) ? [] : $this->urlParams;
	}
}