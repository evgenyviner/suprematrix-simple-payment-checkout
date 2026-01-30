<?php
/**
 * Plugin Name: Stripe Embedded Checkout (Simple)
 */

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/vendor/autoload.php';

add_action('rest_api_init', function () {
  register_rest_route('stripe-embedded/v1', '/create-session', [
    'methods'  => 'POST',
    'callback' => 'zora_stripe_create_session',
    'permission_callback' => '__return_true', // public endpoint for anonymous checkout
  ]);
});

function zora_stripe_create_session(\WP_REST_Request $request) {
  if (!defined('STRIPE_SECRET_KEY')) {
    return new \WP_REST_Response(['error' => 'Stripe secret key not configured'], 500);
  }

  // IMPORTANT: Only allow known Price IDs from your server.
  $price_id = 'price_1SoMpcDAhVuVVDV1S4OkCwiX';
  // $price_id = 'price_1Sgj3FDAhVuVVDV13GsisC2U';

  \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

  try {
    $session = \Stripe\Checkout\Session::create([
      'mode' => 'payment',
      'ui_mode' => 'embedded',
      'line_items' => [[
        'price' => $price_id,
        'quantity' => 1,
      ]],
      'return_url' => 'https://zora.health/danke-fuer-einkauf/?session_id={CHECKOUT_SESSION_ID}',
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
      'zora-embedded-checkout',
      plugin_dir_url(__FILE__) . 'assets/embedded-checkout.js',
      ['stripe-js'],
      '1.0.0',
      true
    );
  
    wp_localize_script('zora-embedded-checkout', 'ZoraStripe', [
      'publishableKey' => defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : '',
      'createSessionUrl' => esc_url_raw(rest_url('stripe-embedded/v1/create-session')),
    ]);
  });
  
  add_shortcode('zora_embedded_checkout', function () {
    return 'SHORTCODE-OK <div id="stripe-embedded-checkout"></div>';
  });
  