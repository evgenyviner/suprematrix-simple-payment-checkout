<?php
/**
 * Admin Settings Page for Simple Payment Checkout
 *
 * @package SPC
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPC_Settings_Page {

    /**
     * Option group name
     */
    const OPTION_GROUP = 'spc_settings';

    /**
     * Option name
     */
    const OPTION_NAME = 'spc_settings';

    /**
     * Initialize the settings page
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('Simple Payment Checkout', 'spc'),
            __('Simple Payment Checkout', 'spc'),
            'manage_options',
            'spc-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings using WordPress Settings API
     */
    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array(
                    'test_mode' => true,
                    'test_secret_key' => '',
                    'test_publishable_key' => '',
                    'live_secret_key' => '',
                    'live_publishable_key' => '',
                    'return_url' => '',
                    'price_id' => '',
                ),
            )
        );

        // Mode Settings Section
        add_settings_section(
            'spc_mode_section',
            __('Mode Settings', 'spc'),
            array($this, 'render_mode_section_description'),
            'spc-settings'
        );

        add_settings_field(
            'test_mode',
            __('Test Mode', 'spc'),
            array($this, 'render_test_mode_field'),
            'spc-settings',
            'spc_mode_section'
        );

        // API Keys Section
        add_settings_section(
            'spc_keys_section',
            __('API Keys', 'spc'),
            array($this, 'render_keys_section_description'),
            'spc-settings'
        );

        add_settings_field(
            'test_secret_key',
            __('Test Secret Key', 'spc'),
            array($this, 'render_test_secret_key_field'),
            'spc-settings',
            'spc_keys_section'
        );

        add_settings_field(
            'test_publishable_key',
            __('Test Publishable Key', 'spc'),
            array($this, 'render_test_publishable_key_field'),
            'spc-settings',
            'spc_keys_section'
        );

        add_settings_field(
            'live_secret_key',
            __('Live Secret Key', 'spc'),
            array($this, 'render_live_secret_key_field'),
            'spc-settings',
            'spc_keys_section'
        );

        add_settings_field(
            'live_publishable_key',
            __('Live Publishable Key', 'spc'),
            array($this, 'render_live_publishable_key_field'),
            'spc-settings',
            'spc_keys_section'
        );

        // Checkout Configuration Section
        add_settings_section(
            'spc_checkout_section',
            __('Checkout Configuration', 'spc'),
            array($this, 'render_checkout_section_description'),
            'spc-settings'
        );

        add_settings_field(
            'return_url',
            __('Return URL', 'spc'),
            array($this, 'render_return_url_field'),
            'spc-settings',
            'spc_checkout_section'
        );

        add_settings_field(
            'price_id',
            __('Product Price ID', 'spc'),
            array($this, 'render_price_id_field'),
            'spc-settings',
            'spc_checkout_section'
        );
    }

    /**
     * Sanitize and validate settings
     */
    public function sanitize_settings($input) {
        // Get existing settings to merge with
        $existing_settings = $this->get_settings();
        $sanitized = array();
        $errors = array();

        // Test Mode (boolean) - checkbox sends '1' when checked, nothing when unchecked
        // WordPress Settings API: if checkbox is checked, it sends the value; if unchecked, the key is not in $input
        if (isset($input['test_mode'])) {
            // Checkbox was in the form - check if it's checked (value is '1')
            $sanitized['test_mode'] = ($input['test_mode'] === '1' || $input['test_mode'] === 1 || $input['test_mode'] === true);
        } else {
            // Checkbox was not in the form submission, meaning it's unchecked
            $sanitized['test_mode'] = false;
        }

        // Test Secret Key
        $test_secret = isset($input['test_secret_key']) ? trim($input['test_secret_key']) : '';
        if (!empty($test_secret) && !$this->validate_secret_key($test_secret, 'test')) {
            $errors[] = __('Test Secret Key must start with "sk_test_"', 'spc');
        }
        $sanitized['test_secret_key'] = $test_secret;

        // Test Publishable Key
        $test_publishable = isset($input['test_publishable_key']) ? trim($input['test_publishable_key']) : '';
        if (!empty($test_publishable) && !$this->validate_publishable_key($test_publishable, 'test')) {
            $errors[] = __('Test Publishable Key must start with "pk_test_"', 'spc');
        }
        $sanitized['test_publishable_key'] = $test_publishable;

        // Live Secret Key
        $live_secret = isset($input['live_secret_key']) ? trim($input['live_secret_key']) : '';
        if (!empty($live_secret) && !$this->validate_secret_key($live_secret, 'live')) {
            $errors[] = __('Live Secret Key must start with "sk_live_"', 'spc');
        }
        $sanitized['live_secret_key'] = $live_secret;

        // Live Publishable Key
        $live_publishable = isset($input['live_publishable_key']) ? trim($input['live_publishable_key']) : '';
        if (!empty($live_publishable) && !$this->validate_publishable_key($live_publishable, 'live')) {
            $errors[] = __('Live Publishable Key must start with "pk_live_"', 'spc');
        }
        $sanitized['live_publishable_key'] = $live_publishable;

        // Return URL
        $return_url = isset($input['return_url']) ? trim($input['return_url']) : '';
        if (!empty($return_url) && !filter_var($return_url, FILTER_VALIDATE_URL)) {
            $errors[] = __('Return URL must be a valid URL', 'spc');
        }
        $sanitized['return_url'] = esc_url_raw($return_url);

        // Price ID
        $sanitized['price_id'] = isset($input['price_id']) ? sanitize_text_field(trim($input['price_id'])) : '';

        // Add validation errors
        if (!empty($errors)) {
            foreach ($errors as $error) {
                add_settings_error(self::OPTION_NAME, 'validation_error', $error, 'error');
            }
        }

        // Warn if required keys are missing for current mode
        if ($sanitized['test_mode']) {
            if (empty($sanitized['test_secret_key']) || empty($sanitized['test_publishable_key'])) {
                add_settings_error(
                    self::OPTION_NAME,
                    'missing_test_keys',
                    __('Test mode is enabled but test keys are missing.', 'spc'),
                    'warning'
                );
            }
        } else {
            if (empty($sanitized['live_secret_key']) || empty($sanitized['live_publishable_key'])) {
                add_settings_error(
                    self::OPTION_NAME,
                    'missing_live_keys',
                    __('Live mode is enabled but live keys are missing.', 'spc'),
                    'warning'
                );
            }
        }

        return $sanitized;
    }

    /**
     * Validate secret key format
     */
    private function validate_secret_key($key, $mode) {
        if ($mode === 'test') {
            return strpos($key, 'sk_test_') === 0;
        } else {
            return strpos($key, 'sk_live_') === 0;
        }
    }

    /**
     * Validate publishable key format
     */
    private function validate_publishable_key($key, $mode) {
        if ($mode === 'test') {
            return strpos($key, 'pk_test_') === 0;
        } else {
            return strpos($key, 'pk_live_') === 0;
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        $is_test_mode = !empty($settings['test_mode']);
        $shortcode = '[spc_embedded_checkout]';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if ($is_test_mode) : ?>
                <div class="notice notice-info">
                    <p><strong><?php esc_html_e('Test Mode is currently active.', 'spc'); ?></strong></p>
                </div>
            <?php else : ?>
                <div class="notice notice-warning">
                    <p><strong><?php esc_html_e('Live Mode is currently active.', 'spc'); ?></strong></p>
                </div>
            <?php endif; ?>

            <div class="spc-shortcode-section" style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; margin: 20px 0;">
                <h2 style="margin-top: 0;"><?php esc_html_e('Shortcode', 'spc'); ?></h2>
                <p style="margin-bottom: 15px;">
                    <?php esc_html_e('Copy the shortcode below and paste it into any page or post where you want the checkout form to appear.', 'spc'); ?>
                </p>
                <div style="display: flex; gap: 10px; align-items: stretch;">
                    <input type="text" 
                           id="spc-shortcode-input" 
                           value="<?php echo esc_attr($shortcode); ?>" 
                           readonly 
                           style="flex: 1; padding: 8px 12px; background-color: #f0f0f1; border: 1px solid #8c8f94; color: #50575e; font-family: monospace; font-size: 14px; cursor: text; box-sizing: border-box; height: 38px; line-height: 1.5;">
                    <button type="button" 
                            id="spc-copy-shortcode" 
                            class="button button-secondary"
                            data-shortcode="<?php echo esc_attr($shortcode); ?>"
                            style="height: 38px; box-sizing: border-box; padding: 8px 12px; line-height: 1.5;">
                        <?php esc_html_e('Copy Shortcode', 'spc'); ?>
                    </button>
                </div>
                <p id="spc-copy-feedback" style="margin: 10px 0 0 0; color: #00a32a; display: none; font-weight: 600;">
                    <?php esc_html_e('✓ Shortcode copied to clipboard!', 'spc'); ?>
                </p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections('spc-settings');
                submit_button(__('Save Settings', 'spc'));
                ?>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
                <p>
                    <?php
                    printf(
                        __('Enjoying %s? %s', 'spc'),
                        '<strong>Simple Payment Checkout</strong>',
                        '<a href="https://buymeacoffee.com/evgenyviner" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">☕ ' . __('Buy me a coffee', 'spc') . '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our settings page
        if ($hook !== 'settings_page_spc-settings') {
            return;
        }
        
        // Add inline script for copy functionality
        $script = "
        jQuery(document).ready(function($) {
            $('#spc-copy-shortcode').on('click', function(e) {
                e.preventDefault();
                var shortcodeInput = document.getElementById('spc-shortcode-input');
                var feedback = document.getElementById('spc-copy-feedback');
                var shortcode = shortcodeInput.value;
                
                // Try modern Clipboard API first
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(shortcode).then(function() {
                        showCopyFeedback(feedback, $(this));
                    }.bind(this)).catch(function(err) {
                        // Fallback to execCommand
                        fallbackCopy(shortcodeInput, feedback, $(this));
                    }.bind(this));
                } else {
                    // Fallback to execCommand for older browsers
                    fallbackCopy(shortcodeInput, feedback, $(this));
                }
            });
            
            function fallbackCopy(input, feedback, button) {
                input.select();
                input.setSelectionRange(0, 99999); // For mobile devices
                
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        showCopyFeedback(feedback, button);
                    } else {
                        alert('" . esc_js(__('Please manually copy the shortcode.', 'spc')) . "');
                    }
                } catch (err) {
                    alert('" . esc_js(__('Please manually copy the shortcode.', 'spc')) . "');
                }
            }
            
            function showCopyFeedback(feedback, button) {
                // Show feedback message
                feedback.style.display = 'block';
                setTimeout(function() {
                    feedback.style.display = 'none';
                }, 3000);
                
                // Change button text temporarily
                var originalText = button.text();
                button.text('" . esc_js(__('Copied!', 'spc')) . "');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            }
        });
        ";
        
        wp_add_inline_script('jquery', $script);
    }

    /**
     * Get current settings
     */
    private function get_settings() {
        $defaults = array(
            'test_mode' => true,
            'test_secret_key' => '',
            'test_publishable_key' => '',
            'live_secret_key' => '',
            'live_publishable_key' => '',
            'return_url' => '',
            'price_id' => '',
        );

        $settings = get_option(self::OPTION_NAME, $defaults);
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Render mode section description
     */
    public function render_mode_section_description() {
        echo '<p>' . esc_html__('Choose whether to use test or live Stripe keys.', 'spc') . '</p>';
    }

    /**
     * Render test mode field
     */
    public function render_test_mode_field() {
        $settings = $this->get_settings();
        $test_mode = !empty($settings['test_mode']);
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[test_mode]" value="1" <?php checked($test_mode); ?>>
            <?php esc_html_e('Enable Test Mode', 'spc'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, test keys will be used. Uncheck to use live keys.', 'spc'); ?>
        </p>
        <?php
    }

    /**
     * Render keys section description
     */
    public function render_keys_section_description() {
        echo '<p>' . esc_html__('Enter your Stripe API keys. You can find these in your Stripe Dashboard under Developers > API keys.', 'spc') . '</p>';
    }

    /**
     * Render test secret key field
     */
    public function render_test_secret_key_field() {
        $settings = $this->get_settings();
        $value = isset($settings['test_secret_key']) ? $settings['test_secret_key'] : '';
        ?>
        <input type="password" 
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[test_secret_key]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="sk_test_...">
        <p class="description">
            <?php esc_html_e('Your test secret key (starts with sk_test_)', 'spc'); ?>
        </p>
        <?php
    }

    /**
     * Render test publishable key field
     */
    public function render_test_publishable_key_field() {
        $settings = $this->get_settings();
        $value = isset($settings['test_publishable_key']) ? $settings['test_publishable_key'] : '';
        ?>
        <input type="text" 
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[test_publishable_key]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="pk_test_...">
        <p class="description">
            <?php esc_html_e('Your test publishable key (starts with pk_test_)', 'spc'); ?>
        </p>
        <?php
    }

    /**
     * Render live secret key field
     */
    public function render_live_secret_key_field() {
        $settings = $this->get_settings();
        $value = isset($settings['live_secret_key']) ? $settings['live_secret_key'] : '';
        ?>
        <input type="password" 
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[live_secret_key]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="sk_live_...">
        <p class="description">
            <?php esc_html_e('Your live secret key (starts with sk_live_)', 'spc'); ?>
        </p>
        <?php
    }

    /**
     * Render live publishable key field
     */
    public function render_live_publishable_key_field() {
        $settings = $this->get_settings();
        $value = isset($settings['live_publishable_key']) ? $settings['live_publishable_key'] : '';
        ?>
        <input type="text" 
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[live_publishable_key]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="pk_live_...">
        <p class="description">
            <?php esc_html_e('Your live publishable key (starts with pk_live_)', 'spc'); ?>
        </p>
        <?php
    }

    /**
     * Render checkout section description
     */
    public function render_checkout_section_description() {
        echo '<p>' . esc_html__('Configure the checkout behavior and product settings.', 'spc') . '</p>';
    }

    /**
     * Render return URL field
     */
    public function render_return_url_field() {
        $settings = $this->get_settings();
        $value = isset($settings['return_url']) ? $settings['return_url'] : '';
        $default_url = home_url('/?session_id={CHECKOUT_SESSION_ID}');
        ?>
        <input type="url" 
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[return_url]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="<?php echo esc_attr($default_url); ?>">
        <p class="description">
            <?php esc_html_e('URL where customers will be redirected after checkout. Use {CHECKOUT_SESSION_ID} as a placeholder.', 'spc'); ?>
            <?php if (empty($value)) : ?>
                <br><strong><?php esc_html_e('Suggested:', 'spc'); ?></strong> <code><?php echo esc_html($default_url); ?></code>
            <?php endif; ?>
        </p>
        <?php
    }

    /**
     * Render price ID field
     */
    public function render_price_id_field() {
        $settings = $this->get_settings();
        $value = isset($settings['price_id']) ? $settings['price_id'] : '';
        ?>
        <input type="text" 
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[price_id]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="price_...">
        <p class="description">
            <?php esc_html_e('The Stripe Price ID for the product you want to sell. You can find this in your Stripe Dashboard under Products.', 'spc'); ?>
        </p>
        <?php
    }
}
