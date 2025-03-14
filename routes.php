<?php
return [
	'' => [
		'handler' => 'Main'
	],
	'projects/{projectName}' => [
		'handler' => 'Project',
		'patterns' => [
			'projectName' => '[a-zA-Z_0-9\-]+'
		],
		'action' => 'listBranches',
		'methods' => 'GET'
	],
	'projects/{projectName}/deploy' => [
		'handler' => 'Project',
		'patterns' => [
			'projectName' => '[a-zA-Z_0-9\-]+'
		],
		'action' => 'deploy',
		'methods' => 'POST'
	],
	'projects/{projectName}/scripts' => [
		'handler' => 'Project',
		'patterns' => [
			'projectName' => '[a-zA-Z_0-9\-]+'
		],
		'action' => 'changeScript',
		'methods' => 'POST'
	],
	'new-project' => [
		'handler' => 'Project',
		'methods' => 'GET',
		'action' => 'newProject'
	],
	'new-project' => [
		'handler' => 'Project',
		'action' => 'addProject',
		'methods' => 'POST'
	]
];