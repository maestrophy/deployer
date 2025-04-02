<?php

use App\Application;

try {
	set_include_path(__DIR__);
	include_once 'App/Bootstrap.php';
	$boot = new App\Bootstrap();
	$di = $boot->getDi();
	(new Application($di))->run();
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