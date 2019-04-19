<?php

return [
	'routes' => [
		'collection:object:facebook_connect:login' => [
			'path' => '/facebook_connect/login',
			'resource' => 'facebook_connect/login',
			'walled' => false,
		],
		'collection:object:facebook_connect:revoke' => [
			'path' => '/facebook_connect/revoke',
			'resource' => 'facebook_connect/revoke',
		],
	],
];
