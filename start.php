<?php

return function() {
	elgg_register_event_handler('init', 'system', 'facebook_connect_init');
};

function facebook_connect_init() {
	
	// sign on with facebook
	if (facebook_connect_allow_sign_on_with_facebook()) {
		elgg_extend_view('core/account/login_box', 'facebook_connect/login_btn');
		elgg_extend_view('login/sidebar/login', 'facebook_connect/login_btn');
	}
}

/**
 * Send password for new user who is registered using facebook connect
 *
 * @param $email
 * @param $name
 * @param $username
 * @param $password
 */

function send_user_password_mail($email, $name, $username, $password) {
	$site = elgg_get_site_entity();
	$email = trim($email);

	// send out other email addresses
	if (!is_email_address($email)) {
		return false;
	}

	$message = elgg_echo('facebook_connect:email:body', array(
					$name,
					$site->name,
					$site->url,
					$username,
					$email,
					$password,
					$site->name,
					$site->url
				)
	);

	$subject = elgg_echo('facebook_connect:email:subject', array($name));

	// create the from address
	$site = get_entity($site->guid);
	if (($site) && (isset($site->email))) {
		$from = $site->email;
	} else {
		$from = 'noreply@' . getDomain();
	}
	
	$email = \Elgg\Email::factory([
			'to' => $email,
			'from' => $from,
			'subject' => $subject,
			'body' => $message,
		]);
		elgg_send_email($email);
}

/**
 * check admin has enabled Sign-On-With-Facebook
 * Admins can disable or enable
 *
 * @access public
 * @return void
 */
function facebook_connect_allow_sign_on_with_facebook() {
	if (!$app_id = elgg_get_plugin_setting('app_id', 'facebook_connect')) {
		return false;
	}
	if (!$app_secret = elgg_get_plugin_setting('app_secret', 'facebook_connect')) {
		return false;
	}
	return elgg_get_plugin_setting('sign_on', 'facebook_connect') == 'yes';
}

/**
 * Checks if this site is accepting new users.
 * Admins can disable manual registration, but some might want to allow
 *
 * @access public
 * @return void
 */
function facebook_connect_allow_new_users_with_facebook() {
	$site_reg = elgg_get_config('allow_registration');
	$facebook_reg = elgg_get_plugin_setting('new_users', 'facebook_connect');
	if ($site_reg || (!$site_reg && $facebook_reg == 'yes')) {
		return true;
	}
	return false;
}