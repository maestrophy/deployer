<?php

namespace Services;
class CommandService {

	private static Logger $logger;

	public static function runCommandsAsUser(array $commands): bool
	{
		$userConfig = include 'config.php';
		foreach ($commands as $command) {
			exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . $command . "'", $output, $exitCode);
			if ($exitCode !== 0) {
				throw new \Exception(join("\n", $output));
			}
		}
		return true;
	}

	public static function runCommandAsUser(string $command): array
	{
		$userConfig = include 'config.php';
		exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . $command . "'", $output, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception(join("\n", $output));
		}
		return $output;
	}

	public static function runCommandsAsUserInFolder(array $commands, string $path): bool
	{
		$userConfig = include 'config.php';
		foreach ($commands as $command) {
			exec("echo '" . $userConfig['password'] . "' | su - maestro -c 'cd " . $path . " && " . $command . "'", $output, $exitCode);
			if ($exitCode !== 0) {
				throw new \Exception(var_export($output));
			}
		}
		return true;
	}

	public static function runCommandAsUserInFolder(string $command, string $path): array
	{
		if (empty(self::$logger)) {
			self::$logger = new Logger('', __CLASS__);
		}
		$userConfig = include 'config.php';
		$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c 'cd " . $path . " && " . $command . "'", $output, $exitCode);
		self::$logger->info('Output', $output);
		self::$logger->info('Result', $result);
		if ($exitCode !== 0) {
			throw new \Exception(join("\n", $output));
		}
		return $output;
	}
}