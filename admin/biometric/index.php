<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

class WebtonativeBiometric
{
  public function __construct()
  {
    add_submenu_page('webtonative-settings', 'Biometrics Settings', 'Biometrics', 'manage_options', 'webtonative-settings-bio', array($this, 'html'));
    add_action('admin_init', array($this, 'settings'));
  }

  function settings()
  {
    add_settings_section('webtonative_biometric_section', null, null, 'webtonative-settings-bio');

    add_settings_field('wton_enable_biometric_login', 'Enable Biometrics', array($this, 'checkbox'), 'webtonative-settings-bio', 'webtonative_biometric_section', array(
      'name' => 'wton_enable_biometric_login',
      'description' => 'For adding biometric login to a page, use Shortcode <b>[webtonative_biometric_login]</b>',
    ));

    register_setting('webtonative_biometric_option_grp', 'wton_enable_biometric_login', array(
      'type' => 'boolean',
      'default' => false,
    ));
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

  function html()
  {
    ?>
        <div class="wrap">
            <h1>
                Biometrics Settings
            </h1>
            <form action="options.php" method="post">
                <?php
                settings_errors();
                settings_fields('webtonative_biometric_option_grp');
                do_settings_sections('webtonative-settings-bio');
                submit_button();?>
            </form>
        </div>
        <?php
  }
}
