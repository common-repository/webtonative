<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

require_once __DIR__ . '/biometric.php';
require_once __DIR__ . '/social.php';
require_once __DIR__ . '/iap.php';
require_once __DIR__ . '/push-notification.php';

class WebtonativePluginWebsite
{
  private WebtonativeSocialLoginWebsite $social;
  private WebtonativeBiometricWebsite $biometric;
  private WebtonativeIAPWebsite $iap;
  private WebtonativePushNotificationWebsite $pushNotification;

  function __construct()
  {
    wp_enqueue_script('webtonative', plugin_dir_url(__FILE__) . 'scripts/webtonative.min.js', array(), '1.0.50', true);
    $this->social = new WebtonativeSocialLoginWebsite();
    // $this->biometric = new WebtonativeBiometricWebsite();
    $this->iap = new WebtonativeIAPWebsite();
    $this->pushNotification = new WebtonativePushNotificationWebsite();
  }
}

new WebtonativePluginWebsite();
