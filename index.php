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

	$endpoints = include_once 'routes.php';
	$handler = 'NotFound';
	foreach ($endpoints as $endpoint => $options) {
		$patterns = empty($options['patterns']) ? [] : $options['patterns'];
		preg_match_all('/\{([a-zA-Z0-9\-\_]+)\}/', $endpoint, $wildCards);
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
			$handler = $options['handler'] . 'Handler';
			$action = empty($options['action']) ? 'index' : $options['action'];
			break;
		}
	}

	include_once 'Handlers/AbstractHandler.php';
	include_once 'Views/ViewBuilder.php';
	include_once 'Handlers/' . $handler . '.php';

	/**
	 * @var AbstractHandler
	 */
	$handlerInstance = new $handler();

	$handlerInstance->$action(...$vars);
} catch (Throwable $e) {
	echo $e->getMessage();
}