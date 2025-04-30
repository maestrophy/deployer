<?php

namespace Services;
class CommandService {

	private static Logger $logger;

	public static function runCommandsAsUser(array $commands): bool
	{
		self::initLogger();
		$userConfig = include 'config.php';
		foreach ($commands as $command) {
			self::$logger->info('Command', $command);
			$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . $command . "'", $output, $exitCode);
			self::$logger->info('Output', $output);
			self::$logger->info('Exit code', $exitCode);
			self::$logger->info('Result', $result);
			if ($exitCode !== 0) {
				throw new \Exception(join("\n", $output));
			}
		}
		return true;
	}

	public static function runCommandAsUser(string $command): array
	{
		self::initLogger();
		$userConfig = include 'config.php';
		self::$logger->info('Command', $command);
		$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c '" . $command . "'", $output, $exitCode);
		self::$logger->info('Output', $output);
		self::$logger->info('Exit code', $exitCode);
		self::$logger->info('Result', $result);
		if ($exitCode !== 0) {
			throw new \Exception(join("\n", $output));
		}
		return $output;
	}

	public static function runCommandsAsUserInFolder(array $commands, string $path): bool
	{
		self::initLogger();
		$userConfig = include 'config.php';
		foreach ($commands as $command) {
			self::$logger->info('Command', $command);
			$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c 'cd " . $path . " && " . $command . "'", $output, $exitCode);
			self::$logger->info('Output', $output);
			self::$logger->info('Exit code', $exitCode);
			self::$logger->info('Result', $result);
			if ($exitCode !== 0) {
				throw new \Exception(var_export($output));
			}
		}
		return true;
	}

	public static function runCommandAsUserInFolder(string $command, string $path): array
	{
		self::initLogger();
		$userConfig = include 'config.php';
		self::$logger->info('Command', $command);
		$result = exec("echo '" . $userConfig['password'] . "' | su - maestro -c 'cd " . $path . " && " . $command . "'", $output, $exitCode);
		self::$logger->info('Output', $output);
		self::$logger->info('Exit code', $exitCode);
		self::$logger->info('Result', $result);
		if ($exitCode !== 0) {
			throw new \Exception(join("\n", $output));
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
}