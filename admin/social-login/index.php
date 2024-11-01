<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

require_once __DIR__ . '/../../common/social-login/rest.php';

class WebtonativeSocialLogin
{
  public function __construct()
  {
    if (!empty($_POST['justsubmitted']) && $_POST['justsubmitted'] == 'true') {
      $this->handle_submit();
    }
    add_submenu_page('webtonative-settings', 'Social Login Settings', 'Social Login', 'manage_options', 'webtonative-settings', array($this, 'html'));
    add_action('admin_init', array($this, 'settings'));
  }

  public function settings()
  {
    $this->facebook();
    $this->google();
    $this->config();
  }

  public function facebook()
  {
    $socialApps = WebtonativeSocialLoginUtill::getSocialApps();

    $fbAppId = $socialApps['facebook']['appId'];
    $fbAppSecret = $socialApps['facebook']['appSecret'];
    $enableFbLogin = $socialApps['facebook']['enabled'];

    add_settings_section('webtonative_facebook_login_section', 'Facebook Login', null, 'webtonative-settings');

    add_settings_field('wton_facebook_login_enabled', 'Enable facebook login', array($this, 'checkbox'), 'webtonative-settings', 'webtonative_facebook_login_section', array(
      'name' => 'wton_facebook_login_enabled',
      'description' => null,
      'value' => $enableFbLogin,
    ));

    add_settings_field('wton_facebook_login_app_id', 'Facebook app id', array($this, 'text'), 'webtonative-settings', 'webtonative_facebook_login_section', array(
      'name' => 'wton_facebook_login_app_id',
      'description' => null,
      'value' => $fbAppId,
    ));

    add_settings_field('wton_facebook_login_app_secret', 'Facebook app secret', array($this, 'text'), 'webtonative-settings', 'webtonative_facebook_login_section', array(
      'name' => 'wton_facebook_login_app_secret',
      'description' => null,
      'value' => $fbAppSecret,
    ));
  }

  public function google()
  {
    $socialApps = WebtonativeSocialLoginUtill::getSocialApps();

    $googleClientId = $socialApps['google']['clientId'];
    $googleClientSecret = $socialApps['google']['clientSecret'];
    $enableGoogleLogin = $socialApps['google']['enabled'];

    add_settings_section('webtonative_google_login_section', 'Google Login', null, 'webtonative-settings');

    add_settings_field('wton_google_login_enabled', 'Enable google login', array($this, 'checkbox'), 'webtonative-settings', 'webtonative_google_login_section', array(
      'name' => 'wton_google_login_enabled',
      'description' => null,
      'value' => $enableGoogleLogin,
    ));

    add_settings_field('wton_google_login_client_id', 'Google client id', array($this, 'text'), 'webtonative-settings', 'webtonative_google_login_section', array(
      'name' => 'wton_google_login_client_id',
      'description' => null,
      'value' => $googleClientId,
    ));

    add_settings_field('wton_google_login_client_secret', 'Google client secret', array($this, 'text'), 'webtonative-settings', 'webtonative_google_login_section', array(
      'name' => 'wton_google_login_client_secret',
      'description' => null,
      'value' => $googleClientSecret,
    ));
  }

  public function config()
  {
    $socialSettings = WebtonativeSocialLoginUtill::getSocialSettings();
    $redirectUri = $socialSettings['redirectUri'];
    $remember = $socialSettings['remember'];

    add_settings_section('webtonative_social_login_config_section', 'Config', null, 'webtonative-settings');

    add_settings_field('wton_social_login_redirect_uri', 'Redirect uri', array($this, 'text'), 'webtonative-settings', 'webtonative_social_login_config_section', array(
      'name' => 'wton_social_login_redirect_uri',
      'description' => null,
      'value' => $redirectUri,
    ));

    add_settings_field('wton_social_login_remember', 'Remember me', array($this, 'checkbox'), 'webtonative-settings', 'webtonative_social_login_config_section', array(
      'name' => 'wton_social_login_remember',
      'description' => null,
      'value' => $remember,
    ));
  }

  public function checkbox($args)
  {
    $name = $args['name'];
    $value = $args['value'];
    $description = isset($args['description']) ? $args['description'] : null;
    ?>
        <input type="checkbox" name="<?php echo $name; ?>" value="enabled" <?php checked($value, true); ?> />
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
    $value = $args['value'];
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

  public function handle_submit()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'wton-social-settings_nonce') or !current_user_can('manage_options')) { ?>
            <div class="error">
                <p>Sorry, you do not have permission to perform that action.</p>
            </div>
      <?php return;}
    if (
      !empty($_POST['wton_google_login_enabled']) &&
      !empty($_POST['wton_google_login_client_id']) &&
      !empty($_POST['wton_google_login_client_secret']) &&
      $_POST['wton_google_login_enabled'] == 'enabled'
    ) {
      update_option('wton_google_login_config', array(
        'clientId' => sanitize_text_field($_POST['wton_google_login_client_id']),
        'clientSecret' => sanitize_text_field($_POST['wton_google_login_client_secret']),
        'enabled' => 1,
      ));
    } else {
      update_option('wton_google_login_config', array(
        'enabled' => 0,
        'clientId' => '',
        'clientSecret' => '',
      ));
    }

    if (
      !empty($_POST['wton_facebook_login_enabled']) &&
      !empty($_POST['wton_facebook_login_app_id']) &&
      !empty($_POST['wton_facebook_login_app_secret']) &&
      $_POST['wton_facebook_login_enabled'] == 'enabled'
    ) {
      update_option('wton_facebook_login_config', array(
        'appId' => sanitize_text_field($_POST['wton_facebook_login_app_id']),
        'appSecret' => sanitize_text_field($_POST['wton_facebook_login_app_secret']),
        'enabled' => 1,
      ));
    } else {
      update_option('wton_facebook_login_config', array(
        'enabled' => 0,
        'appId' => '',
        'serverToken' => '',
      ));
    }
    $socialSettings = array();
    if (!empty($_POST['wton_social_login_redirect_uri'])) {
      $socialSettings['redirectUri'] = sanitize_text_field($_POST['wton_social_login_redirect_uri']);
    }
    $socialSettings['remember'] = !empty($_POST['wton_social_login_remember']) && $_POST['wton_social_login_remember'] == 'enabled' ? true : false;
    update_option('wton_social_login_settings', $socialSettings);?>
        <div class="updated">
            <p>Settings Saved</p>
        </div>
    <?php
  }

  public function html()
  {
    ?>
        <div class="wrap">
            <h1>
                Social Login Settings
            </h1>
            <form method="post">
               <input type="hidden" name="justsubmitted" value="true" />
                <?php
                wp_nonce_field('wton-social-settings_nonce', 'nonce');
                settings_fields('webtonative_social_login_option_grp');
                do_settings_sections('webtonative-settings');
                ?>
                <p>
                    For adding social login to custom theme page, use Shortcode <b>[webtonative_social_login]</b>
                </p>
                <?php submit_button(); ?>
            </form>
            <script>
                const facebookEnabled = document.querySelector('[name="wton_facebook_login_enabled"]');
                const googleEnabled = document.querySelector('[name="wton_google_login_enabled"]');
                const facebookAppId = document.querySelector('[name="wton_facebook_login_app_id"]');
                const facebookAppSecret = document.querySelector('[name="wton_facebook_login_app_secret"]');
                const googleClientId = document.querySelector('[name="wton_google_login_client_id"]');
                const googleClientSecret = document.querySelector('[name="wton_google_login_client_secret"]');

                function facebookFields() {
                    if (facebookEnabled.checked) {
                        facebookAppId.disabled = false;
                        facebookAppSecret.disabled = false;
                    } else {
                        facebookAppId.disabled = true;
                        facebookAppSecret.disabled = true;
                    }
                }

                function googleFields() {
                    if (googleEnabled.checked) {
                        googleClientId.disabled = false;
                        googleClientSecret.disabled = false;
                    } else {
                        googleClientId.disabled = true;
                        googleClientSecret.disabled = true;
                    }
                }

                facebookEnabled.addEventListener('change', facebookFields);
                googleEnabled.addEventListener('change', googleFields);
                facebookFields();
                googleFields();
            </script>
        </div>
<?php
  }
}
