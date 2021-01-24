<?php

class NotifyLKTrigger {
    /*
     * Declare setting prefix and other values.
     */

    private $prefix = 'notifylk_sms_woo_';
    private $ApiKey, $userId, $sendId, $adminRecipients;
    private $yesPending, $yesOnHold, $yesProcessing, $yesCompleted, $yesCancelled, $yesRefunded, $yesFailed, $yesAdminMsg;
    private $contentDefault, $contentPending, $contentOnHold, $contentProcessing, $contentCompleted, $contentCancelled, $contentRefunded, $contentFailed, $contentAdmin;

    /*
     * Initialize values.
     */

    public function __construct() {
        /*
         * Get NotifyLK configuration settings.
         */
        $this->ApiKey = get_option($this->prefix . 'api_key');
        $this->userId = get_option($this->prefix . 'user_id');
        $this->sendId = get_option($this->prefix . 'from_id');
        $this->adminRecipients = get_option($this->prefix . 'admin_sms_recipients');

        /*
         * Get enabled or desabled.
         */
        $this->yesPending = get_option($this->prefix . 'send_sms_pending') == 'yes';
        $this->yesOnHold = get_option($this->prefix . 'send_sms_on-hold') == 'yes';
        $this->yesProcessing = get_option($this->prefix . 'send_sms_processing') == 'yes';
        $this->yesCompleted = get_option($this->prefix . 'send_sms_completed') == 'yes';
        $this->yesCancelled = get_option($this->prefix . 'send_sms_cancelled') == 'yes';
        $this->yesRefunded = get_option($this->prefix . 'send_sms_refunded') == 'yes';
        $this->yesFailed = get_option($this->prefix . 'send_sms_failed') == 'yes';
        $this->yesAdminMsg = get_option($this->prefix . 'enable_admin_sms') == 'yes';

        /*
         * Get messages
         */
        $this->contentDefault = get_option($this->prefix . 'default_sms_template');
        $this->contentPending = get_option($this->prefix . 'pending_sms_template');
        $this->contentOnHold = get_option($this->prefix . 'on-hold_sms_template');
        $this->contentProcessing = get_option($this->prefix . 'processing_sms_template');
        $this->contentCompleted = get_option($this->prefix . 'completed_sms_template');
        $this->contentCancelled = get_option($this->prefix . 'cancelled_sms_template');
        $this->contentRefunded = get_option($this->prefix . 'refunded_sms_template');
        $this->contentFailed = get_option($this->prefix . 'failed_sms_template');
        $this->contentAdmin = get_option($this->prefix . 'admin_sms_template');
    }

    public function notify_send_admin_sms_for_woo_new_order($order_id) {
        if ($this->yesAdminMsg)
            $this->NotifyLKsend($order_id, 'admin-order');
    }

    public function notify_send_customer_sms_for_woo_order_status_pending($order_id) {
        if ($this->yesPending)
            $this->NotifyLKsend($order_id, 'pending');
    }

    public function notify_send_customer_sms_for_woo_order_status_failed($order_id) {
        if ($this->yesFailed)
            $this->NotifyLKsend($order_id, 'failed');
    }

    public function notify_send_customer_sms_for_woo_order_status_on_hold($order_id) {
        if ($this->yesOnHold)
            $this->NotifyLKsend($order_id, 'on-hold');
    }

    public function notify_send_customer_sms_for_woo_order_status_processing($order_id) {
        if ($this->yesProcessing) {
            $this->NotifyLKsend($order_id, 'processing');
        }
    }

    public function notify_send_customer_sms_for_woo_order_status_completed($order_id) {
        if ($this->yesCompleted)
            $this->NotifyLKsend($order_id, 'completed');
    }

    public function notify_send_customer_sms_for_woo_order_status_refunded($order_id) {
        if ($this->yesRefunded)
            $this->NotifyLKsend($order_id, 'refunded');
    }

    public function notify_send_customer_sms_for_woo_order_status_cancelled($order_id) {
        if ($this->yesCancelled)
            $this->NotifyLKsend($order_id, 'cancelled');
    }

    public static function shortCode($message, $order_details) {
        $replacements_string = array(
            '{{shop_name}}' => get_bloginfo('name'),
            '{{order_id}}' => $order_details->get_order_number(),
            '{{order_amount}}' => $order_details->get_total(),
            '{{order_status}}' => ucfirst($order_details->get_status()),
            '{{first_name}}' => ucfirst($order_details->billing_first_name),
            '{{last_name}}' => ucfirst($order_details->billing_last_name),
            '{{billing_city}}' => ucfirst($order_details->billing_city),
            '{{customer_phone}}' => $order_details->billing_phone,
        );
        return str_replace(array_keys($replacements_string), $replacements_string, $message);
    }

    public static function reformatPhoneNumbers($value) {
        $number = preg_replace("/[^0-9]/", "", $value);
        if (strlen($number) == 9) {
            $number = "94" . $number;
        } elseif (strlen($number) == 10 && substr($number, 0, 1) == '0') {
            $number = "94" . ltrim($number, "0");
        } elseif (strlen($number) == 12 && substr($number, 0, 3) == '940') {
            $number = "94" . ltrim($number, "940");
        }
        return $number;
    }

    private function NotifyLKsend($order_id, $status) {
        $order_details = new WC_Order($order_id);
        $message = '';
        switch ($status) {
            case 'pending':
                $message = $this->contentPending;
                break;
            case 'failed':
                $message = $this->contentFailed;
                break;
            case 'on-hold':
                $message = $this->contentOnHold;
                break;
            case 'processing':
                $message = $this->contentProcessing;
                break;
            case 'completed':
                $message = $this->contentCompleted;
                break;
            case 'refunded':
                $message = $this->contentRefunded;
                break;
            case 'cancelled':
                $message = $this->contentCancelled;
                break;
            case 'admin-order':
                $message = $this->contentAdmin;
                break;
            default:
                $message = $this->contentDefault;
                break;
        }
        $message = (empty($message) ? $this->contentDefault : $message);
        $message = self::shortCode($message, $order_details);
        $pn = ('admin-order' === $status ? $this->adminRecipients : $order_details->billing_phone);
        $phone = $this->reformatPhoneNumbers($pn);
        $apiInt = new \NotifyLk\Api\SmsApi();

        $fName = $order_details->billing_first_name;
        $lName = $order_details->billing_last_name;
        $bEmail = $order_details->billing_email;        
        $addr1 = $order_details->billing_address_1;
        $addr2 = $order_details->billing_address_2;
        $bCity = $order_details->billing_city;
        $postC = $order_details->shipping_postcode;        
        $address = $addr1 . ', ' . $addr2 . ', ' . $bCity . ', ' . $postC; 
        try {
            $apiInt->sendSMS($this->userId, $this->ApiKey, $message, $phone, $this->sendId, $fName, $lName, $bEmail, $address);
        } catch (\Throwable $th) {
            //throw $th;
        }    
        
    }

}
