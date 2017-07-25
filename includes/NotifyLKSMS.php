<?php

class NotifyLKSMS {

    public $prefix = 'notifylk_sms_woo_';

    public function __construct($baseFile = null) {
	define("TEXTDOMAIN", "settings_tab_notifylk");
	$this->init();
    }

    private function init() {
	/*
	 * Add NotifyLK SMS to woo-commerce settings.
	 */
	$triggerAPI = new NotifyLKTrigger();
	add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
	add_action('woocommerce_settings_tabs_settings_tab_notifylk', array($this, 'settings_tab'));
	add_action('woocommerce_update_options_settings_tab_notifylk', array($this, 'update_settings'));

	/*
	 * Customer Messages
	 */
	add_action('woocommerce_order_status_pending', array($triggerAPI, 'notify_send_customer_sms_for_woo_order_status_pending'), 10, 1);
	add_action('woocommerce_order_status_failed', array($triggerAPI, 'notify_send_customer_sms_for_woo_order_status_failed'), 10, 1);
	add_action('woocommerce_order_status_on-hold', array($triggerAPI, 'notify_send_customer_sms_for_woo_order_status_on_hold'), 10, 1);
	add_action('woocommerce_order_status_processing', array($triggerAPI, 'notify_send_customer_sms_for_woo_order_status_processing'), 10, 1);
	add_action('woocommerce_order_status_completed', array($triggerAPI, 'notify_send_customer_sms_for_woo_order_status_completed'), 10, 1);
	add_action('woocommerce_order_status_refunded', array($triggerAPI, 'notify_send_customer_sms_for_woo_order_status_refunded'), 10, 1);
	add_action('woocommerce_order_status_cancelled', array($triggerAPI, 'notify_send_customer_sms_for_woo_order_status_cancelled'), 10, 1);

	/*
	 * Send new order admin SMS
	 */
	add_action('woocommerce_order_status_processing', array($triggerAPI, 'notify_send_admin_sms_for_woo_new_order'), 10, 1);
    }

    public static function add_settings_tab($settings_tabs) {
	$settings_tabs['settings_tab_notifylk'] = __('NotifyLK SMS', TEXTDOMAIN);
	return $settings_tabs;
    }

    public function update_settings() {
	woocommerce_update_options($this->getFields());
    }

    public function settings_tab() {
	woocommerce_admin_fields($this->getFields());
    }

    private function getFields() {


	/*
	 * 
	 * Customer Notifications
	 * 
	 */


	$fields[] = array(
	    'title' => 'Notifications for Customer',
	    'type' => 'title',
	    'desc' => 'Send SMS to customer\'s mobile phone. Will be sent to the phone number which customer is providing while checkout process.',
	    'id' => TEXTDOMAIN . 'customersettings'
	);
	$fields[] = array(
	    'title' => 'Enable SMS notifications for these customer actions',
	    'desc' => 'Pending',
	    'id' => $this->prefix . 'send_sms_pending',
	    'default' => 'yes',
	    'desc_tip' => __('Order received (unpaid)', TEXTDOMAIN),
	    'type' => 'checkbox',
	    'checkboxgroup' => 'start'
	);

	$fields[] = array(
	    'desc' => __('Failed', TEXTDOMAIN),
	    'id' => $this->prefix . 'send_sms_failed',
	    'default' => 'yes',
	    'desc_tip' => __('Payment failed or was declined (unpaid)', TEXTDOMAIN),
	    'type' => 'checkbox',
	    'checkboxgroup' => '',
	    'autoload' => false
	);
	$fields[] = array(
	    'desc' => __('Processing', TEXTDOMAIN),
	    'id' => $this->prefix . 'send_sms_processing',
	    'default' => 'yes',
	    'desc_tip' => __('Payment received', TEXTDOMAIN),
	    'type' => 'checkbox',
	    'checkboxgroup' => '',
	    'autoload' => false
	);
	$fields[] = array(
	    'desc' => __('Completed', TEXTDOMAIN),
	    'id' => $this->prefix . 'send_sms_completed',
	    'default' => 'yes',
	    'desc_tip' => __('Order fulfilled and complete', TEXTDOMAIN),
	    'type' => 'checkbox',
	    'checkboxgroup' => '',
	    'autoload' => false
	);

	$fields[] = array(
	    'desc' => __('On-Hold', TEXTDOMAIN),
	    'id' => $this->prefix . 'send_sms_on-hold',
	    'default' => 'yes',
	    'desc_tip' => __('Order received (unpaid)', TEXTDOMAIN),
	    'type' => 'checkbox',
	    'checkboxgroup' => '',
	    'autoload' => false
	);


	$fields[] = array(
	    'desc' => __('Cancelled', TEXTDOMAIN),
	    'id' => $this->prefix . 'send_sms_cancelled',
	    'default' => 'yes',
	    'desc_tip' => __('Cancelled by an admin or the customer', TEXTDOMAIN),
	    'type' => 'checkbox',
	    'checkboxgroup' => '',
	    'autoload' => false
	);
	$fields[] = array(
	    'desc' => __('Refunded', TEXTDOMAIN),
	    'id' => $this->prefix . 'send_sms_refunded',
	    'default' => 'yes',
	    'desc_tip' => __('Refunded by an admin', TEXTDOMAIN),
	    'type' => 'checkbox',
	    'checkboxgroup' => 'end',
	    'autoload' => false
	);


	$fields[] = array(
	    'title' => 'Default Message',
	    'id' => $this->prefix . 'default_sms_template',
	    'desc_tip' => __('This message will be sent by default if there are no any text in the following event message fields.', TEXTDOMAIN),
	    'default' => __('Your order #{{order_id}} is now {{order_status}}. Thank you for shopping at {{shop_name}}.', TEXTDOMAIN),
	    'type' => 'textarea',
	    'css' => 'min-width:500px;'
	);

	$fields[] = array(
	    'title' => __('Pending Message', TEXTDOMAIN),
	    'id' => $this->prefix . 'pending_sms_template',
	    'css' => 'min-width:500px;',
	    'type' => 'textarea'
	);
	$fields[] = array(
	    'title' => __('Failed Message', TEXTDOMAIN),
	    'id' => $this->prefix . 'failed_sms_template',
	    'css' => 'min-width:500px;',
	    'type' => 'textarea'
	);

	$fields[] = array(
	    'title' => __('Processing Message', TEXTDOMAIN),
	    'id' => $this->prefix . 'processing_sms_template',
	    'css' => 'min-width:500px;',
	    'type' => 'textarea'
	);
	$fields[] = array(
	    'title' => __('Completed Message', TEXTDOMAIN),
	    'id' => $this->prefix . 'completed_sms_template',
	    'css' => 'min-width:500px;',
	    'type' => 'textarea'
	);
	$fields[] = array(
	    'title' => __('On-Hold Message', TEXTDOMAIN),
	    'id' => $this->prefix . 'on-hold_sms_template',
	    'css' => 'min-width:500px;',
	    'type' => 'textarea'
	);
	$fields[] = array(
	    'title' => __('Cancelled Message', TEXTDOMAIN),
	    'id' => $this->prefix . 'cancelled_sms_template',
	    'css' => 'min-width:500px;',
	    'type' => 'textarea'
	);
	$fields[] = array(
	    'title' => __('Refund Message', TEXTDOMAIN),
	    'id' => $this->prefix . 'refunded_sms_template',
	    'css' => 'min-width:500px;',
	    'type' => 'textarea'
	);



	/*
	 * 
	 * Admin notifications
	 * 
	 */

	$fields[] = array('type' => 'sectionend', 'id' => TEXTDOMAIN . 'adminsettings');
	$fields[] = array(
	    'title' => 'Notification for Admin',
	    'type' => 'title',
	    'desc' => 'Enable admin notifications for new customer orders.',
	    'id' => TEXTDOMAIN . 'adminsettings'
	);

	$fields[] = array(
	    'title' => 'Receive Admin Notifications for New Orders.',
	    'id' => $this->prefix . 'enable_admin_sms',
	    'default' => 'no',
	    'type' => 'checkbox'
	);
	$fields[] = array(
	    'title' => 'Admin Mobile Number',
	    'id' => $this->prefix . 'admin_sms_recipients',
	    'desc_tip' => 'Enter admin mobile number begining with your country code.(e.g. 9471XXXXXXXX).',
	    'default' => '',
	    'type' => 'text'
	);
	$fields[] = array(
	    'title' => 'Message',
	    'id' => $this->prefix . 'admin_sms_template',
	    'desc_tip' => 'Customization tags for new order SMS: {{shop_name}}, {{order_id}}, {{order_amount}}. 160 Characters.',
	    'css' => 'min-width:500px;',
	    'default' => 'You have a new customer order for {{shop_name}}. Order #{{order_id}}, Total Value: {{order_amount}}',
	    'type' => 'textarea'
	);

	/*
	 * 
	 * API Credentials
	 * 
	 */

	$fields[] = array('type' => 'sectionend', 'id' => TEXTDOMAIN . 'apisettings');
	$fields[] = array(
	    'title' => __('Notify.lk Settings', TEXTDOMAIN),
	    'type' => 'title',
	    'desc' => 'Provide following details from your Notify.lk account. <a href="https://app.notify.lk/settings/api-keys" target="_blank">Click here</a> to go to API KEY section.',
	    'id' => TEXTDOMAIN . 'notifylk_settings'
	);

	$fields[] = array(
	    'title' => __('User ID', TEXTDOMAIN),
	    'id' => $this->prefix . 'user_id',
	    'desc_tip' => __('User id available in your NotifyLK account settings page.', TEXTDOMAIN),
	    'type' => 'text',
	    'css' => 'min-width:300px;',
	);
	$fields[] = array(
	    'title' => __('API Key', TEXTDOMAIN),
	    'id' => $this->prefix . 'api_key',
	    'desc_tip' => __('API key available in your NotifyLK account.', TEXTDOMAIN),
	    'type' => 'text',
	    'css' => 'min-width:300px;',
	);
	$fields[] = array(
	    'title' => __('Sender ID', TEXTDOMAIN),
	    'id' => $this->prefix . 'from_id',
	    'desc_tip' => __('Enter your NotifyLK purchased SenderID.', TEXTDOMAIN),
	    'type' => 'text',
	    'css' => 'min-width:300px;',
	);
	/*
	  $fields[] = array(
	  'desc' => __('Use if experiencing issues.', TEXTDOMAIN),
	  'title' => __('Log Api Errors', TEXTDOMAIN),
	  'id' => $this->prefix . 'log_errors',
	  'default' => 'no',
	  'type' => 'checkbox'
	  );
	 */
	$fields[] = array('type' => 'sectionend', 'id' => TEXTDOMAIN . 'customersettings');
	return $fields;
    }

}
