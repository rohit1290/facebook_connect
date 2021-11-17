<?php
$site_name = elgg_get_site_entity()->name;
$user = elgg_get_logged_in_user_entity();

$facebook_id = $user->getPluginSetting('facebook_connect', 'fbid');
$facebook_name = $user->getPluginSetting('facebook_connect', 'fbname');
$access_token = $user->getPluginSetting('facebook_connect', 'fbaccess_token');

echo '<div>' . elgg_echo('facebook_connect:usersettings:description', [$site_name]) . '</div>';

if (!$facebook_id) {
	// send user off to validate account
	echo '<div>' .  elgg_echo('facebook_connect:usersettings:logout_required', [$site_name]) . '</div>';
} else {
	echo '<p>' . sprintf(elgg_echo('facebook_connect:usersettings:authorized'), [$facebook_id, $facebook_name]) . '</p>';
	$url = elgg_get_site_url() . "facebook_connect/revoke";
	echo '<div>' . sprintf(elgg_echo('facebook_connect:usersettings:revoke'), $url) . '</div>';
}
