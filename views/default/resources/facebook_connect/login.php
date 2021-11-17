<?php

$cncl_url = elgg_get_site_url(). "login";

if (!facebook_connect_allow_sign_on_with_facebook()) {
	elgg_error_response(elgg_echo('Facebook registration is disabled'));
	return elgg_redirect_response($cncl_url);
}
if (elgg_is_logged_in()) {
	elgg_error_response(elgg_echo('Please logout and then login using facebook'));
	return elgg_redirect_response($cncl_url);
}

// Response (on Canceled)
// https://yourdomain/facebook_connect/login?
// error_reason=user_denied
// &error=access_denied
// &error_description=Permissions+error.

$error_reason = get_input('error_reason', null);
$error_description = get_input('error_description', null);
if ($error_reason != null) {
	elgg_error_response(elgg_echo($error_description));
	return elgg_redirect_response($cncl_url);
}

// Response (on Sucess)
// https://yourdomain/facebook_connect/login?
// code=<code>
// &state=<randomcode>#_=_

$code = get_input('code', null);
if($code == null) {
	elgg_error_response("Authorization not found");
	return elgg_redirect_response($cncl_url);
}

$app_version = elgg_get_plugin_setting('default_graph_version', 'facebook_connect');
$app_id = elgg_get_plugin_setting('app_id', 'facebook_connect');
$app_secret = elgg_get_plugin_setting('app_secret', 'facebook_connect');
$redirect_uri = elgg_generate_url('collection:object:facebook_connect:login');
$state = md5(rand(1000, 999));

$url = "https://graph.facebook.com/$app_version/oauth/access_token?client_id=$app_id&redirect_uri=$redirect_uri&client_secret=$app_secret&code=$code";
$code_exchange = file_get_contents($url);

// {
//   "access_token":"",
//   "token_type":"",
//   "expires_in":
//  }

// If file_get_contents failed and did not got any response
if ($code_exchange === false) {
	elgg_error_response("There was an error with the login. Please try again");
	return elgg_redirect_response($cncl_url);
}

$code_exchange = json_decode($code_exchange, true);
if(!array_key_exists('access_token', $code_exchange)) {
	elgg_error_response("NO Access Token found. Please try again");
	return elgg_redirect_response($cncl_url);
}

$access_token = $code_exchange['access_token'];

$url = "https://graph.facebook.com/$app_version/me/permissions/email?access_token=$access_token";
$perm_data = file_get_contents($url);

// If file_get_contents failed and did not got any response
if ($perm_data === false) {
	elgg_error_response("There was an error with the permissions. Please try again");
	return elgg_redirect_response($cncl_url);
}

$perm_data = json_decode($perm_data, true);
if($perm_data['data'][0]['status'] != "granted") {
	// Re Requesting for the permission (email) as it was not allowed earlier
	$url = "https://www.facebook.com/$app_version/dialog/oauth?client_id={$app_id}&redirect_uri={$redirect_uri}&auth_type=rerequest&state={$state}&scope=email";
	return elgg_redirect_response($url);
}

$url = "https://graph.facebook.com/$app_version/me?fields=id,name,email&access_token=$access_token";
$user_data = file_get_contents($url);

// If file_get_contents failed and did not got any response
if ($user_data === false) {
	elgg_error_response("No user data returned from facebook");
	return elgg_redirect_response($cncl_url);
}

$user_data = json_decode($user_data, true);
$fbid = $user_data['id'];
$fbname = $user_data['name'];
$email = $user_data['email'];
$fbaccess_token = $access_token;

// Check if user exists with the email ID
$getUsers = get_user_by_email($email);
if ((int) $getUsers[0]->guid > 0) {
	// if exists then retrieved the user
	$user = get_user($getUsers[0]->guid);
	$user->name = $fbname;
	$user->validated = 1;
	$user->validated_method = 'facebook';
	$user->language = get_current_language();
	$user->save();
} else {
	// Check new registration allowed
	if (!facebook_connect_allow_new_users_with_facebook()) {
		elgg_error_response(elgg_echo('registerdisabled'));
		return elgg_redirect_response($cncl_url);
	}
	// If not exist then create a profile for the user with name and email id,
	$u = explode("@", $email);
	$username = $u[0];
	$usernameTmp = $username;

	$username = elgg_call(ELGG_SHOW_DISABLED_ENTITIES, function() use ($username) {
		while (get_user_by_username($username)) {
			$username = $usernameTmp . '_' . rand(1000, 9999);
		}
		return $username;
	});

	$password = generate_random_cleartext_password();
	$uguid = register_user($username, $password, $fbname, $email);
	if ($uguid === false) {
		elgg_error_response(elgg_echo('registerbad'));
		return elgg_redirect_response($cncl_url);
	} else {
		$user = get_user($uguid);
	  // Send mail to user
		send_user_password_mail($email, $fbname, $username, $password);
	}
}

  // We have a registered user
  login($user, true);
  elgg_ok_response('', elgg_echo('facebook_connect:login:success'));

  // then map id, accessToken
  $user->setPluginSetting('facebook_connect', 'fbid', $fbid);
  $user->setPluginSetting('facebook_connect', 'fbaccess_token', $fbaccess_token);
  // $user->setPluginSetting('facebook_connect', 'fbname', $fbname);

  // also update the profile image of the user
  $url = "https://graph.facebook.com/$app_version/me/picture?type=large&redirect=false&access_token=$access_token";
  $picture_json = file_get_contents($url);

if ((int) $user->icontime < (time() - 31536000)) {
	// Dont change icon if updated within last 1 year
	if ($picture_json !== false) {
		$picture_json = json_decode($picture_json, true);
		$fb_pic_url = $picture_json['data']['url'];
		$picture = file_get_contents($fb_pic_url);
		
		$sizes = [
			'topbar' => [16, 16, true],
			'tiny' => [25, 25, true],
			'small' => [40, 40, true],
			'medium' => [100, 100, true],
			'large' => [200, 200, false],
			'master' => [550, 550, false],
		];
		$filehandler = new ElggFile();
		$filehandler->owner_guid = $user->getGUID();
		foreach ($sizes as $size => $dimensions) {
			$filehandler->setFilename("profile/$user->guid$size.jpg");
			$filehandler->open('write');
			$filehandler->write($picture);
			$filehandler->close();
		}
		$user->icontime = time();
		$user->save();
	}
}

return elgg_redirect_response(elgg_get_site_url());