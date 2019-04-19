<?php
$site_name = elgg_get_site_entity()->name;
$user_id = elgg_get_logged_in_user_guid();

$facebook_id = elgg_get_plugin_user_setting('fbid', $user_id, 'facebook_connect');
$facebook_name = elgg_get_plugin_user_setting('fbname', $user_id, 'facebook_connect');
$access_token = elgg_get_plugin_user_setting('fbaccess_token', $user_id, 'facebook_connect');

echo '<div>' . elgg_echo('facebook_connect:usersettings:description', [$site_name]) . '</div>';

if (!$facebook_id) {
	// send user off to validate account
	echo '<div>' .  elgg_echo('facebook_connect:usersettings:logout_required', [$site_name]) . '</div>';
} else {
	echo '<p>' . sprintf(elgg_echo('facebook_connect:usersettings:authorized'), [$facebook_id, $facebook_name]) . '</p>';
	$url = elgg_get_site_url() . "facebook_connect/revoke";
	echo '<div>' . sprintf(elgg_echo('facebook_connect:usersettings:revoke'), $url) . '</div>';
}