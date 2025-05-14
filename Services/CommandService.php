<?php

namespace Services;
class CommandService {

	private static Logger $logger;

	public static function runCommandsAsUser(array $commands): bool
	{
		$userConfig = include 'config.php';
		foreach ($commands as $command) {
			$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . self::formatCommand($command) . "'", $output, $exitCode);
			if ($exitCode !== 0) {
				throw new \Exception(var_export($output, true));
			}
		}
		return true;
	}

	public static function runCommandAsUser(string $command): array
	{
		$userConfig = include 'config.php';
		$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . self::formatCommand($command) . "'", $output, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception(var_export($output, true));
		}
		return $output;
	}

	public static function runCommandsAsUserInFolder(array $commands, string $path): bool
	{
		$userConfig = include 'config.php';
		foreach ($commands as $command) {
			$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c 'cd " . $path . " && " . self::formatCommand($command) . "'", $output, $exitCode);
			if ($exitCode !== 0) {
				throw new \Exception(var_export($output, true));
			}
		}
		return true;
	}

	public static function runCommandAsUserInFolder(string $command, string $path): array
	{
		$userConfig = include 'config.php';
		$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c 'cd " . $path . " && " . self::formatCommand($command) . "'", $output, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception(var_export($output, true));
		}
		return $output;
	}

	public static function initLogger(Logger|null $logger = null): void
	{
		if (!empty(self::$logger)) {
			return;
		}
		if (empty($logger)) {
			self::$logger = new Logger('', __CLASS__);
		} else {
			self::$logger = $logger;
		}
	}

	private static function formatCommand(string $command): string
	{
		$result = htmlspecialchars_decode($command);
		if (preg_match('/docker/', $result) && preg_match('/ -it /', $result)) {
			$result = preg_replace('/ -it /', ' -i ', $result);
		}
		return $result;
	}
}