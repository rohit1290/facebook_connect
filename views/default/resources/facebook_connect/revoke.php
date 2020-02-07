<?php
$user = elgg_get_logged_in_user_entity();

// unregister user's information
elgg_unset_plugin_user_setting('fbid', $user->guid, 'facebook_connect');
elgg_unset_plugin_user_setting('fbaccess_token', $user->guid, 'facebook_connect');
elgg_unset_plugin_user_setting('fbname', $user->guid, 'facebook_connect');

system_message(elgg_echo('facebook_connect:revoke:success'));

forward('settings/plugins/'.$user->username.'/facebook_connect', 'facebook_connect');

	