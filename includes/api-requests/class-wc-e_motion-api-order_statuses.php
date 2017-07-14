<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_API_Order_Statuses Class
 */
class WC_e_motion_m4ec_API_Order_Statuses extends WC_e_motion_m4ec_API_Request
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


        $getOrderStatuses = wc_get_order_statuses();
        $this->log(__("Get order statuses", 'e-motion-m4ec'));
        $order_statuses = array('success' => true, 'code' => 0, 'data' => $getOrderStatuses);

        while (ob_get_level()) {
            ob_end_clean();
        }

        wp_send_json($order_statuses);
    }

}

return new WC_e_motion_m4ec_API_Order_Statuses();
