<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

class WebtonativePushNotificationWebsite
{
  public function __construct()
  {
    add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
  }

  public function load_scripts()
  {
    if (!is_user_logged_in()) {
      return;
    }
    $current_user = wp_get_current_user();
    $wton_notification_key = get_option('wton_notification_key', null);
    if (!$wton_notification_key) {
      return;
    }

    // hased user id with key
    $extenalUserId = hash_hmac('sha256', $current_user->ID, $wton_notification_key);
    wp_register_script('webtonative-push-notification', plugins_url('scripts/push-notification.js', __FILE__), array('webtonative'), '', true);
    wp_localize_script('webtonative-push-notification', 'webtonative_push_notification_settings', array(
      'external_user_id' => $extenalUserId,
    ));
    wp_enqueue_script('webtonative-push-notification');
  }
}
