<?php
/**
 * Plugin Name: Stripe Embedded Checkout (Simple)
 */

require_once __DIR__ . '/includes/stripe/stripe-php/init.php';

if (!defined('ABSPATH')) exit;

// Load admin settings page
if (is_admin()) {
    require_once __DIR__ . '/includes/admin/class-settings-page.php';
    new SECWP_Settings_Page();
}

/**
 * Get Stripe settings from options
 */
function secwp_get_settings() {
    $defaults = array(
        'test_mode' => true,
        'test_secret_key' => '',
        'test_publishable_key' => '',
        'live_secret_key' => '',
        'live_publishable_key' => '',
        'return_url' => '',
        'price_id' => '',
    );

    $settings = get_option('secwp_settings', $defaults);
    return wp_parse_args($settings, $defaults);
}

/**
 * Get the appropriate secret key based on test mode
 */
function secwp_get_secret_key() {
    $settings = secwp_get_settings();
    $is_test_mode = !empty($settings['test_mode']);
    
    if ($is_test_mode) {
        return !empty($settings['test_secret_key']) ? $settings['test_secret_key'] : '';
    } else {
        return !empty($settings['live_secret_key']) ? $settings['live_secret_key'] : '';
    }
}

/**
 * Get the appropriate publishable key based on test mode
 */
function secwp_get_publishable_key() {
    $settings = secwp_get_settings();
    $is_test_mode = !empty($settings['test_mode']);
    
    if ($is_test_mode) {
        return !empty($settings['test_publishable_key']) ? $settings['test_publishable_key'] : '';
    } else {
        return !empty($settings['live_publishable_key']) ? $settings['live_publishable_key'] : '';
    }
}

/**
 * Get the configured return URL
 */
function secwp_get_return_url() {
    $settings = secwp_get_settings();
    $return_url = !empty($settings['return_url']) ? $settings['return_url'] : '';
    
    // If no return URL is set, use a default
    if (empty($return_url)) {
        $return_url = home_url('/?session_id={CHECKOUT_SESSION_ID}');
    }
    
    return $return_url;
}

/**
 * Get the configured price ID
 */
function secwp_get_price_id() {
    $settings = secwp_get_settings();
    return !empty($settings['price_id']) ? $settings['price_id'] : '';
}

add_action('rest_api_init', function () {
  register_rest_route('stripe-embedded/v1', '/create-session', [
    'methods'  => 'POST',
    'callback' => 'secwp_stripe_create_session',
    'permission_callback' => '__return_true', // public endpoint for anonymous checkout
  ]);
});

function secwp_stripe_create_session(\WP_REST_Request $request) {
  $secret_key = secwp_get_secret_key();
  $price_id = secwp_get_price_id();
  $return_url = secwp_get_return_url();

  if (empty($secret_key)) {
    return new \WP_REST_Response(['error' => 'Stripe secret key not configured'], 500);
  }

  if (empty($price_id)) {
    return new \WP_REST_Response(['error' => 'Product price ID not configured'], 500);
  }

  \Stripe\Stripe::setApiKey($secret_key);

  try {
    $session = \Stripe\Checkout\Session::create([
      'mode' => 'payment',
      'ui_mode' => 'embedded',
      'line_items' => [[
        'price' => $price_id,
        'quantity' => 1,
      ]],
      'return_url' => $return_url,
    ]);

    return new \WP_REST_Response([
      'clientSecret' => $session->client_secret,
    ], 200);

  } catch (\Exception $e) {
    return new \WP_REST_Response(['error' => $e->getMessage()], 500);
  }
}

add_action('wp_enqueue_scripts', function () {
    // Stripe.js (client-side library)
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], null, true);
  
    wp_enqueue_script(
      'secwp-embedded-checkout',
      plugin_dir_url(__FILE__) . 'assets/embedded-checkout.js',
      ['stripe-js'],
      '1.0.0',
      true
    );
  
    wp_localize_script('secwp-embedded-checkout', 'secwp', [
      'publishableKey' => secwp_get_publishable_key(),
      'createSessionUrl' => esc_url_raw(rest_url('stripe-embedded/v1/create-session')),
    ]);
  });
  
  add_shortcode('secwp_embedded_checkout', function () {
    return 'SHORTCODE-OK <div id="secwp"></div>';
  });
  