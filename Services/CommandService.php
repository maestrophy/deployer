<?php

class CommandService {

	public static function runCommandsAsUser(array $commands): bool
	{
		$userConfig = include 'config.php';
		foreach ($commands as $command) {
			$output = exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . $command . "'", $output, $exitCode);
			if ($exitCode !== 0) {
				throw new Exception($output);
			}
		}
		return true;
	}

	public static function runCommandAsUser(string $command): string
	{
		$userConfig = include 'config.php';
		$output = exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . $command . "'", $output, $exitCode);
		if ($exitCode !== 0) {
			throw new Exception($output);
		}
		return $output;
	}
}