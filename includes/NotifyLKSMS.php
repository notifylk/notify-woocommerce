<?php

class NotifyLKSMS
{

    public $prefix = 'notifylk_sms_woo_';

    public function __construct($baseFile = null)
    {
        $this->init();
    }

    private function init()
    {
        /*
         * Add NotifyLK SMS to woo-commerce settings.
         */
        $triggerAPI = new NotifyLKTrigger();
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab') , 50);
        add_action('woocommerce_settings_tabs_settings_tab_notifylk', array($this, 'settings_tab'));
        add_action('woocommerce_update_options_settings_tab_notifylk', array($this, 'update_settings'));

        /*
         * Send new order admin SMS
         */
        add_action('woocommerce_order_status_processing', array($triggerAPI, 'notify_send_admin_sms_for_woo_new_order'), 10, 1);
    }

    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs['settings_tab_notifylk'] = __('Notify.lk SMS', $this->prefix);
        return $settings_tabs;
    }

    public function update_settings()
    {
        woocommerce_update_options($this->getFields());
    }

    public function settings_tab()
    {
        woocommerce_admin_fields($this->getFields());
    }

    private function getFields()
    {

        $all_statusses = wc_get_order_statuses();

        /*
         * 
         * Customer Notifications
         * 
         */


        $fields[] = array(
            'title' => 'Notifications for Customer',
            'type' => 'title',
            'desc' => 'Send SMS to customer\'s mobile phone. Will be sent to the phone number which customer is providing while checkout process.',
            'id' => $this->prefix . 'customersettings'
        );

        $fields[] = array(
            'title' => 'Default Message',
            'id' => $this->prefix . 'default_sms_template',
            'desc_tip' => __('This message will be sent by default if there are no any text in the following event message fields.', $this->prefix),
            'default' => __('Your order #{{order_id}} is now {{order_status}}. Thank you for shopping at {{shop_name}}.', $this->prefix),
            'type' => 'textarea',
            'css' => 'min-width:500px;min-height:80px;'
        );

        foreach ($all_statusses as $key => $val) {
            $key = str_replace("wc-", "", $key);
            $fields[] = array(
                'title' => $val,
                'desc' => 'Enable "' . $val . '" status alert',
                'id' => $this->prefix . 'send_sms_' . $key,
                'default' => 'yes',
                'type' => 'checkbox',
            );
            $fields[] = array(
                'id' => $this->prefix . $key . '_sms_template',
                'type' => 'textarea',
                'placeholder' => 'SMS Content for the ' . $val . ' event',
                'css' => 'min-width:500px;margin-top:-25px;min-height:80px;'
            );
        }

        /**
         * 
         * Customer Note settings
         * 
         */

        $fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'notesettings');
        $fields[] = array(
            'title' => 'Customer Note Notifications',
            'type' => 'title',
            'desc' => 'Enable SMS notifications for new customer notes.',
            'id' => $this->prefix . 'notesettings'
        );

        $fields[] = array(
            'title' => 'Send Notes Alerts',
            'id' => $this->prefix . 'enable_notes_sms',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => 'Enable SMS alerts for new customer notes'
        );

        $fields[] = array(
            'title' => 'Note Message Prefix',
            'id' => $this->prefix . 'note_sms_template',
            'desc_tip' => 'Text you provide here will be prepended to your customer note.',
            'css' => 'min-width:500px;',
            'default' => 'You have a new note: ',
            'type' => 'textarea'
        );


        /*
         * 
         * Admin notifications
         * 
         */

        $fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'adminsettings');
        $fields[] = array(
            'title' => 'Notification for Admin',
            'type' => 'title',
            'desc' => 'Enable admin notifications for new customer orders.',
            'id' => $this->prefix . 'adminsettings'
        );

        $fields[] = array(
            'title' => 'Receive Admin Notifications',
            'id' => $this->prefix . 'enable_admin_sms',
            'desc' => 'Enable admin notifications for new customer orders.',
            'default' => 'no',
            'type' => 'checkbox'
        );
        $fields[] = array(
            'title' => 'Admin Mobile Number',
            'id' => $this->prefix . 'admin_sms_recipients',
            'desc' => 'Enter admin mobile numbers. You can use multiple numbers by separating with a comma.<br> Example: 0777123456, 0712755777.',
            'desc_tip' => 'Enter admin mobile numbers. You can use multiple numbers by separating with a comma.<br> Example: 0777123456, 0712755777.',
            'default' => '',
            'type' => 'text'
        );
        $fields[] = array(
            'title' => 'Message',
            'id' => $this->prefix . 'admin_sms_template',
            'desc_tip' => 'Customization tags for new order SMS: {{shop_name}}, {{order_id}}, {{order_amount}}.',
            'css' => 'min-width:500px;',
            'default' => 'You have a new customer order for {{shop_name}}. Order #{{order_id}}, Total Value: {{order_amount}}',
            'type' => 'textarea'
        );

        /*
         * 
         * API Credentials
         * 
         */

        $fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'apisettings');
        $fields[] = array(
            'title' => __('Notify.lk Settings', $this->prefix),
            'type' => 'title',
            'desc' => 'Provide following details from your Notify.lk account. <a href="https://app.notify.lk/settings/api-keys" target="_blank">Click here</a> to go to API KEY section.',
            'id' => $this->prefix . 'notifylk_settings'
        );

        $fields[] = array(
            'title' => __('User ID', $this->prefix),
            'id' => $this->prefix . 'user_id',
            'desc_tip' => __('User id available in your NotifyLK account settings page.', $this->prefix),
            'type' => 'text',
            'css' => 'min-width:300px;',
        );
        $fields[] = array(
            'title' => __('API Key', $this->prefix),
            'id' => $this->prefix . 'api_key',
            'desc_tip' => __('API key available in your NotifyLK account.', $this->prefix),
            'type' => 'text',
            'css' => 'min-width:300px;',
        );
        $fields[] = array(
            'title' => __('Sender ID', $this->prefix),
            'id' => $this->prefix . 'from_id',
            'desc_tip' => __('Enter your NotifyLK purchased SenderID.', $this->prefix),
            'type' => 'text',
            'css' => 'min-width:300px;',
        );
        /*
          $fields[] = array(
          'desc' => __('Use if experiencing issues.', $this->prefix),
          'title' => __('Log Api Errors', $this->prefix),
          'id' => $this->prefix . 'log_errors',
          'default' => 'no',
          'type' => 'checkbox'
          );
         */
        $fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'customersettings');


        /*
         * Shortcodes and its descriptions.
         */

        $avbShortcodes = array(
            '{{first_name}}' => "First name of the customer.",
            '{{last_name}}' => "Last name of the customer.",
            '{{shop_name}}' => 'Your shop name (' . get_bloginfo('name') . ').',
            '{{order_id}}' => 'Ther order ID.',
            '{{order_amount}}' => "Current order amount.",
            '{{order_status}}' => 'Current order status (Pending, Failed, Processing, etc...).',
            '{{billing_city}}' => 'The city in the customer billing address (If available).',
            '{{customer_phone}}' => 'Customer mobile number (If given).'
        );

        $shortcode_desc = '';
        foreach ($avbShortcodes as $handle => $description) {
            $shortcode_desc .= '<b>' . $handle . '</b> - ' . $description . '<br>';
        }

        $fields[] = array(
            'title' => __('Available Shortcodes', $this->prefix),
            'type' => 'title',
            'desc' => 'These shortcodes can be used in your message body contents. <br><br>' . $shortcode_desc,
            'id' => $this->prefix . 'notifylk_settings'
        );

        $fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'apisettings');
        return $fields;
    }
}
