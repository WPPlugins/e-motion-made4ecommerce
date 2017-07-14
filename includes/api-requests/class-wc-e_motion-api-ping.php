<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_API_Payment_Gateways Class
 */
class WC_e_motion_m4ec_API_Ping extends WC_e_motion_m4ec_API_Request
{

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!WC_e_motion_m4ec_API::authenticated()) {
            exit;
        }
    }

    /**
     * Do the request
     */
    public function request()
    {

        $ping = array('success' => true, 'code' => 0, 'data' => 'Connected with e-motion');
        wp_send_json($ping);
    }

}

return new WC_e_motion_m4ec_API_Ping();
