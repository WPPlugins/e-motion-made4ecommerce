<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_API_Payment_Gateways Class
 */
class WC_e_motion_m4ec_API_Payment_Gateways extends WC_e_motion_m4ec_API_Request
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


        $getGateways = WC()->payment_gateways()->payment_gateways;

        $this->log(__("Get payment gateways", 'e-motion-m4ec'));
        $gateways = array('success' => true, 'code' => 0, 'data' => $getGateways);

        wp_send_json($gateways);
    }

}

return new WC_e_motion_m4ec_API_Payment_Gateways();
