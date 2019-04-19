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

session_start();

$fb = new Facebook\Facebook([
  'app_id'                => elgg_get_plugin_setting('app_id', 'facebook_connect'),
  'app_secret'            => elgg_get_plugin_setting('app_secret', 'facebook_connect'),
  'default_graph_version' => elgg_get_plugin_setting('default_graph_version', 'facebook_connect'),
]);

$helper = $fb->getRedirectLoginHelper();
 
try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}
 
if (! isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Bad request';
  }
  exit;
}
 
// The OAuth 2.0 client handler helps us manage access tokens
$oAuth2Client = $fb->getOAuth2Client();
 
if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
    exit;
  }
  $fb->setDefaultAccessToken($accessToken->getValue());
}

$fbaccess_token = $accessToken->getValue();

try {
  $res = $fb->get('/me');
} catch (Facebook\Exceptions\FacebookSDKException $e) {
  echo $e->getMessage();
  exit;
}

$node = $res->getGraphObject();

$email = trim($node->getProperty('email'));
$name = trim($node->getProperty('name'));
$fbid = trim($node->getProperty('id'));
$fblink = trim($node->getProperty('link'));
$gender = trim($node->getProperty('gender'));

// Check - if email is blank then forward to login
if (empty($email)) {
  $helper = $fb->getRedirectLoginHelper();
   
  // $permissions = ['email', 'public_profile']; // Optional permissions
  $call_back = elgg_generate_url('collection:object:facebook_connect:login');
  $loginUrl = $helper->getLoginUrl($call_back);
  forward($loginUrl);
}

// Check - if email found check if user exists with the email ID
$getUsers = get_user_by_email($email);
  if((int)$getUsers[0]->guid > 0){
    // A. if exists then retrieved the user
    $user = get_user($getUsers[0]->guid);
    $user->name = $name;
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
    $uguid = register_user($username,$password,$name,$email);
    if ($uguid === false) {
      register_error(elgg_echo('registerbad'));
      forward();
    } else {
      $user = get_user($uguid);
      // send mail to user
      send_user_password_mail($email, $name, $username, $password);
    }
  }
  $user->gender = $gender;
  $user->save();
  
  // When either A or B is finished we have a registered user
  login($user,true);
  system_message(elgg_echo('facebook_connect:login:success'));

  // then map id, name, link, gender, locale to the user
  elgg_set_plugin_user_setting('fbid', $fbid, $user->guid);
  elgg_set_plugin_user_setting('fbaccess_token', $fbaccess_token, $user->guid);
  elgg_set_plugin_user_setting('fbname', $fbname, $user->guid);
  elgg_set_plugin_user_setting('fblink', $fblink, $user->guid);


  // also update the profile image of the user
  $res = $fb->get('/me/picture?type=large&amp;amp;redirect=false');
  $picture = $res->getGraphObject();
  
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
  
  forward(elgg_get_site_url());
  
 ?>