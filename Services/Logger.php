<?php

namespace Services;

use Utils\PathUtil;

class Logger {

	private static $logRoot = 'Logs';
	private static $valueSeparatingMessage = 'Logged value has large size, so it is stored in a separated file:';
	private static $invalidFileCharacters = '\.\?\!\(\)\[\]';
	private static $separatingNewLineCount = 3;

	private int $separateLoggingLengthLimit = 200;
	private int $separateLoggingLengthHardMinLimit = 50;
	private int $separateLoggingLengthHardMaxLimit = 5000;

	private string $logSubject;
	private string $logPath;
	private string $logFileName;

	private bool $typeLogging = true;

	private int $logLevel = 0;

	private $dateDependingLogLevels = [];

	private $logLevelMapping = [
		'Info' => 10,
		'Notice' => 20,
		'Warning' => 30,
		'Error' => 80,
		'Fatal' => 100
	];
	
	function __construct(
		string $logPath,
		string $fileName,
		string $logSubject = 'App'
	)
	{
		if (strpos($fileName, '\\') !== false && class_exists($fileName)) {
			$classNameParts = explode('\\', $fileName);
			$fileName = end($classNameParts);
		}
		$this->setLogSubject($logSubject);
		$this->setLogPath($logPath);
		$this->setLogFileName($fileName);
	}

	private static function addLog(
		string $path,
		string $fileName,
		string $level,
		int $separateLoggingLengthLimit,
		bool $logType,
		string $logSubject,
		string $description,
		$value = 'Xz9-KTq8Yb#dN7Wg_!'
	) {
		$path = self::$logRoot . '/' . $path;
		$path = PathUtil::makeFullPathFromRelative($path);
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}
		$logFileName = $fileName . '_' . date('Y-m-d') . '.log';
		if (!self::indicateNotDefined($value)) {
			$valueType = gettype($value);
			switch ($valueType) {
				case 'object':
					$value = var_export($value);
					break;
				case 'array':
					$value = json_encode($value);
					break;
			}
		}
		$separateFileName = '';
		if (strlen($value) > $separateLoggingLengthLimit) {
			for (
				$x = 1000001;
				true;
				$x++
			) {
				$separateFileName = $x . '.log';
				$separateFilePath = $path . '/' . $separateFileName;
				if (!file_exists($separateFilePath)) {
					file_put_contents($separateFilePath, $value);
					unset($value);
					break;
				}
				if ($x > 2000000) {
					throw new \Exception('Infinite loop in loging, or too many sideEffect files!');
					break;
				}
			}
		}

		$fullFilePath = $path . '/' . $logFileName;
		$preContent = !file_exists($fullFilePath) ? "\xEF\xBB\xBF" : "";
		$stream = fopen($fullFilePath, 'a+b');
		if (!empty($preContent)) {
			fwrite($stream, $preContent);
		}
		fwrite(
			$stream,
			'[' . date('Y-m-d H:i:s') . '] ' .
			'[' . $level . '] ' .
			'[' . $logSubject . '] ' .
			$description .
			(
				empty($separateFileName) ?
					(self::indicateNotDefined($value) ? '' : ':') .
					(
						self::indicateNotDefined($value) ? '' : (
							"\nValue:" .
							($logType ? "\n" . $valueType : '') .
							(empty($value) ? '' : "\n" . $value)
						)
					) :
				self::$valueSeparatingMessage . ' ' . $separateFileName
			) .
			str_repeat("\n", self::$separatingNewLineCount)
		);
	}

	private static function indicateNotDefined($value)
	{
		return (is_string($value) && $value === 'Xz9-KTq8Yb#dN7Wg_!');
	}

	private function getDateDependentLogLevel(): int
	{
		$logLevel = $this->logLevel;
		$greatestPassedDate = '';
		$now = date('Y-m-d H:i:s');
		foreach ($this->dateDependingLogLevels as $date => $level) {
			if (
				$date < $now &&
				(
					empty($greatestPassedDate) ||
					$date > $greatestPassedDate
				)
			) {
				$greatestPassedDate = $date;
				$logLevel = $level;
			}
		}
		return $logLevel;
	}


	private function isLogging($messageLevel): bool
	{
		if (!is_numeric($messageLevel) && !array_key_exists($messageLevel, $this->logLevelMapping)) {
			throw new \Exception('Log level for "' . $messageLevel . '" is not recognizable!');
		}
		if (array_key_exists($messageLevel, $this->logLevelMapping)) {
			$messageLevel = $this->logLevelMapping[$messageLevel];
		}
		return !($this->getDateDependentLogLevel() > $messageLevel);
	}

	public function setMaxLoggableLengthLimit(int $limit): void
	{
		if ($limit < $this->separateLoggingLengthHardMinLimit) {
			$this->separateLoggingLengthLimit = $this->separateLoggingLengthHardMinLimit;
			return;
		}
		if ($limit > $this->separateLoggingLengthHardMaxLimit) {
			$this->separateLoggingLengthLimit = $this->separateLoggingLengthHardMaxLimit;
			return;
		}
		$this->separateLoggingLengthLimit = $limit;
	}

	public function setLogSubject(string $logSubject)
	{
		if (!empty($logSubject)) {
			$this->logSubject = $logSubject;
		} else {
			throw new \Error('Tried to set logsubject to an empty value!');
		}
	}

	public function setLogPath(string $logPath)
	{
		if (empty($logPath)) {
			$this->logPath = '';
			return;
		}
		$this->logPath = ltrim(
			preg_replace(
				'/^(\.\.|\.\/)/',
				'',
				str_replace(
					'/../',
					'/',
					$logPath
				)
			),
			'/'
		);
	}

	public function setLogFileName(string $logFileName)
	{
		if (empty($logFileName)) {
			throw new \Error('Tried to set log file name to an empty value!');
		}

		$this->logFileName = preg_replace(
			'/[' . self::$invalidFileCharacters . ']/',
			'',
			$logFileName
		);
	}

	public function setTypeLogging(bool $switch = true)
	{
		$this->typeLogging = $switch;
	}

	public function toggleTypeLogging(): bool
	{
		$this->typeLogging = !$this->typeLogging;
		return $this->typeLogging;
	}

	public function setLogLevel(int $level = 0)
	{
		$this->logLevel = max(min($level, 100), 0);
	}

	public function setDateLogLevel(string $date, $logLevel = 100, $override = false): bool
	{
		$date = (new \DateTime($date))->format('Y-m-d H:i:s');
		if (empty($this->dateDependingLogLevels[$date]) || $override) {
			$this->dateDependingLogLevels[$date] = max(min($logLevel, 100), 0);
			return true;
		}
		return false;
	}

	public function info(string $description, $value = 'Xz9-KTq8Yb#dN7Wg_!')
	{
		$messageLevel = 'Info';
		if ($this->isLogging($messageLevel)) {
			self::addLog(
				$this->logPath,
				$this->logFileName,
				$messageLevel,
				$this->separateLoggingLengthLimit,
				$this->typeLogging,
				$this->logSubject,
				$description,
				$value
			);
		}
	}

	public function notice(string $description, $value = 'Xz9-KTq8Yb#dN7Wg_!')
	{
		$messageLevel = 'Notice';
		if ($this->isLogging($messageLevel)) {
			self::addLog(
				$this->logPath,
				$this->logFileName,
				$messageLevel,
				$this->separateLoggingLengthLimit,
				$this->typeLogging,
				$this->logSubject,
				$description,
				$value
			);
		}
	}

	public function warning(string $description, $value = 'Xz9-KTq8Yb#dN7Wg_!')
	{
		$messageLevel = 'Warning';
		if ($this->isLogging($messageLevel)) {
			self::addLog(
				$this->logPath,
				$this->logFileName,
				$messageLevel,
				$this->separateLoggingLengthLimit,
				$this->typeLogging,
				$this->logSubject,
				$description,
				$value
			);
		}
	}

	public function error(string $description, $value = 'Xz9-KTq8Yb#dN7Wg_!')
	{
		$messageLevel = 'Error';
		if ($this->isLogging($messageLevel)) {
			self::addLog(
				$this->logPath,
				$this->logFileName,
				$messageLevel,
				$this->separateLoggingLengthLimit,
				$this->typeLogging,
				$this->logSubject,
				$description,
				$value
			);
		}
	}

	public function fatal(string $description, $value = 'Xz9-KTq8Yb#dN7Wg_!')
	{
		$messageLevel = 'Fatal';
		if ($this->isLogging($messageLevel)) {
			self::addLog(
				$this->logPath,
				$this->logFileName,
				$messageLevel,
				$this->separateLoggingLengthLimit,
				$this->typeLogging,
				$this->logSubject,
				$description,
				$value
			);
		}
	}

	public function log(string $description, $value = 'Xz9-KTq8Yb#dN7Wg_!', int $messageLevel = 50)
	{
		if ($this->isLogging($messageLevel)) {
			self::addLog(
				$this->logPath,
				$this->logFileName,
				'Custom',
				$this->separateLoggingLengthLimit,
				$this->typeLogging,
				$this->logSubject,
				$description,
				$value
			);
		}
	}
}