<?php
try {
	set_include_path(__DIR__);
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
	$target = explode('?', $target)[0];
	$endpoints = include_once 'routes.php';
	$handler = 'NotFoundHandler';
	foreach ($endpoints as $options) {
		$endpoint = $options['uri'];
		$patterns = empty($options['patterns']) ? [] : $options['patterns'];
		preg_match_all('/\{([a-zA-Z0-9\-\_%+]+)\}/', $endpoint, $wildCards);
		$vars = [];
		if (!empty($wildCards[1])) {
			$varNames = $wildCards[1];
		}
		foreach ($patterns as $toReplace => $pattern) {
			$endpoint = str_replace('{' . $toReplace . '}', '(' . $pattern . ')', $endpoint);
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
				$vars[$varNames[$x - 1]] = $matches[$x];
			}
			$action = empty($options['action']) ? 'index' : $options['action'];
			$handler = $options['handler'] . 'Handler';
			break;
		}
	}
	empty($action) && $action = 'index';
	include_once 'Views/ViewBuilder.php';
	include_once 'Services/ProjectService.php';
	include_once 'Models/Project.php';
	include_once 'Services/LogService.php';
	include_once 'Handlers/' . $handler . '.php';
	ProjectService::initLogger();
	$_POST = json_decode(file_get_contents('php://input'), true);

	/**
	 * @var AbstractHandler
	 */
	$handlerInstance = new $handler();

	$response = $handlerInstance->$action(...$vars);
	if (!empty($response)) {
		$finalResponse = json_encode($response);
		if (!headers_sent()) {
			header('Content-Type: application/json');
			header('Content-Length: ' . strlen($finalResponse));
		} else {
			usleep(100000);
		}
		echo $finalResponse;
		fastcgi_finish_request();
	}
} catch (Throwable $e) {
	echo $e->getMessage();
	$trace = $e->getTrace();
	foreach ($trace as $unit) {
		if (!empty($unit['file']) && !empty($unit['line'])) {
			echo '<br>';
			echo $unit['file'] . ': ' . $unit['line'];
		}
	}
}