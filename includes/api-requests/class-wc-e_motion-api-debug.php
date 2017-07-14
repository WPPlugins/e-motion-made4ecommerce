<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_API_Payment_Gateways Class
 */
class WC_e_motion_m4ec_API_Debug extends WC_e_motion_m4ec_API_Request
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

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $getSiteInfo['wp_version'] = get_bloginfo('version');
        $getSiteInfo['wp_wpurl'] = get_bloginfo('wpurl');
        $getSiteInfo['wp_url'] = get_bloginfo('url');
        $getSiteInfo['plugins'] = get_plugins();

        $this->log(__("Get site info", 'e-motion-m4ec'));
        $site_info = array('success' => true, 'code' => 0, 'data' => $getSiteInfo);
        wp_send_json($site_info);

    }

}

return new WC_e_motion_m4ec_API_Debug();
