<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

require_once __DIR__ . '/../common/social-login/rest.php';

class WebtonativeSocialLoginWebsite
{
  function __construct()
  {
    add_action('login_form', array($this, 'renderSocialLoginApps'));

    wp_enqueue_style('webtonative-css', plugin_dir_url(__FILE__) . 'styles/style.css');
    wp_enqueue_script('webtonative-social-login', plugin_dir_url(__FILE__) . 'scripts/social-login.js', array('webtonative'), '2', true);
    add_shortcode('webtonative_social_login', array($this, 'shortcode'));
  }

  function renderIcon($app)
  {
    if ($app == 'google') {
      return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="35px" height="35px" viewBox="0 0 70 70" style="padding-left: 8%;"><defs><path id="a" d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"></path></defs><clipPath id="b"><use xlink:href="#a" overflow="visible"></use></clipPath><path clip-path="url(#b)" fill="#FBBC05" d="M0 37V11l17 13z"></path><path clip-path="url(#b)" fill="#EA4335" d="M0 11l17 13 7-6.1L48 14V0H0z"></path><path clip-path="url(#b)" fill="#34A853" d="M0 37l30-23 7.9 1L48 0v48H0z"></path><path clip-path="url(#b)" fill="#4285F4" d="M48 48L17 24l-4-3 35-10z"></path></svg>';
    }
    if ($app == 'facebook') {
      return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25px" height="25px" viewBox="0 0 25 25" fill="#ffffff"><g id="surface1"><path style=" stroke:none;" d="M 24.609375 12.5 C 24.609375 5.8125 19.1875 0.390625 12.5 0.390625 C 5.8125 0.390625 0.390625 5.8125 0.390625 12.5 C 0.390625 18.542969 4.820312 23.554688 10.609375 24.460938 L 10.609375 16 L 7.53125 16 L 7.53125 12.5 L 10.609375 12.5 L 10.609375 9.832031 C 10.609375 6.796875 12.414062 5.121094 15.179688 5.121094 C 16.507812 5.121094 17.890625 5.359375 17.890625 5.359375 L 17.890625 8.335938 L 16.367188 8.335938 C 14.859375 8.335938 14.390625 9.269531 14.390625 10.226562 L 14.390625 12.5 L 17.75 12.5 L 17.214844 16 L 14.390625 16 L 14.390625 24.460938 C 20.179688 23.554688 24.609375 18.542969 24.609375 12.5 Z M 24.609375 12.5 "/></g></svg>';
    }
    if ($app == 'apple') {
      return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25px" height="33px" viewBox="0 0 25 33" fill="#ffffff"><g id="surface1"><path style=" stroke:none;" d="M 20.75 17.320312 C 20.734375 14.953125 21.816406 13.167969 24.003906 11.851562 C 22.78125 10.117188 20.929688 9.164062 18.488281 8.976562 C 16.179688 8.796875 13.652344 10.3125 12.726562 10.3125 C 11.75 10.3125 9.511719 9.042969 7.753906 9.042969 C 4.121094 9.101562 0.261719 11.910156 0.261719 17.628906 C 0.261719 19.316406 0.574219 21.0625 1.199219 22.863281 C 2.03125 25.226562 5.039062 31.027344 8.175781 30.929688 C 9.816406 30.890625 10.976562 29.777344 13.113281 29.777344 C 15.183594 29.777344 16.257812 30.929688 18.085938 30.929688 C 21.25 30.886719 23.972656 25.613281 24.765625 23.242188 C 20.519531 21.261719 20.75 17.441406 20.75 17.320312 Z M 17.0625 6.734375 C 18.839844 4.648438 18.679688 2.746094 18.625 2.0625 C 17.058594 2.152344 15.242188 3.121094 14.207031 4.3125 C 13.066406 5.589844 12.394531 7.167969 12.539062 8.945312 C 14.238281 9.074219 15.789062 8.210938 17.0625 6.734375 Z M 17.0625 6.734375 "/></g></svg>';
    }
    return '';
  }

  function renderText($app)
  {
    return 'Sign In With ' . ucfirst($app);
  }

  function renderApp($app, $appData)
  {
    if ($appData['enabled'] !== true) {
      return;
    }
    echo '<div onclick="wtonSocialLogin(\'' .
      $app .
      '\')" class="btnL btn-social btn-' .
      $app .
      '"><span class="social-login-icon">' .
      $this->renderIcon($app) .
      '</span><span class="social-login-text">' .
      $this->renderText($app) .
      '</span></div>';
  }

  function renderSocialLoginApps()
  {
    if (is_user_logged_in()) {
      return;
    }
    $socialApps = WebtonativeSocialLoginUtill::getSocialApps();
    echo '<div class="wton-social-wrapper">';
    echo '<script> var wton_wp_site_url="' . get_site_url() . '";</script>';
    foreach ($socialApps as $app => $appData) {
      $this->renderApp($app, $appData);
    }
    echo '</div>';
  }

  function shortcode()
  {
    ob_start();
    $this->renderSocialLoginApps();
    $output = ob_get_clean();
    return $output;
  }
}
