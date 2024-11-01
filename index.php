<?php
/*
  Plugin Name: webtonative
  Description: webtonative Plugin
  Version: 2.2
  Author: webtonative
*/

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

define('PLUGIN_PATH', plugin_dir_path(__FILE__));

if (is_admin()) {
  require_once __DIR__ . '/admin/index.php';
} else {
  require_once __DIR__ . '/website/index.php';
}
