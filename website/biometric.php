<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

class WebtonativeBiometricWebsite
{
  public function __construct()
  {
    add_shortcode('webtonative_biometric_login', array($this, 'shortcode'));
  }

  function enqueue_biometric_scripts()
  {
    if (!is_user_logged_in()) {
      return;
    }

    $wton_enable_biometric_login = get_option('wton_enable_biometric_login', false);
    if (!$wton_enable_biometric_login) {
      return;
    }
    wp_enqueue_script('webtonative-biometric', plugins_url('scripts/biometric.js', __FILE__), array('webtonative'), '2', true);
  }

  function renderBiometricOption()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_biometric_scripts'));
    echo '<div id="wton-biometric-wrapper"></div>';
  }

  function shortcode()
  {
    ob_start();
    $this->renderBiometricOption();
    $output = ob_get_clean();
    return $output;
  }
}
