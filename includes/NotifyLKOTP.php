<?php
if (!defined('ABSPATH'))
    exit;
class NotifyLKOTP
{
    /*
     * Declare setting prefix and other values.
     */

    private $prefix = 'notifylk_sms_woo_';
    private $rest_api_endopint = 'notifywc/v1/';
    private $api_key, $userId, $send_id;

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        // Do nothing if WooCommerce is not active
        $plugin_path = trailingslashit(WP_PLUGIN_DIR) . 'woocommerce/woocommerce.php';

        if (!in_array($plugin_path, wp_get_active_and_valid_plugins()))
            return;

        /*
         * Get NotifyLK configuration settings.
         */
        $this->api_key = get_option($this->prefix . 'api_key');
        $this->userId = get_option($this->prefix . 'user_id');
        $this->send_id = get_option($this->prefix . 'from_id');

        // Register the endpoints
        add_action('rest_api_init', array($this, 'generate_otp_api_endpoint'));
        add_action('rest_api_init', array($this, 'validate_otp_api_endpoint'));

        add_action('woocommerce_register_form_start', array($this, 'add_phone_number_field'));
        add_action('woocommerce_after_checkout_billing_form', array($this, 'add_phone_number_field'));
        add_action('woocommerce_register_post', array($this, 'validate_phone_number_field'), 10, 3);
        add_action('woocommerce_created_customer', array($this, 'save_phone_number_field'));

        add_action('woocommerce_after_checkout_billing_form', array($this, 'inject_otp_js'));
    }

    public function inject_otp_js()
    {
        if (is_checkout() || is_account_page())
            wp_enqueue_script('notifylk-otp-js', plugins_url('js/notify-wc-otp-check.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    public function generate_otp_api_endpoint()
    {
        register_rest_route($this->rest_api_endopint, 'notifylk_wc_otp/', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_otp_endpoint_callback'),
        ));
    }

    // API Callback function
    public function generate_otp_endpoint_callback($request)
    {
        $user_id = get_current_user_id();
        $otp = $this->generate_otp();

        update_user_meta($user_id, 'notifylk_wc_otp', $otp);

        $phone = $request->get_param('phone');
        $phone = sanitize_text_field($phone);
        $phone = NotifyLKTrigger::reformatPhoneNumbers($phone);

        $site_name = get_bloginfo('name');

        $apiInt = new \NotifyLk\Api\SmsApi();
        $message = "Please use $otp as your verification code for $site_name.";
        try {
            $apiInt->sendSMS($this->userId, $this->api_key, $message, $phone, $this->send_id);
        } catch (\Throwable $th) {
            //throw $th;
        }

        return array('status' => 'success');
    }

    public function generate_otp()
    {
        return rand(1000, 9999);
    }

    public function validate_otp_api_endpoint()
    {
        register_rest_route($this->rest_api_endopint, 'notifylk_wc_validate_otp/', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_otp_endpoint_callback'),
        ));
    }

    public function validate_otp_endpoint_callback($request)
    {
        $request_otp = $request->get_param('notifylk_wc_otp');
        $request_otp = sanitize_text_field($request_otp);

        $user_id = get_current_user_id();
        $saved_otp = get_user_meta($user_id, 'notifylk_wc_otp', true);

        if ($saved_otp == $request_otp) {
            return array('status' => 'success');
        } else {
            return array('status' => 'error');
        }
    }

    /**
     * Adds a phone number field to the checkout page.
     *
     * @param WC_Checkout $checkout The checkout object.
     */
    public function add_phone_number_field($checkout = null)
    {
        // Get the checkout fields
        $checkout_fields = WC()->checkout()->get_checkout_fields();

        // Do nothing if the billing_phone field exists
        if (isset($checkout_fields['billing']['billing_phone'])) {
            return;
        }

        $oldValue = empty($checkout) ? $_POST['billing_phone'] : $checkout->get_value('billing_phone');

        woocommerce_form_field('billing_phone', array(
            'type'          => 'text',
            'class'         => array('form-row-wide'),
            'label'         => __('Phone number'),
            'placeholder'   => __('Enter your phone number'),
            'required'      => true,
        ), $oldValue);
    }

    public function validate_phone_number_field($username, $email, $validation_errors)
    {
        if (isset($_POST['billing_phone']) && empty($_POST['billing_phone'])) {
            $validation_errors->add('billing_phone_error', __('Phone number is required!', 'woocommerce'));
        }
        return $validation_errors;
    }

    public function save_phone_number_field($customer_id)
    {
        if (isset($_POST['billing_phone'])) {
            update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
        }
    }
}
