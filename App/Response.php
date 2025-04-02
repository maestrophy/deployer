<?php

namespace App;

class Response {
	
	private array $headers = [];
	private $currentContent;
	private string $literalCurrentContent;

	function __construct($content = null, int $statusCode = 0, string $statusMessage = '')
	{
		if (!empty($content)) {
			$this->setContent($content);
		}
	}

	public function sendPartial()
	{
		if (empty($this->currentContent)) {
			trigger_error('Current response content is empty!');
			return;
		}
		$this->addHeadersAndAddPartialIndicator();
		$this->setRequiredHeaders();
		echo empty($this->literalCurrentContent) ? '' : $this->literalCurrentContent;
		ob_flush();
		flush();
		ob_get_clean();
	}

	private function guessContentType(): string
	{
		if (is_array($this->currentContent)) {
			return 'application/json';
		}
		if (is_string($this->currentContent)) {
			$tryJson = json_decode($this->currentContent, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return 'application/json';
			}
			if (preg_match('/<(div|span|p|html|body|section|form|img|)/', $this->currentContent)) {
				return 'text/html';
			}
		}
		if (is_object($this->currentContent)) {
			return 'application/json';
		}
		return 'text/plain';
	}

	private function addHeadersAndAddPartialIndicator(): void
	{
		if (headers_sent()) {
			return;
		}
		if (is_array($this->currentContent)) {
			$this->addHeader('Content-Type', 'application/json');
			$this->currentContent['duringProgress'] = true;
			$this->literalCurrentContent = json_encode($this->currentContent);
			return;
		}
		if (is_string($this->currentContent)) {
			$tryJson = json_decode($this->currentContent, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$this->addHeader('Content-Type', 'application/json');
				$tryJson['duringProgress'] = true;
				$this->currentContent = json_encode($tryJson);
				$this->literalCurrentContent = $this->currentContent;
				return;
			}
		}
		if (is_object($this->currentContent)) {
			$this->currentContent->duringProgress = true;
			$this->literalCurrentContent = json_encode($this->currentContent);
			$this->addHeader('Content-Type', 'application/json');
		}
		$this->addHeader('Connection', 'close');
	}

	public function addHeader(string $headerName, string $headerValue): bool
	{
		if (!headers_sent()) {
			$this->headers[$headerName] = $headerValue;
			return true;
		}
		return false;
	}

	private function setRequiredHeaders(): void
	{
		if (!headers_sent()) {
			if (!empty($this->headers)) {
				foreach ($this->headers as $headerName => $headerValue) {
					if (preg_match('/^status[_\-\s]?code/i', $headerName)) {
						$statusCode = $headerValue;
					} else if (preg_match('/^status[_\-\s]?message/i', $headerName)) {
						$statusMessage = $headerValue;
					} else {
						header($headerName . ': ' . $headerValue);
					}
				}
				if (!empty($statusCode)) {
					if (empty($statusMessage)) {
						http_response_code($statusCode);
					} else {
						header('HTTP/1.1 ' . $statusCode . ' ' . $statusMessage);
					}
				}
			}
			if (
				empty($this->headers['Content-Type']) &&
				count(
					array_filter(
						headers_list(),
						fn ($header) => stripos($header, 'Content-Type:') === 0
					)
				) === 0
			) {
				header('Content-Type: ' . $this->guessContentType());
			}
		}
	}

	public function setContent($content): Response
	{
		$this->currentContent = $content;
		$contentType = $this->guessContentType();
		switch ($contentType) {
			case 'application/json':
				if (!is_string($content)) {
					$this->literalCurrentContent = json_encode($content);
				}
				break;
			default:
			$this->literalCurrentContent = $content;
		}
		return $this;
	}

	public function setJsonContent(array|string $content): Response
	{
		if (is_array($content)) {
			$this->currentContent = $content;
			$this->literalCurrentContent = json_encode($content);
		} else {
			json_decode($content);
			if (json_last_error() === JSON_ERROR_NONE) {
				$this->currentContent = $content;
				$this->literalCurrentContent = $content;
			} else {
				throw new \Error('Invalid JSON content!');
			}
		}
		return $this;
	}

	public function send($content = null, int $statusCode = 0, string $statusMessage = '')
	{
		if (!empty($statusCode)) {
			$this->addHeader('Status-Code', $statusCode);
		}
		if (!empty($statusMessage)) {
			$this->addHeader('Status-Message', $statusMessage);
		}
		if (!empty($content)) {
			$this->setContent($content);
		}
		$this->setRequiredHeaders();
		echo empty($this->literalCurrentContent) ? '' : $this->literalCurrentContent;
	}

	public function addContent($content): Response
	{
		$this->currentContent = $content;
		$contentType = $this->guessContentType();
		switch ($contentType) {
			case 'application/json':
				if (!is_string($content)) {
					$this->literalCurrentContent .= json_encode($content);
				}
				break;
			default:
			$this->literalCurrentContent .= $content;
		}
		return $this;
	}

	public function setStatusCode(int $statusCode, string $statusMessage = '')
	{
		$this->addHeader('Status-Code', $statusCode);
		if (!empty($statusMessage)) {
			$this->addHeader('Status-Message', $statusMessage);
		}
	}

	public function setStatusMessage(string $statusMessage, int $statusCode = 0)
	{
		$this->addHeader('Status-Message', $statusMessage);
		if (!empty($statusCode)) {
			$this->addHeader('Status-Code', $statusCode);
		}
	}
}