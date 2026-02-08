=== Simple Payment Checkout ===
Contributors: evgenyviner
Tags: stripe, payments, checkout, payment-form, embedded-checkout
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Stripe payments on your WordPress site with a simple embedded checkout form.

== Description ==

Suprematrix Simple Payment Checkout makes it easy to accept payments through Stripe on your WordPress website. The plugin embeds a secure Stripe checkout form directly on your site using an iframe, providing a seamless payment experience for your customers.

**Key Features:**

* Simple setup - just add your Stripe API keys and product ID
* Secure payment processing through Stripe
* Embedded checkout form - customers stay on your site
* Supports both test and live modes
* Lightweight and fast
* No coding required

**Perfect for:**

* Selling a single product or service
* Accepting donations
* Processing one-time payments
* Simple checkout needs

**Requirements:**

* A Stripe account (free to create at stripe.com)
* A product created in your Stripe Dashboard
* Your Stripe API keys (test and/or live)

**How It Works:**

1. Create a product in your Stripe Dashboard
2. Install and activate the plugin
3. Enter your Stripe API keys in the plugin settings
4. Add your Stripe product ID
5. Use the provided shortcode to display the checkout form anywhere on your site

The plugin handles all the technical details - you just configure the basics and you're ready to accept payments!

**Privacy & Security:**

This plugin does not store any payment information on your WordPress site. All payment data is processed securely by Stripe. The plugin only stores your Stripe API keys and product ID in your WordPress database.

== Third-Party Services & Libraries ==

This plugin uses the following third-party library:

= Stripe PHP SDK =
* Version: 19.2.0 (or whatever version you're using)
* License: MIT License
* Source: https://github.com/stripe/stripe-php
* Documentation: https://stripe.com/docs/api
* Purpose: Secure payment processing through Stripe API
* Location: Bundled in /vendor/stripe/stripe-php/

The Stripe PHP SDK is included to communicate with Stripe's payment processing service. When you use this plugin to process payments, payment data is transmitted to Stripe's servers according to their privacy policy: https://stripe.com/privacy

This library is not modified and remains in its original form to ensure security and ease of updates.


== Installation ==

**Automatic Installation:**

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "Simple Payment Checkout"
4. Click "Install Now" and then "Activate"

**Manual Installation:**

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

**Configuration:**

1. Go to Settings > Simple Payment Checkout
2. Enter your Stripe Publishable Key (test or live)
3. Enter your Stripe Secret Key (test or live)
4. Enter your Stripe Product ID
5. Save your settings
6. Add the shortcode `[simple_payment_checkout]` to any page or post where you want the checkout form to appear

== Frequently Asked Questions ==

= Do I need a Stripe account? =

Yes, you need a Stripe account to use this plugin. You can create a free account at stripe.com.

= Where do I find my Stripe API keys? =

Log in to your Stripe Dashboard, go to Developers > API keys. You'll find both your Publishable key and Secret key there. Use the test keys for testing and live keys when you're ready to accept real payments.

= Where do I find my Product ID? =

In your Stripe Dashboard, go to Products, click on your product, and you'll see the Product ID (starts with "prod_") in the product details.

= Can I sell multiple products? =

The current version supports one product. Multiple product support is planned for future updates.

= Is the checkout form secure? =

Yes! The checkout form is provided by Stripe and all payment data is processed securely by Stripe. Your WordPress site never handles sensitive payment information.

= Can I accept subscriptions? =

The current version supports one-time payments only. Subscription support may be added in future versions.

= Can I customize the appearance of the checkout form? =

The checkout form appearance can be customized through your Stripe Dashboard branding settings (Settings > Branding). The plugin uses Stripe's standard checkout interface.

= What happens after a successful payment? =

After successful payment, customers are redirected to a confirmation page. You can view all payments in your Stripe Dashboard.

= How do I test the plugin before accepting real payments? =

Use your Stripe test API keys in the plugin settings. You can use Stripe's test card numbers (available in Stripe's documentation) to simulate payments without charging real cards.

= Where can I get support? =

You can get support through the WordPress.org support forums for this plugin. For Stripe-specific questions, refer to Stripe's documentation at stripe.com/docs.

== Screenshots ==

1. Plugin settings page - easy configuration
2. Embedded checkout form on a page
3. Simple shortcode implementation

== Changelog ==

= 1.0.0 =
* Initial release
* Support for single product payments
* Embedded Stripe checkout form
* Test and live mode support
* Simple shortcode integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Simple Payment Checkout.

== Additional Information ==

**Developer Resources:**

* GitHub Repository: [Your GitHub URL]
* Report Issues: [Your GitHub Issues URL]

**Credits:**

This plugin uses the Stripe API to process payments. Stripe is a registered trademark of Stripe, Inc.

**Support the Development:**

If you find this plugin helpful, please consider leaving a review or supporting the development.