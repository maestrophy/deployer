<?php

namespace Models;

use Services\CommandService;
use Services\Logger;
use Services\ProjectService;
use App\Response;
use Exception;

class Project extends BaseModel {

	private string $name;
	private string $path;
	private array $scripts;
	private array $branches;
	private string $activeBranch;
	private Logger $logger;

	function __construct(array $data)
	{
		$this->logger = new Logger('', __CLASS__);
		ProjectService::validateProjectData($data);
		$this->name = $data['projectName'];
		$this->path = $data['projectPath'];
		$this->scripts = $data['scripts'];
	}

	public function checkout(string $branchName)
	{
		/**
		 * @var Response
		 */
		$response = $this->di->get('response');
		CommandService::runCommandsAsUserInFolder(['git fetch', 'git fetch origin'], $this->path);

		$response->sendPartial();
		
		$activeBranch = $this->getActiveBranch();
		$this->logger->log('Branch to switch on', $branchName);
		$this->logger->log('Active branch', $activeBranch);
		
		// Separating scripts to execute before, and after checkout
		$separated = $this->getScriptsSeparated();
		$scriptsBeforePull = $separated['before'];
		$this->logger->log('Scripts before', $scriptsBeforePull);
		$scriptsAfterPull = $separated['after'];
		$this->logger->log('Scripts after', $scriptsAfterPull);

		$response->setContent(
			[
				'allScripts' => [
					...$scriptsBeforePull,
					[
						'schedule' => 'none',
						'targetPath' => '',
						'command' => ($branchName === $activeBranch ? 'pull' : 'checkout')
					],
					...$scriptsAfterPull
				]
			]
		);
		$response->sendPartial();

		// Executing scripts before checkout
		foreach ($scriptsBeforePull as $script) {
			$this->logger->log('Script to execute', $script);
			$targetPath = empty($script['targetPath']) ? $this->path : $script['targetPath'];
			$this->logger->log('Target path', $targetPath);
			try {
				CommandService::runCommandAsUserInFolder($script['command'], $targetPath);
				$response->setContent(
					[
						'scriptFinished' => $script['command']
					]
				);
				$response->sendPartial();
				$this->logger->log('Script successfully executed');
			} catch (Exception $e) {
				$response->setContent(
					[
						'scriptFailed' => $script['command'],
						'errorMessage' => $e->getMessage()
					]
				);
				$response->sendPartial();

				$this->logger->log('Script failed', $e->getMessage());
				return false;
			}
		}

		// Only pulling, staying the same branch
		if ($branchName === $activeBranch) {
			$this->logger->log('Only pulling, staying the same branch');
			try {
				$pullResult = $this->pull();
				if (
					is_array($pullResult) &&
					count($pullResult) === 1 &&
					$pullResult[0] === 'Already up to date.'
				) {
					$response->setContent(
						[
							'scriptFinished' => 'pull'
						]
					);
					$response->sendPartial();
					foreach ($scriptsAfterPull as $script) {
						$response->setContent(
							[
								'scriptFinished' => $script['command']
							]
						);
						$response->sendPartial();
					}
					return true;
				}
				$response->setContent(
					[
						'scriptFinished' => 'pull'
					]
				);
				$response->sendPartial();
			} catch (\Exception $e) {
				$this->logger->log('Pull failed!', $e->getMessage());
				$response->setContent(
					[
						'scriptFailed' => 'pull',
						'errorMessage' => $e->getMessage()
					]
				);
				$response->sendPartial();
				return false;
			}
		} else {
			try {
				CommandService::runCommandAsUserInFolder('git checkout -B branch-name origin/' . $branchName, $this->path);
				$response->setContent(
					[
						'scriptFinished' => 'checkout'
					]
				);
				$response->sendPartial();
				$this->logger->log('Checkout succeeded!');
			} catch (Exception $e) {
				$response->setContent(
					[
						'scriptFailed' => 'checkout',
						'errorMessage' => $e->getMessage()
					]
				);
				$response->sendPartial();
				$this->logger->log('Pull failed!', $e->getMessage());
				return false;
			}
		}

		// Executing scripts after checkout
		foreach ($scriptsAfterPull as $script) {
			$this->logger->log('Script to execute', $script);
			$targetPath = empty($script['targetPath']) ? $this->path : $script['targetPath'];
			$this->logger->log('Target path', $targetPath);
			try {
				CommandService::runCommandAsUserInFolder($script['command'], $targetPath);
				$response->setContent(
					[
						'scriptFinished' => $script['command']
					]
				);
				$response->sendPartial();
				$this->logger->log('Script successfully executed');
			} catch (Exception $e) {
				$response->setContent(
					[
						'scriptFailed' => $script['command'],
						'errorMessage' => $e->getMessage()
					]
				);
				$response->sendPartial();

				$this->logger->log('Script failed', $e->getMessage());
				return false;
			}
		}
		return true;
	}

	public function pull()
	{
		return CommandService::runCommandAsUserInFolder('git pull', $this->path);
	}

	public function getProjectName(): string
	{
		return $this->name;
	}

	public function getUrlEncodedProjectName(): string
	{
		return urlencode($this->getProjectName());
	}

	public function getBranchList(): array
	{
		CommandService::runCommandsAsUserInFolder(['git fetch', 'git fetch origin'], $this->path);
		$branchesOutput = CommandService::runCommandAsUserInFolder('git branch -r', $this->path);
		$this->logger->info('Branchlist', $branchesOutput);
		return array_map(
			fn ($branch) =>
				trim(
					str_replace(
						'origin/',
						'',
						trim($branch)
					)
				),
			array_filter(
				$branchesOutput,
				fn ($item) => !preg_match('/HEAD ->/', $item)
			)
		);
	}

	public function getActiveBranch(): string
	{
		$activeBranch = '';
		$activeBranchesOutput = CommandService::runCommandAsUserInFolder('git branch', $this->path);
		foreach ($activeBranchesOutput as $outputLine) {
			if (substr($outputLine, 0, 2) === '* ') {
				$activeBranch = substr($outputLine, 2);
			}
		}
		return $activeBranch;
	}

	public function getBuildScripts(): array
	{
		return $this->scripts;
	}

	public function getScriptsSeparated(): array
	{
		$result = [];
		$result['before'] = array_filter($this->scripts, fn ($script) => $script['schedule'] === 'beforePull');
		$result['after'] = array_filter($this->scripts, fn ($script) => $script['schedule'] === 'afterPull');
		return $result;
	}

	public function getProjectPath(): string
	{
		return $this->path;
	}
}