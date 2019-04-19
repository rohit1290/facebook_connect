<?php
session_start();

// Initialize the Facebook PHP SDK v5.
$fb = new Facebook\Facebook([
  'app_id'                => elgg_get_plugin_setting('app_id', 'facebook_connect'),
  'app_secret'            => elgg_get_plugin_setting('app_secret', 'facebook_connect'),
  'default_graph_version' => elgg_get_plugin_setting('default_graph_version', 'facebook_connect'),
]);
 
$helper = $fb->getRedirectLoginHelper();
 
// $permissions = ['email', 'public_profile']; // Optional permissions
$call_back = elgg_generate_url('collection:object:facebook_connect:login');
$loginUrl = $helper->getLoginUrl($call_back);

echo '<a href="' . $loginUrl . '"><img src="'.elgg_get_simplecache_url('facebook_connect/facebook_connect.png').'" width="70%" style="display:block;margin:auto;"></a>';
?>
