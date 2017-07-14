<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once('api-requests/class-wc-e_motion-api-request.php');

/**
 * WC_e_motion_m4ec_API Class
 */
class WC_e_motion_m4ec_API extends WC_e_motion_m4ec_API_Request
{

    /** @var boolean Stores whether or not e_motion has been authenticated */
    private static $authenticated = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        nocache_headers();


        if (!defined('DONOTCACHEPAGE')) {
            define("DONOTCACHEPAGE", "true");
        }

        if (!defined('DONOTCACHEOBJECT')) {
            define("DONOTCACHEOBJECT", "true");
        }

        if (!defined('DONOTCACHEDB')) {
            define("DONOTCACHEDB", "true");
        }

        self::$authenticated = false;


        $this->request();
    }

    /**
     * Has API been authenticated?
     * @return bool
     */
    public static function authenticated()
    {
        return self::$authenticated;
    }

    /**
     * Handle the request
     */
    public function request()
    {


        if (empty($_GET['auth_key'])) {
            $this->trigger_error(__('Authentication key is required', 'e-motion-m4ec'), 1);
        }

        // echo WC_e_motion_m4ec_Integration::$secret_key;

        if (!hash_equals(sanitize_text_field($_GET['auth_key']), WC_e_motion_m4ec_Integration::$secret_key)) {
            $this->trigger_error(__('Invalid authentication key', 'e-motion-m4ec'), 2);
        }

        self::$authenticated = true;

        $request = $_GET;

        if (isset($request['action'])) {
            $this->request = array_map('sanitize_text_field', $request);
        } else {
            $this->trigger_error(__('Action is required', 'e-motion-m4ec'), 3);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        if (in_array($this->request['action'], array('ping', 'export', 'notify', 'order_statuses', 'payment_gateways', 'get_order', 'notify', 'debug'))) {
            $this->log(sprintf(__('Input params: %s', 'e-motion-m4ec'), http_build_query($this->request)));
            $request_class = include('api-requests/class-wc-e_motion-api-' . $this->request['action'] . '.php');


            $request_class->request();
        } else {
            $this->trigger_error(sprintf(__('%s is not a valid action', 'e-motion-m4ec'), $this->request['action']), 4);
        }

        exit;
    }


}

new WC_e_motion_m4ec_API();
