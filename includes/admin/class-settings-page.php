<?php
/**
 * Admin Settings Page for Stripe Embedded Checkout
 *
 * @package SECWP
 */

if (!defined('ABSPATH')) {
    exit;
}

class SECWP_Settings_Page {

    /**
     * Option group name
     */
    const OPTION_GROUP = 'secwp_settings';

    /**
     * Option name
     */
    const OPTION_NAME = 'secwp_settings';

    /**
     * Initialize the settings page
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('Stripe Embedded Checkout', 'secwp'),
            __('Stripe Embedded Checkout', 'secwp'),
            'manage_options',
            'secwp-settings',
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
            'secwp_mode_section',
            __('Mode Settings', 'secwp'),
            array($this, 'render_mode_section_description'),
            'secwp-settings'
        );

        add_settings_field(
            'test_mode',
            __('Test Mode', 'secwp'),
            array($this, 'render_test_mode_field'),
            'secwp-settings',
            'secwp_mode_section'
        );

        // API Keys Section
        add_settings_section(
            'secwp_keys_section',
            __('API Keys', 'secwp'),
            array($this, 'render_keys_section_description'),
            'secwp-settings'
        );

        add_settings_field(
            'test_secret_key',
            __('Test Secret Key', 'secwp'),
            array($this, 'render_test_secret_key_field'),
            'secwp-settings',
            'secwp_keys_section'
        );

        add_settings_field(
            'test_publishable_key',
            __('Test Publishable Key', 'secwp'),
            array($this, 'render_test_publishable_key_field'),
            'secwp-settings',
            'secwp_keys_section'
        );

        add_settings_field(
            'live_secret_key',
            __('Live Secret Key', 'secwp'),
            array($this, 'render_live_secret_key_field'),
            'secwp-settings',
            'secwp_keys_section'
        );

        add_settings_field(
            'live_publishable_key',
            __('Live Publishable Key', 'secwp'),
            array($this, 'render_live_publishable_key_field'),
            'secwp-settings',
            'secwp_keys_section'
        );

        // Checkout Configuration Section
        add_settings_section(
            'secwp_checkout_section',
            __('Checkout Configuration', 'secwp'),
            array($this, 'render_checkout_section_description'),
            'secwp-settings'
        );

        add_settings_field(
            'return_url',
            __('Return URL', 'secwp'),
            array($this, 'render_return_url_field'),
            'secwp-settings',
            'secwp_checkout_section'
        );

        add_settings_field(
            'price_id',
            __('Product Price ID', 'secwp'),
            array($this, 'render_price_id_field'),
            'secwp-settings',
            'secwp_checkout_section'
        );
    }

    /**
     * Sanitize and validate settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $errors = array();

        // Test Mode (boolean)
        $sanitized['test_mode'] = isset($input['test_mode']) && $input['test_mode'] === '1';

        // Test Secret Key
        $test_secret = isset($input['test_secret_key']) ? trim($input['test_secret_key']) : '';
        if (!empty($test_secret) && !$this->validate_secret_key($test_secret, 'test')) {
            $errors[] = __('Test Secret Key must start with "sk_test_"', 'secwp');
        }
        $sanitized['test_secret_key'] = $test_secret;

        // Test Publishable Key
        $test_publishable = isset($input['test_publishable_key']) ? trim($input['test_publishable_key']) : '';
        if (!empty($test_publishable) && !$this->validate_publishable_key($test_publishable, 'test')) {
            $errors[] = __('Test Publishable Key must start with "pk_test_"', 'secwp');
        }
        $sanitized['test_publishable_key'] = $test_publishable;

        // Live Secret Key
        $live_secret = isset($input['live_secret_key']) ? trim($input['live_secret_key']) : '';
        if (!empty($live_secret) && !$this->validate_secret_key($live_secret, 'live')) {
            $errors[] = __('Live Secret Key must start with "sk_live_"', 'secwp');
        }
        $sanitized['live_secret_key'] = $live_secret;

        // Live Publishable Key
        $live_publishable = isset($input['live_publishable_key']) ? trim($input['live_publishable_key']) : '';
        if (!empty($live_publishable) && !$this->validate_publishable_key($live_publishable, 'live')) {
            $errors[] = __('Live Publishable Key must start with "pk_live_"', 'secwp');
        }
        $sanitized['live_publishable_key'] = $live_publishable;

        // Return URL
        $return_url = isset($input['return_url']) ? trim($input['return_url']) : '';
        if (!empty($return_url) && !filter_var($return_url, FILTER_VALIDATE_URL)) {
            $errors[] = __('Return URL must be a valid URL', 'secwp');
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
                    __('Test mode is enabled but test keys are missing.', 'secwp'),
                    'warning'
                );
            }
        } else {
            if (empty($sanitized['live_secret_key']) || empty($sanitized['live_publishable_key'])) {
                add_settings_error(
                    self::OPTION_NAME,
                    'missing_live_keys',
                    __('Live mode is enabled but live keys are missing.', 'secwp'),
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
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if ($is_test_mode) : ?>
                <div class="notice notice-info">
                    <p><strong><?php esc_html_e('Test Mode is currently active.', 'secwp'); ?></strong></p>
                </div>
            <?php else : ?>
                <div class="notice notice-warning">
                    <p><strong><?php esc_html_e('Live Mode is currently active.', 'secwp'); ?></strong></p>
                </div>
            <?php endif; ?>

            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections('secwp-settings');
                submit_button(__('Save Settings', 'secwp'));
                ?>
            </form>
        </div>
        <?php
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
        echo '<p>' . esc_html__('Choose whether to use test or live Stripe keys.', 'secwp') . '</p>';
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
            <?php esc_html_e('Enable Test Mode', 'secwp'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, test keys will be used. Uncheck to use live keys.', 'secwp'); ?>
        </p>
        <?php
    }

    /**
     * Render keys section description
     */
    public function render_keys_section_description() {
        echo '<p>' . esc_html__('Enter your Stripe API keys. You can find these in your Stripe Dashboard under Developers > API keys.', 'secwp') . '</p>';
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
            <?php esc_html_e('Your test secret key (starts with sk_test_)', 'secwp'); ?>
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
            <?php esc_html_e('Your test publishable key (starts with pk_test_)', 'secwp'); ?>
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
            <?php esc_html_e('Your live secret key (starts with sk_live_)', 'secwp'); ?>
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
            <?php esc_html_e('Your live publishable key (starts with pk_live_)', 'secwp'); ?>
        </p>
        <?php
    }

    /**
     * Render checkout section description
     */
    public function render_checkout_section_description() {
        echo '<p>' . esc_html__('Configure the checkout behavior and product settings.', 'secwp') . '</p>';
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
            <?php esc_html_e('URL where customers will be redirected after checkout. Use {CHECKOUT_SESSION_ID} as a placeholder.', 'secwp'); ?>
            <?php if (empty($value)) : ?>
                <br><strong><?php esc_html_e('Suggested:', 'secwp'); ?></strong> <code><?php echo esc_html($default_url); ?></code>
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
            <?php esc_html_e('The Stripe Price ID for the product you want to sell. You can find this in your Stripe Dashboard under Products.', 'secwp'); ?>
        </p>
        <?php
    }
}
