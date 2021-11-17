<?php
require_once __DIR__ . "/lib/functions.php";

return [
	'plugin' => [
		'name' => 'Facebook Connect',
		'version' => '4.0',
		'dependencies' => [],
	],
	'bootstrap' => FacebookConnect::class,
	'views' => [
		'default' => [
			'facebook_connect/' => __DIR__ . '/graphics',
		],
	],
	'routes' => [
		'collection:object:facebook_connect:login' => [
			'path' => '/facebook_connect/login',
			'resource' => 'facebook_connect/login',
			'walled' => false,
		],
		'collection:object:facebook_connect:connect' => [
			'path' => '/facebook_connect/connect',
			'resource' => 'facebook_connect/connect',
			'walled' => false,
		],
		'collection:object:facebook_connect:revoke' => [
			'path' => '/facebook_connect/revoke',
			'resource' => 'facebook_connect/revoke',
		],
	],
];
