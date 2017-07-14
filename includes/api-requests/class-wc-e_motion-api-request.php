<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_API_Request Class
 */
abstract class WC_e_motion_m4ec_API_Request
{

    /**
     * Stores logger class
     * @var WC_Logger
     */
    private $log = null;

    /**
     * Log something
     * @param  string $message
     */
    public function log($message)
    {
        if ('no' === WC_e_motion_m4ec_Integration::$logging_enabled) {
            return;
        }
        if (is_null($this->log)) {
            $this->log = new WC_Logger();
        }
        $this->log->add('e-motion-m4ec', $message);
    }

    /**
     * Run the request
     */
    public function request()
    {

    }

    /**
     * Validate data
     * @param  array $required_fields fields to look for
     */
    function validate_input($required_fields)
    {
        foreach ($required_fields as $required) {
            if (empty($_GET[$required])) {
                $this->trigger_error(sprintf(__('Missing required param: %s', 'e-motion-m4ec'), $required), 5);
            }
        }
    }

    /**
     * Trigger and log an error
     * @param  string $message
     */
    public function trigger_error($message, $code = NULL)
    {
        $this->log($message);
        $response = array('success' => false, 'code' => $code, 'data' => $message);
        wp_send_json($response);
    }

    /**
     * @param $type
     * @param $code
     * @param $variation
     * @return string
     */
    public function returnProductCode($type, $code, $variation)
    {

        switch ($type) {
            case 'variation':
                return (string)($code . '_' . $variation);

                break;

            default:
                return (string)$code;
                break;
        }
    }

}
