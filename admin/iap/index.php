<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

require_once __DIR__ . '/woo-commerce.php';
require_once __DIR__ . '/rest.php';

class WebtonativeIAP
{
  public function __construct()
  {
    new WebtonativeIAPRestController();
  }
}
