<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

require_once __DIR__ . '/util.php';
require_once __DIR__ . '/jwt.php';

class WebtonativeSocialRestController
{
  private static $GOOGLE_REDIRECT_URI = '/wp-json/webtonative/social-login/verify/google';
  private static $GOOGLE_OAUTH_URI = 'https://accounts.google.com/o/oauth2/auth';
  private static $GOOGLE_OAUTH_TOKEN_URI = 'https://oauth2.googleapis.com/token';
  private static $GOOGLE_TOKEN_INFO_URI = 'https://oauth2.googleapis.com/tokeninfo';

  private static $FACEBOOK_REDIRECT_URI = '/wp-json/webtonative/social-login/verify/facebook';
  private static $FACEBOOK_OAUTH_URI = 'https://www.facebook.com/v16.0/dialog/oauth';
  private static $FACEBOOK_ACCESS_TOKEN_URI = 'https://graph.facebook.com/v16.0/oauth/access_token';
  private static $FACEBOOK_USER_GQL_URL = 'https://graph.facebook.com/me';

  private static $APPLE_BASE_URL = 'https://appleid.apple.com';
  private static $JWKS_APPLE_URI = '/auth/keys';

  function __construct()
  {
    add_action('rest_api_init', array($this, 'at_rest_init'));
  }

  function at_rest_init()
  {
    $namespace = 'webtonative/social-login';
    $appStr = '(?P<app>(facebook|google|apple))';
    register_rest_route($namespace, 'auth/' . $appStr, array(
      'methods' => 'GET',
      'callback' => array($this, 'auth_route'),
    ));
    register_rest_route($namespace, 'verify/' . $appStr, array(
      'methods' => 'GET',
      'callback' => array($this, 'verify_route'),
    ));
    register_rest_route($namespace, 'failed', array(
      'methods' => 'GET',
      'callback' => array($this, 'fail_route'),
    ));
  }

  function fail_route()
  {
    header('Content-Type: text/html; charset=utf-8');
    echo '
			<!DOCTYPE html>
			<html>
				<head></head>
				<body>
					<h1>Invalid OAuth Configuration</h1>
					<script>
						setTimeout(function(){window.location.href = "/";},5000);
					</script>
				</body>
			</html>
		';
  }

  function handle_failed_scenario($flag = false)
  {
    if ($flag == true) {
      $socialSettings = WebtonativeSocialLoginUtill::getSocialSettings(true);
      $redirect_uri = $socialSettings['redirectUri'];
      header('Location: ' . redirect_uri);
    } else {
      header('Location: /wp-json/webtonative/social-login/failed');
    }
    die();
  }

  function auth_route($data)
  {
    if (is_user_logged_in()) {
      return $this->handle_failed_scenario(true);
    }
    $app = $data['app'];
    $socialApps = WebtonativeSocialLoginUtill::getSocialApps();
    if ($socialApps[$app]['enabled'] !== true) {
      return $this->handle_failed_scenario(true);
    }
    if ($app == 'google') {
      $redirect_uri =
        static::$GOOGLE_OAUTH_URI .
        '?redirect_uri=' .
        get_bloginfo('wpurl') .
        static::$GOOGLE_REDIRECT_URI .
        '&response_type=code&client_id=' .
        $socialApps['google']['clientId'] .
        '&scope=email+profile&access_type=offline&approval_prompt=force';
      header('Location: ' . $redirect_uri);
      die();
    } elseif ($app == 'facebook') {
      $redirect_uri =
        static::$FACEBOOK_OAUTH_URI .
        '?client_id=' .
        $socialApps['facebook']['appId'] .
        '&redirect_uri=' .
        get_bloginfo('wpurl') .
        static::$FACEBOOK_REDIRECT_URI .
        '&auth_type=rerequest&scope=email, public_profile';
      header('Location: ' . $redirect_uri);
      die();
    }
    return $this->handle_failed_scenario(true);
  }
  function verify_route($data)
  {
    if (is_user_logged_in()) {
      return $this->handle_failed_scenario(true);
    }
    $app = $data['app'];
    $socialApps = WebtonativeSocialLoginUtill::getSocialApps();
    if ($socialApps[$app]['enabled'] !== true) {
      return $this->handle_failed_scenario(true);
    }
    if ($app == 'google') {
      $code = !empty($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
      $idToken = !empty($_GET['id_token']) ? sanitize_text_field($_GET['id_token']) : '';
      if (!empty($code)) {
        $args = array(
          'method' => 'POST',
          'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
          ),
          'body' =>
            'code=' .
            $code .
            '&client_id=' .
            $socialApps['google']['clientId'] .
            '&client_secret=' .
            $socialApps['google']['clientSecret'] .
            '&redirect_uri=' .
            get_bloginfo('wpurl') .
            static::$GOOGLE_REDIRECT_URI .
            '&grant_type=authorization_code&code_verifier',
        );
        $xml = wp_remote_retrieve_body(wp_remote_post(static::$GOOGLE_OAUTH_TOKEN_URI, $args));
        $resp = json_decode($xml, true);
        $idToken = $resp['id_token'];
      }

      if (!empty($idToken)) {
        $xml = wp_remote_retrieve_body(wp_remote_get(static::$GOOGLE_TOKEN_INFO_URI . '?id_token=' . $idToken));
        $resp = json_decode($xml, true);
        $aud = $resp['aud'];
        if ($aud !== $socialApps['google']['clientId']) {
          return $this->handle_failed_scenario(false);
        }
        $user_email = $resp['email'];
        if (empty($user_email)) {
          return $this->handle_failed_scenario(false);
        }
        return $this->createOrLoginUser(array(
          'user_login' => '',
          'user_email' => $user_email,
          'display_name' => $resp['name'],
          'first_name' => $resp['given_name'],
          'last_name' => $resp['family_name'],
        ));
      }
    } elseif ($app == 'facebook') {
      $code = !empty($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
      $access_token = !empty($_GET['access_token']) ? sanitize_text_field($_GET['access_token']) : '';
      if (!empty($code)) {
        $url =
          static::$FACEBOOK_ACCESS_TOKEN_URI .
          '?client_id=' .
          $socialApps['facebook']['appId'] .
          '&redirect_uri=' .
          get_bloginfo('wpurl') .
          static::$FACEBOOK_REDIRECT_URI .
          '&client_secret=' .
          $socialApps['facebook']['appSecret'] .
          '&code=' .
          $code;
        $xml = wp_remote_retrieve_body(wp_remote_get($url));
        $resp = json_decode($xml, true);
        $access_token = $resp['access_token'];
      }
      if (!empty($access_token)) {
        $xml = wp_remote_retrieve_body(
          wp_remote_get(static::$FACEBOOK_USER_GQL_URL . '?fields=id,name,first_name,last_name,email,gender,location,picture&access_token=' . $access_token)
        );
        $resp = json_decode($xml, true);
        $user_email = $resp['email'];
        if (empty($user_email)) {
          return $this->handle_failed_scenario(false);
        }
        return $this->createOrLoginUser(array(
          'user_login' => '',
          'user_email' => $user_email,
          'display_name' => $resp['name'],
          'first_name' => $resp['first_name'],
          'last_name' => $resp['last_name'],
        ));
      }
    } elseif ($app == 'apple') {
      $idToken = !empty($_GET['id_token']) ? sanitize_text_field($_GET['id_token']) : '';
      $bundleId = !empty($_GET['client_id']) ? sanitize_text_field($_GET['client_id']) : '';
      $firstName = !empty($_GET['firstName']) ? sanitize_text_field($_GET['firstName']) : '';
      $lastName = !empty($_GET['lastName']) ? sanitize_text_field($_GET['lastName']) : '';
      list($headb64, $bodyb64, $cryptob64) = explode('.', $idToken);
      $header = WebtonativeJWT::jsonDecode(WebtonativeJWT::urlsafeB64Decode($headb64));

      $auth_keys = wp_remote_retrieve_body(wp_remote_get(static::$APPLE_BASE_URL . static::$JWKS_APPLE_URI));

      $public_keys = WebtonativeJWT::parseKeySet(json_decode($auth_keys, true));
      $kid = $header->kid;

      $decoded = WebtonativeJWT::decode($idToken, $public_keys[$kid], array('RS256'));

      if ($decoded->iss != static::$APPLE_BASE_URL) {
        return $this->handle_failed_scenario(false);
      }
      if ($decoded->aud != $bundleId) {
        return $this->handle_failed_scenario(false);
      }
      if (empty($decoded->email)) {
        return $this->handle_failed_scenario(false);
      }
      $fullName = '';
      if (!empty($firstName) && !empty($firstName)) {
        $fullName = $firstName + ' ' + $lastName;
      } elseif (!empty($firstName)) {
        $fullName = $firstName;
      } elseif (!empty($lastName)) {
        $fullName = $lastName;
      }
      return $this->createOrLoginUser(array(
        'user_login' => '',
        'user_email' => $decoded->email,
        'display_name' => $fullName,
        'first_name' => $firstName,
        'last_name' => $lastName,
      ));
    }
  }

  function createOrLoginUser($data)
  {
    $user_login = $data['user_login'];
    $user_email = $data['user_email'];
    $display_name = $data['display_name'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];

    if (empty($user_email)) {
      return $this->handle_failed_scenario(false);
    }
    if (email_exists($user_email)) {
      $user = get_user_by('email', $user_email);
      $user_id = $user->ID;
    } else {
      $userdata = array(
        'user_login' => $user_login,
        'user_email' => $user_email,
        'user_pass' => wp_generate_password(10, false),
        'display_name' => $display_name,
        'first_name' => $first_name,
        'last_name' => $last_name,
      );
      global $wpdb;
      if (!empty($userdata['user_login'])) {
        $user_name_user_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users where user_login = %s", $userdata['user_login']));
      }

      if (isset($user_name_user_id) || empty($user_login)) {
        $email_array = explode('@', $user_email);
        $user_name = $email_array[0];
        $user_name_user_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users where user_login = %s", $user_name));
        $i = 1;
        while (!empty($user_name_user_id)) {
          $uname = $user_name . '_' . $i;
          $user_name_user_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users where user_login = %s", $uname));
          $i++;
          if (empty($user_name_user_id)) {
            $userdata['user_login'] = $uname;
            $username = $uname;
          }
        }

        if ($i == 1) {
          $userdata['user_login'] = $user_name;
        }

        if (isset($user_name_user_id)) {
          return $this->handle_failed_scenario(false);
        }
      }
      $user_id = wp_insert_user($userdata);
    }
    $socialSettings = WebtonativeSocialLoginUtill::getSocialSettings(true);
    $redirect_uri = $socialSettings['redirectUri'];
    $remember = $socialSettings['remember'];
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, $remember);
    header('Location: ' . $redirect_uri);
    die();
  }
}

new WebtonativeSocialRestController();
