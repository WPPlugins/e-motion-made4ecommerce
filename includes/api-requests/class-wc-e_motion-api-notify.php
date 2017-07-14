<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_API_Notify Class
 */
class WC_e_motion_m4ec_API_Notify extends WC_e_motion_m4ec_API_Request
{

    private static $labelCode = 17;

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
        global $wpdb;

        $this->log(file_get_contents('php://input'));
        $emtJSON = json_decode(file_get_contents('php://input'), true);


        if (is_array($emtJSON)) {


            $order_number = array_key_exists('referenceid', $emtJSON) ? $this->get_order_id($emtJSON['referenceid']) : '';
            $valid = array_key_exists('valid', $emtJSON) ? wc_clean($emtJSON['valid']) : '';
            $result = array_key_exists('result', $emtJSON) ? wc_clean($emtJSON['result']) : '';


            $emotionReference = array_key_exists('emotionreference', $emtJSON) ? wc_clean($emtJSON['emotionreference']) : '';
            $emotionStatusID = array_key_exists('emotionStatusID', $emtJSON) ? wc_clean($emtJSON['emotionStatusID']) : '';
            $emotionStatus = array_key_exists('emotionStatus', $emtJSON) ? wc_clean($emtJSON['emotionStatus']) : '';
            $emotionDate = array_key_exists('date', $emtJSON) ? $emtJSON['date'] : '';


            //only label case

            $urlLabel = array_key_exists('url_label', $emtJSON) ? wc_clean($emtJSON['url_label']) : '';
            $carrier = array_key_exists('carrier', $emtJSON) ? wc_clean($emtJSON['carrier']) : '';
            $tracking_number = array_key_exists('shipping_number', $emtJSON) ? wc_clean($emtJSON['shipping_number']) : '';

            //only status case

            $wcStatusId = array_key_exists('wcStatusId', $emtJSON) ? wc_clean($emtJSON['wcStatusId']) : '';
            $linkServizioTracking = array_key_exists('linkServizioTracking', $emtJSON) ? wc_clean($emtJSON['linkServizioTracking']) : '';


            //common parameters

            $order = wc_get_order($order_number);
            $order_note = '';

            if (empty($order->id)) {
                exit;
            }

            //if label
            if ($emotionStatusID == self::$labelCode) {


                $urlLabelHTML = '<a href="' . $urlLabel . '" target="_blank">' . __('Click here to print your label', 'e-motion-m4ec') . '</a>';

                $order_note = sprintf(__('Items shipped via %s on %s with tracking number %s.<br>%s', 'e-motion-m4ec'), esc_html($carrier), date_i18n(get_option('date_format'), strtotime($emotionDate)), $tracking_number, $urlLabelHTML);
                // Tracking information - WC Shipment Tracking extension

                if (class_exists('WC_Shipment_Tracking')) {
                    update_post_meta($order->id, '_tracking_provider', strtolower($carrier));
                    update_post_meta($order->id, '_tracking_number', $tracking_number);
                    update_post_meta($order->id, '_date_shipped', $emotionDate);
                    $is_customer_note = 0;
                } else {
                    $is_customer_note = 0; //1
                }

                $order->add_order_note($order_note, $is_customer_note);
            } else {


                $order->update_status($wcStatusId);
                $this->log(sprintf(__("Updated order %s to status %s", 'e-motion-m4ec'), $order->id, $wcStatusId));
            }


            // Trigger action for other integrations
            do_action('woocommerce_e_motion_notify', $order, array('tracking_number' => $tracking_number, 'carrier' => $carrier, 'ship_date' => $emotionDate));


            $notify = array('result' => 'OK', 'message' => 'Stato spedizione aggiornato', 'versione' => 'WooCommerceV1');
            wp_send_json($notify);
        }
    }

    /**
     * Get the order ID from the order number
     *
     * @param string $order_number
     * @return integer
     */
    private function get_order_id($order_number)
    {
        if (class_exists('WC_Seq_Order_Number')) {

            global $wc_seq_order_number;

            $order_id = $wc_seq_order_number->find_order_by_order_number($order_number);
        } elseif (class_exists('WC_Seq_Order_Number_Pro')) {

            global $wc_seq_order_number_pro;

            if (isset($wc_seq_order_number_pro)) {
                $order_id = $wc_seq_order_number_pro->find_order_by_order_number($order_number);
            } else {
                $order_id = WC_Seq_Order_Number_Pro::instance()->find_order_by_order_number($order_number);
            }
        } else {
            $order_id = $order_number;
        }

        if (0 === $order_id) {
            $order_id = $order_number;
        }

        return absint($order_id);
    }

}

return new WC_e_motion_m4ec_API_Notify();


