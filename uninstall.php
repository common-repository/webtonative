<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

// social login
delete_option('wton_google_login_config');
delete_option('wton_facebook_login_config');
delete_option('wton_social_login_settings');

// biometric
delete_option('wton_enable_biometric_login');

// push notification
delete_option('wton_enable_push_notification');
delete_option('wton_one_signal_app_id');
delete_option('wton_push_notification_rest_key');
