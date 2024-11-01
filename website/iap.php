<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly


require_once __DIR__ . '/../admin/iap/rest.php';


class WebtonativeIAPWebsite
{
  public function __construct()
  {
    add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
    add_shortcode('webtonative_buy_now', array($this, 'shortcode'));
    new WebtonativeIAPRestController();
  }

  public function load_scripts()
  {
    wp_register_script('webtonative-iap', plugins_url('scripts/woocommerce.js', __FILE__), array('webtonative', 'jquery'), '', true);
    wp_localize_script('webtonative-iap', 'webtonative_payment_settings', array(
      'rest_url' => get_rest_url(null, 'webtonative/iap'),
      'nonce' => wp_create_nonce('wp_rest'),
    ));
    wp_enqueue_script('webtonative-iap');
  }
  public function renderBuyNow()
  {
    $product_id = get_the_ID(); ?>
    <button type="submit" name="add-to-cart" value="<?php echo $product_id; ?>" class="single_add_to_cart_button button alt wp-element-button">Buy Now</button>
<?php
  }
  public function shortcode()
  {
    ob_start();
    $this->renderBuyNow();
    $output = ob_get_clean();
    return $output;
  }
}
