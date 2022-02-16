<?php
$user = elgg_get_logged_in_user_entity();

// unregister user's information
$user->removePluginSetting('facebook_connect', 'fbid');
$user->removePluginSetting('facebook_connect', 'fbaccess_token');
// $user->removePluginSetting('facebook_connect', 'fbname');

return elgg_ok_response('', elgg_echo('facebook_connect:revoke:success'), 'settings/plugins/'.$user->username.'/facebook_connect');