<?php
/**
 * Plugin Name: Suprematrix Simple Payment Checkout
 * Description: A simple plugin to add Stripe Embedded Checkout to your WordPress site.
 * Version: 1.0.0
 * Author: Evgeny Viner
 * Text Domain: suprematrix-simple-payment-checkout
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * 
 * Third-party Libraries:
 * - Stripe PHP SDK v19.2.0 (MIT License) - https://github.com/stripe/stripe-php

 */

/*
Suprematrix Simple Payment Checkout is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Suprematrix Simple Payment Checkout is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Suprematrix Simple Payment Checkout. If not, see https://www.gnu.org/licenses/gpl-2.0.txt.
*/


if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/vendor/autoload.php';


// Load admin settings page
if (is_admin()) {
    require_once __DIR__ . '/includes/admin/class-settings-page.php';
    new SSPC_Settings_Page();
}

/**
 * Get Stripe settings from options
 */
function sspc_get_settings() {
    $defaults = array(
        'test_mode' => true,
        'test_secret_key' => '',
        'test_publishable_key' => '',
        'live_secret_key' => '',
        'live_publishable_key' => '',
        'return_url' => '',
        'price_id' => '',
    );

    $settings = get_option('sspc_settings', $defaults);
    return wp_parse_args($settings, $defaults);
}

/**
 * Get the appropriate secret key based on test mode
 */
function sspc_get_secret_key() {
  $settings = sspc_get_settings();
  // Handle test_mode as boolean, integer, or string
  $is_test_mode = !empty($settings['test_mode']) || 
                  (isset($settings['test_mode']) && ($settings['test_mode'] === '1' || $settings['test_mode'] === 1));
  
  if ($is_test_mode) {
      return !empty($settings['test_secret_key']) ? $settings['test_secret_key'] : '';
  } else {
      return !empty($settings['live_secret_key']) ? $settings['live_secret_key'] : '';
  }
}
/**
 * Get the appropriate publishable key based on test mode
 */
function sspc_get_publishable_key() {
  $settings = sspc_get_settings();
  // Handle test_mode as boolean, integer, or string
  $is_test_mode = !empty($settings['test_mode']) || 
                  (isset($settings['test_mode']) && ($settings['test_mode'] === '1' || $settings['test_mode'] === 1));
  
  if ($is_test_mode) {
      return !empty($settings['test_publishable_key']) ? $settings['test_publishable_key'] : '';
  } else {
      return !empty($settings['live_publishable_key']) ? $settings['live_publishable_key'] : '';
  }
}

/**
 * Get the configured return URL
 */
function sspc_get_return_url() {
    $settings = sspc_get_settings();
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
function sspc_get_price_id() {
    $settings = sspc_get_settings();
    return !empty($settings['price_id']) ? $settings['price_id'] : '';
}

add_action('rest_api_init', function () {
  register_rest_route('stripe-embedded/v1', '/create-session', [
    'methods'  => 'POST',
    'callback' => 'sspc_stripe_create_session',
    'permission_callback' => '__return_true', // public endpoint for anonymous checkout
  ]);
});

function sspc_stripe_create_session(\WP_REST_Request $request) {
  $secret_key = sspc_get_secret_key();
  $price_id = sspc_get_price_id();
  $return_url = sspc_get_return_url();

  if (empty($secret_key)) {
    return new \WP_REST_Response(['error' => 'Stripe secret key not configured'], 500);
  }

  if (empty($price_id)) {
    return new \WP_REST_Response(['error' => 'Product price ID not configured'], 500);
  }

  $stripe = new \Stripe\StripeClient($secret_key);

  try {
    $session = $stripe->checkout->sessions->create([
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
      'sspc-embedded-checkout',
      plugin_dir_url(__FILE__) . 'assets/embedded-checkout.js',
      ['stripe-js'],
      '1.0.0',
      true
    );
  
    wp_localize_script('sspc-embedded-checkout', 'sspc', [
      'publishableKey' => sspc_get_publishable_key(),
      'createSessionUrl' => esc_url_raw(rest_url('stripe-embedded/v1/create-session')),
    ]);
  });
  
  add_shortcode('sspc_embedded_checkout', function () {
    return '<div id="sspc"></div>';
  });

/**
 * Add plugin action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sspc_add_action_links');

function sspc_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=sspc-settings') . '">' . __('Settings', 'suprematrix-simple-payment-checkout') . '</a>';
    $support_link = '<a href="https://buymeacoffee.com/evgenyviner" target="_blank" rel="noopener noreferrer">' . __('Buy me a coffee', 'suprematrix-simple-payment-checkout') . '</a>';
    array_unshift($links, $support_link, $settings_link);
    return $links;
}

/**
 * Add plugin row meta
 */
add_filter('plugin_row_meta', 'sspc_add_plugin_row_meta', 10, 2);

function sspc_add_plugin_row_meta($links, $file) {
    if (plugin_basename(__FILE__) !== $file) {
        return $links;
    }
    
    $support_link = '<a href="https://linktr.ee/evgenyviner" target="_blank" rel="noopener noreferrer">' . __('About the Developer', 'suprematrix-simple-payment-checkout') . '</a>';
    $links[] = $support_link;
    
    return $links;
}
