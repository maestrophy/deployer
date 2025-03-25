<?php

class OutputUtil {

	public static function onProgressOutput(array $message)
	{
		if (!headers_sent()) {
			header('Content-Type: application/json');
			header('Connection: close');
		}
		if (ob_get_level() == 0) {
			ob_start();
		}
		$message['duringProgress'] = true;
		echo json_encode($message);
		ob_flush();
		flush();
		ob_get_clean();
	}
}