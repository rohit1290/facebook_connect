<?php 
// Source: https://benmarshall.me/facebook-php-sdk/
	// sanity check
if (!facebook_connect_allow_sign_on_with_facebook()) {
	register_error(elgg_echo('Facebook registration is disabled'));
	forward();
}
if(elgg_is_logged_in()) {
	register_error(elgg_echo('Please logout and then login using facebook'));
	forward();
}

// Response
// https://www.campuskarma.in/facebook_connect/login?
// code=AQBeyThi2quwJrFZNJjmozb0w7ReH7JSy3ASbVfrUdfiHIaeHdfMrwZ67yxRFHGpuojQwnTShL0ChlxDiLmcvyMIeh6T2s81yQQl3gwaQH__k1aS_TFubwFys0WUOSv_49euREagLRYHVkm6e4dWFlk8c8jshzs29ks60skO44oP9GZ_xpEEVGqnxEBywh9bY6YwdtE6lt6ZmXgfQLw6mGbA1AIybGyb_7kD0XvqLTbWa5MLHI0HHzqlFFZJcPDTB3d4FuqHmMNqx477-wOYCFowCfNHHeq7wIPEnvpeSUupAxIXIGszwIlNi91p-FngyHP32zZ4M2IpaeZWS_z7S8a2
// &state=a9b7ba70783b617e9998dc4dd82eb3c5#_=_

$code = get_input('code');
$state = get_input('state');
$error_reason = get_input('error_reason', null);  // user_denied
$error = get_input('error', null);  // access_denied
$error_description = str_replace("+"," ", get_input('error_description', null));  // Permissions+error.

if($error != null){
    register_error("Error in Login: ".$error_description ." - ". $error_reason);
    forward();
}

$app_version = elgg_get_plugin_setting('default_graph_version', 'facebook_connect');
$app_id = elgg_get_plugin_setting('app_id', 'facebook_connect');
$app_secret = elgg_get_plugin_setting('app_secret', 'facebook_connect');
$redirect_uri = elgg_generate_url('collection:object:facebook_connect:login');

$url = "https://graph.facebook.com/$app_version/oauth/access_token?client_id=$app_id&redirect_uri=$redirect_uri&client_secret=$app_secret&code=$code";
$code_exchange = file_get_contents($url);

// var_dump($code_exchange);
// {
//   "access_token":"EAAEcLTmZA1DoBAFcqi58Q3Lfb1mnTcuKeQ7drh9d8Ls8XTKCvN4IqYTCbO7z2MmqpWggo9aPzSdRQ5aIx2jq6it2IoCWJ8RzR3QiF74zej5QWrWLWLrlFwJYrPZAvRjMu7MiC4t0FfZConfkPl71vXcNWraGyXsAlc2ZA73DmQZDZD",
//   "token_type":"bearer",
//   "expires_in":5144343
//  }

if($code_exchange === false) {
    register_error("There was an error with the login. Please try again");
    forward();
} else {
    $code_exchange = json_decode($code_exchange,true);
}

$access_token = $code_exchange['access_token'];

$url = "https://graph.facebook.com/$app_version/me?fields=id,name,email&access_token=$access_token";
$user_data = file_get_contents($url);

if($user_data === false) {
   register_error("No user data returned from facebook");
   forward(); 
} else {
   $user_data = json_decode($user_data,true);
}

$email = $user_data['email'];
$fbname = $user_data['name'];
$fbid = $user_data['id'];

$fbaccess_token = $access_token;

// Check - if email is blank then forward to login
if (empty($email)) {
    $url = "https://www.facebook.com/$app_version/dialog/oauth?client_id=$app_id&redirect_uri=$redirect_uri&auth_type=rerequest&scope=email";
    forward($url);
}

// Check - if email found check if user exists with the email ID
$getUsers = get_user_by_email($email);
  if((int)$getUsers[0]->guid > 0){
    // A. if exists then retrieved the user
    $user = get_user($getUsers[0]->guid);
    $user->name = $fbname;
    $user->validated = 1;
    $user->validated_method = 'facebook';
    $user->language = get_language();
    $user->save();
  } else {
    // check new registration allowed
    if (!facebook_connect_allow_new_users_with_facebook()) {
    	register_error(elgg_echo('registerdisabled'));
    	forward();
    }
    // B. if not exist then create a profile for the user with name and email id,
    $u = explode("@", $email);
    $username = $u[0];
    $usernameTmp = $username;
    while (get_user_by_username($username)) {
      $username = $usernameTmp . '_' . rand(1000, 9999);
    }
    $password = generate_random_cleartext_password();
    $uguid = register_user($username,$password,$fbname,$email);
    if ($uguid === false) {
      register_error(elgg_echo('registerbad'));
      forward();
    } else {
      $user = get_user($uguid);
      // send mail to user
      send_user_password_mail($email, $fbname, $username, $password);
    }
  }

  // When either A or B is finished we have a registered user
  login($user,true);
  system_message(elgg_echo('facebook_connect:login:success'));

  // then map id, accessToken, name
  elgg_set_plugin_user_setting('fbid', $fbid, $user->guid, 'facebook_connect');
  elgg_set_plugin_user_setting('fbaccess_token', $fbaccess_token, $user->guid, 'facebook_connect');
  elgg_set_plugin_user_setting('fbname', $fbname, $user->guid, 'facebook_connect');

  // also update the profile image of the user
  $url = "https://graph.facebook.com/$app_version/me/picture?type=large&redirect=false&access_token=$access_token";
  $picture_json = file_get_contents($url);
  
	if((int)$user->icontime < (time() - 31536000)) { 
		// Dont change icon if updated within last 1 year
	  if($picture_json !== false){
	      
	      $picture_json = json_decode($picture_json,true);
	      $fb_pic_url = $picture_json['data']['url'];
	      $picture = file_get_contents($fb_pic_url);
	      
	      $sizes = array(
	      	'topbar' => array(16, 16, TRUE),
	      	'tiny' => array(25, 25, TRUE),
	      	'small' => array(40, 40, TRUE),
	      	'medium' => array(100, 100, TRUE),
	      	'large' => array(200, 200, FALSE),
	      	'master' => array(550, 550, FALSE),
	      );
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
  
  forward(elgg_get_site_url());
  
 ?>
