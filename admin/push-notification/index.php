<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

class WebtonativePushNotification
{
  public function __construct()
  {
    add_submenu_page('webtonative-settings', 'Notification', 'Push Notification', 'manage_options', 'webtonative-settings-push-notification', array($this, 'html'));
    add_action('admin_init', array($this, 'settings'));
    add_action('woocommerce_order_status_changed', array($this, 'sendOneSlignalNotification'), 10, 4);
  }

  public function sendOneSlignalNotification($id, $status_transition_from, $status_transition_to, $that)
  {
    $order = wc_get_order($id);
    $user = $order->get_user();
    $userId = $user->ID;
    $notification_enabled = get_option('wton_enable_push_notification', false);
    $oneSignalAppId = get_option('wton_one_signal_app_id', null);
    $restApi_Key = get_option('wton_push_notification_rest_key', null);
    $wton_notification_key = get_option('wton_notification_key', null);

    if (!$wton_notification_key or !$oneSignalAppId or !$restApi_Key or !$notification_enabled) {
      return;
    }

    $custom_message = get_option('wton_push_notification_event_' . $status_transition_to, null);

    if (empty($custom_message)) {
      $custom_message = 'Your order status has been changed to ' . $status_transition_to;
      return;
    }

    // hased user id with key
    $extenalUserId = hash_hmac('sha256', $userId, $wton_notification_key);

    // call one signal api
    $url = 'https://onesignal.com/api/v1/notifications';
    $data = array(
      'app_id' => $oneSignalAppId,
      'contents' => array('en' => $custom_message),
      'include_external_user_ids' => array($extenalUserId),
    );

    $result = wp_safe_remote_post($url, array(
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic ' . $restApi_Key,
      ),
      'body' => json_encode($data),
      'data_format' => 'body',
    ));
  }

  public function settings()
  {
    add_settings_section('webtonative_push_notification_section', null, null, 'webtonative-settings-push-notification');

    add_settings_field(
      'wton_enable_push_notification',
      'Enable Push Notification',
      array($this, 'checkbox'),
      'webtonative-settings-push-notification',
      'webtonative_push_notification_section',
      array(
        'name' => 'wton_enable_push_notification',
      )
    );
    register_setting('webtonative_push_notification_grp', 'wton_enable_push_notification', array(
      'type' => 'boolean',
      'default' => false,
    ));

    add_settings_field('wton_one_signal_app_id', 'OneSignal Id', array($this, 'text'), 'webtonative-settings-push-notification', 'webtonative_push_notification_section', array(
      'name' => 'wton_one_signal_app_id',
    ));

    register_setting('webtonative_push_notification_grp', 'wton_one_signal_app_id', array(
      'type' => 'string',
      'default' => '',
      'sanitize_callback' => 'sanitize_text_field',
    ));

    add_settings_field(
      'wton_push_notification_rest_key',
      'Rest Key',
      array($this, 'text'),
      'webtonative-settings-push-notification',
      'webtonative_push_notification_section',
      array(
        'name' => 'wton_push_notification_rest_key',
      )
    );

    register_setting('webtonative_push_notification_grp', 'wton_push_notification_rest_key', array(
      'type' => 'string',
      'default' => '',
      'sanitize_callback' => 'sanitize_text_field',
    ));

    // message customisation

    add_settings_section('webtonative_push_notification_messages', 'Customize Messages', null, 'webtonative-settings-push-notification');

    $statuses = wc_get_order_statuses();

    foreach ($statuses as $key => $value) {
      $key = str_replace('wc-', '', $key);
      add_settings_field(
        'wton_push_notification_event_' . $key,
        'Message for ' . $value,
        array($this, 'text'),
        'webtonative-settings-push-notification',
        'webtonative_push_notification_messages',
        array(
          'name' => 'wton_push_notification_event_' . $key,
        )
      );

      register_setting('webtonative_push_notification_grp', 'wton_push_notification_event_' . $key, array(
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
      ));
    }

    update_option('wton_notification_key', 'w2n_notification_key');
  }

  public function checkbox($args)
  {
    $name = $args['name'];
    $value = get_option($name, false);
    $description = isset($args['description']) ? $args['description'] : null;
    ?>
    <input type="checkbox" name="<?php echo $name; ?>" value="1" <?php checked($value, true); ?> />
    <?php if ($description): ?>
      <p class="description">
        <?php echo $description; ?>
      </p>
    <?php endif; ?>
  <?php
  }

  public function text($args)
  {
    $name = $args['name'];
    $value = get_option($name, '');
    $description = isset($args['description']) ? $args['description'] : null;
    ?>
    <input type="text" name="<?php echo $name; ?>" value="<?php echo esc_attr($value); ?>" />
    <?php if ($description): ?>
      <p class="description">
        <?php echo $description; ?>
      </p>
    <?php endif; ?>
  <?php
  }

  public function html()
  {
    ?>
    <div class="wrap">
      <h1>
        Push Notification Settings
      </h1>
      <form action="options.php" method="post">
        <?php
        settings_errors();
        settings_fields('webtonative_push_notification_grp');
        do_settings_sections('webtonative-settings-push-notification');
        submit_button();?>
      </form>
    </div>
<?php
  }
}

?>
