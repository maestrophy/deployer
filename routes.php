<?php
return [
	[
		'uri' => '',
		'handler' => 'Main'
	],
	[
		'uri' => 'projects/{projectName}',
		'handler' => 'Project',
		'patterns' => [
			'projectName' => '[a-zA-Z_0-9\-\+%]+'
		],
		'action' => 'listBranches',
		'methods' => 'GET'
	],
	[
		'uri' => 'projects/{projectName}/deploy',
		'handler' => 'Project',
		'patterns' => [
			'projectName' => '[a-zA-Z_0-9\-\+%]+'
		],
		'action' => 'deploy',
		'methods' => 'PATCH'
	],
	[
		'uri' => 'projects/{projectName}/scripts',
		'handler' => 'Project',
		'patterns' => [
			'projectName' => '[a-zA-Z_0-9\-\+%]+'
		],
		'action' => 'changeScript',
		'methods' => 'POST'
	],
	[
		'uri' => 'new-project',
		'handler' => 'Project',
		'methods' => 'GET',
		'action' => 'newProject'
	],
	[
		'uri' => 'new-project/check-path',
		'handler' => 'Project',
		'methods' => 'GET',
		'action' => 'checkPath'
	],
	[
		'uri' => 'new-project/check-name',
		'handler' => 'Project',
		'methods' => 'GET',
		'action' => 'checkProjectName'
	],
	[
		'uri' => 'new-project',
		'handler' => 'Project',
		'action' => 'addProject',
		'methods' => 'POST'
	]
];