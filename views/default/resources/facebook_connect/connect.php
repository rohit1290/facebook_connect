<?php
// TODO: if spam login filter is enabled then check for spam

$app_version = elgg_get_plugin_setting('default_graph_version', 'facebook_connect');
$app_id = elgg_get_plugin_setting('app_id', 'facebook_connect');
$redirect_uri = elgg_generate_url('collection:object:facebook_connect:login');
$state = md5(rand(1000,999));
$_SESSION['fb_connect_state'] = $state;
$url = "https://www.facebook.com/$app_version/dialog/oauth?client_id=$app_id&redirect_uri=$redirect_uri&state=$state&scope=email";
// echo $url;
forward($url);
?>
