<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

class WebtonativeSocialLoginUtill
{
  public static function getSocialApps()
  {
    $fbConfig = get_option('wton_facebook_login_config');
    $googleConfig = get_option('wton_google_login_config');

    $fbAppId = !empty($fbConfig) && !empty($fbConfig['appId']) ? $fbConfig['appId'] : '';
    $fbAppSecret = !empty($fbConfig) && !empty($fbConfig['appSecret']) ? $fbConfig['appSecret'] : '';
    $enableFbLogin = !empty($fbConfig) && !empty($fbConfig['enabled']) ? ($fbConfig['enabled'] === 1 ? true : false) : false;

    $googleClientId = !empty($googleConfig) && !empty($googleConfig['clientId']) ? $googleConfig['clientId'] : '';
    $googleClientSecret = !empty($googleConfig) && !empty($googleConfig['clientSecret']) ? $googleConfig['clientSecret'] : '';
    $enableGoogleLogin = !empty($googleConfig) && !empty($googleConfig['enabled']) ? ($googleConfig['enabled'] === 1 ? true : false) : false;

    $socialApps = array(
      'google' => array(
        'enabled' => $enableGoogleLogin,
        'clientId' => $googleClientId,
        'clientSecret' => $googleClientSecret,
      ),
      'facebook' => array(
        'enabled' => $enableFbLogin,
        'appId' => $fbAppId,
        'appSecret' => $fbAppSecret,
      ),
      'apple' => array(
        'enabled' => $enableGoogleLogin || $enableFbLogin,
      ),
    );
    return $socialApps;
  }

  public static function getSocialSettings($default = false)
  {
    $socialSettings = get_option('wton_social_login_settings');
    $redirectUri = !empty($socialSettings) && !empty($socialSettings['redirectUri']) ? $socialSettings['redirectUri'] : '';
    $remember = !empty($socialSettings) && $socialSettings['remember'] == true ? true : false;
    if ($default == true) {
      if (empty($redirectUri)) {
        $redirectUri = '/';
      }
    }
    return array(
      'redirectUri' => $redirectUri,
      'remember' => $remember,
    );
  }
}
