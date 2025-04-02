<?php

namespace App;

class Request {

	private bool $isXmlRequest;
	private string $httpMethod;
	private $values = [];
	private string $rawInput;
	private array $headers = [];
	private $specialHeaders = [
		'CONTENT_TYPE',
		'CONTENT_LENGTH'
	];

	function __construct()
	{
		$this->isXmlRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		$this->httpMethod = $_SERVER['REQUEST_METHOD'];
		$this->values['GET'] = $_GET;
		$this->rawInput = file_get_contents('php://input');
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') === 0) {
				$headerName = strtolower(str_replace('_', '-', substr($key, 5))); // Convert to proper header format
				$this->headers[$headerName] = $value;
			}
		}
		foreach ($this->specialHeaders as $headerName) {
			if (isset($_SERVER[$headerName])) {
				$this->headers[strtolower(str_replace('_', '-', $headerName))] = $_SERVER[$headerName];
			}
		}
		$this->processRawInput();
	}

	private function processRawInput(): void
	{
		$contentType = $this->getHeader('Content-Type');
		if (!empty($contentType)) {
			if (strpos($contentType, 'json') !== false) {
				$this->values[$this->httpMethod] = json_decode($this->rawInput);
				return;
			}
			if (strpos($contentType, 'x-www-form-urlencoded') !== false) {
				if ($this->httpMethod === 'POST') {
					$this->values[$this->httpMethod] = $_POST;
				} else {
					parse_str(file_get_contents('php://input'), $this->values[$this->httpMethod]);
				}
			}
			if (strpos($contentType, 'multipart/form-data') !== false) {
				$this->values[$this->httpMethod] = $_POST;
				$this->values['files'] = $_FILES;
			}
			if (strpos($contentType, 'xml') !== false) {
				$xml = simplexml_load_string($this->rawInput);
				$this->values[$this->httpMethod] = json_decode(json_encode($xml), true); // Convert XML to array
			}
		}
		$this->values[$this->httpMethod] = $this->rawInput;
	}

	public function getHeader(string $key): int|string|null
	{
		if (isset($this->headers[strtolower(str_replace('_', '-', $key))])) {
			return $this->headers[strtolower(str_replace('_', '-', $key))];
		}
		return null;
	}

	public function get(string $key, bool $queryParamsFirst = false)
	{
		if (
			!isset($this->values[$this->httpMethod][$key]) &&
			!isset($this->values['GET'][$key])
		) {
			return null;
		}
		if (
			isset($this->values[$this->httpMethod][$key]) &&
			isset($this->values['GET'][$key])
		) {
			return $queryParamsFirst ? $this->values['GET'][$key] : $this->values[$this->httpMethod][$key];
		}
		return isset($this->values[$this->httpMethod][$key]) ? $this->values[$this->httpMethod][$key] : $this->values['GET'][$key];
	}

	public function isXHR(): bool
	{
		return $this->isXmlRequest;
	}
}