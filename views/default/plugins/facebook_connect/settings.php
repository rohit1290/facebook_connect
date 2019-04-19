<?php
$insert_view = elgg_view('facebooksettings/extend');

$app_id_string = elgg_echo('facebook_connect:app_id');
$app_id_view = elgg_view('input/text', array(
	'name' => 'params[app_id]',
	'value' => $vars['entity']->app_id,
	'class' => 'text_input',
));

$app_secret_string = elgg_echo('facebook_connect:app_secret');
$app_secret_view = elgg_view('input/text', array(
	'name' => 'params[app_secret]',
	'value' => $vars['entity']->app_secret,
	'class' => 'text_input',
));

$default_graph_version_string = elgg_echo('facebook_connect:default_graph_version');
$default_graph_version_view = elgg_view('input/text', array(
	'name' => 'params[default_graph_version]',
	'value' => $vars['entity']->default_graph_version,
	'class' => 'text_input',
));

$sign_on_with_facebook_string = elgg_echo('facebook_connect:login');
$sign_on_with_facebook_view = elgg_view('input/dropdown', array(
	'name' => 'params[sign_on]',
	'options_values' => array(
		'yes' => elgg_echo('option:yes'),
		'no' => elgg_echo('option:no'),
	),
	'value' => $vars['entity']->sign_on ? $vars['entity']->sign_on : 'yes',
));

$new_users_with_facebook = elgg_echo('facebook_connect:new_users');
$new_users_with_facebook_view = elgg_view('input/dropdown', array(
	'name' => 'params[new_users]',
	'options_values' => array(
		'yes' => elgg_echo('option:yes'),
		'no' => elgg_echo('option:no'),
	),
	'value' => $vars['entity']->new_users ? $vars['entity']->new_users : 'no',
));

$settings = <<<__HTML
<div>$insert_view</div>
<div>$app_id_string $app_id_view</div>
<div>$app_secret_string $app_secret_view</div>
<div>$default_graph_version_string $default_graph_version_view</div>
<div>$sign_on_with_facebook_string $sign_on_with_facebook_view</div>
<div>$new_users_with_facebook $new_users_with_facebook_view</div>
__HTML;

echo $settings;