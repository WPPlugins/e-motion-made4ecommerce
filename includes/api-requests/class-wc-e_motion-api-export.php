<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_API_Export Class
 */
class WC_e_motion_m4ec_API_Export extends WC_e_motion_m4ec_API_Request
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
        global $wpdb;

        $this->validate_input(array("start_date", "end_date"));

        $page = max(1, isset($_GET['page']) ? absint($_GET['page']) : 1);
        $exported = 0;
        $tz_offset = get_option('gmt_offset') * 3600;
        $raw_start_date = wc_clean(urldecode($_GET['start_date']));
        $raw_end_date = wc_clean(urldecode($_GET['end_date']));
        $export_statuses = isset($_GET['export_statuses']) ? $_GET['export_statuses'] : WC_e_motion_m4ec_Integration::$export_statuses;


        if (!is_array($export_statuses)) {
            $export_statuses = explode(",", $export_statuses);
        }


        $export_limit = isset($_GET['export_limit']) ? absint($_GET['export_limit']) : E_MOTION_M4EC_EXPORT_LIMIT;

// Parse start and end date
        if ($raw_start_date && false === strtotime($raw_start_date)) {
            $month = substr($raw_start_date, 0, 2);
            $day = substr($raw_start_date, 2, 2);
            $year = substr($raw_start_date, 4, 4);
            $time = substr($raw_start_date, 9, 4);
            $start_date = gmdate("Y-m-d H:i:s", strtotime($year . '-' . $month . '-' . $day . ' ' . $time));
        } else {
            $start_date = gmdate("Y-m-d H:i:s", strtotime($raw_start_date));
        }

        if ($raw_end_date && false === strtotime($raw_end_date)) {
            $month = substr($raw_end_date, 0, 2);
            $day = substr($raw_end_date, 2, 2);
            $year = substr($raw_end_date, 4, 4);
            $time = substr($raw_end_date, 9, 4);
            $end_date = gmdate("Y-m-d H:i:s", strtotime($year . '-' . $month . '-' . $day . ' ' . $time));
        } else {
            $end_date = gmdate("Y-m-d H:i:s", strtotime($raw_end_date));
        }


//$this->trigger_error( __( 'Invalid request? :)', 'e-motion-m4ec' ) );


        $order_ids = $wpdb->get_col(
            $wpdb->prepare("
					SELECT ID FROM {$wpdb->posts}
					WHERE post_type = 'shop_order'
					AND post_status IN ( '" . implode("','", $export_statuses) . "' )
					AND %s <= post_modified_gmt
					AND post_modified_gmt <= %s
					ORDER BY post_modified_gmt DESC
					LIMIT %d, %d
				", $start_date, $end_date, $export_limit * ($page - 1), $export_limit
            )
        );
        $max_results = $wpdb->get_var(
            $wpdb->prepare("
					SELECT COUNT(ID) FROM {$wpdb->posts}
					WHERE post_type = 'shop_order'
					AND post_status IN ( '" . implode("','", $export_statuses) . "' )
					AND %s <= post_modified_gmt
					AND post_modified_gmt <= %s
				", $start_date, $end_date
            )
        );

//$orders_xml = $xml->createElement("Orders");
        $orderCount = 0;
        $order_response['export_info']['page'] = $page;
        $order_response['export_info']['pages'] = ceil($max_results / $export_limit);

        foreach ($order_ids as $order_id) {
            if (!apply_filters('woocommerce_e_motion_export_order', true, $order_id)) {
                continue;
            }

            $order = wc_get_order($order_id);
//order data
            $order_response['Orders'][$orderCount]['OrderInfo']['OrderNumber'] = ltrim($order->get_order_number(), '#');
            $order_response['Orders'][$orderCount]['OrderInfo']['OrderDate'] = gmdate("m/d/Y H:i", strtotime($order->order_date) - $tz_offset);
            $order_response['Orders'][$orderCount]['OrderInfo']['OrderStatus'] = $order->get_status();
            $order_response['Orders'][$orderCount]['OrderInfo']['LastModified'] = gmdate("m/d/Y H:i", strtotime($order->modified_date) - $tz_offset);
            $order_response['Orders'][$orderCount]['OrderInfo']['ShippingMethod'] = implode(' | ', $this->get_shipping_methods($order));
            $order_response['Orders'][$orderCount]['OrderInfo']['PaymentMethod'] = get_post_meta($order->id, '_payment_method', true);


            //get_post_meta( $order->id, '_payment_method', true );


            $order_response['Orders'][$orderCount]['OrderInfo']['OrderTotal'] = $order->get_total();
            $order_response['Orders'][$orderCount]['OrderInfo']['TaxAmount'] = $order->get_total_tax();
            if (class_exists('WC_COG')) {
                $order_response['Orders'][$orderCount]['OrderInfo']['CostOfGoods'] = wc_format_decimal($order->wc_cog_order_total_cost);
            }

            $order_response['Orders'][$orderCount]['OrderInfo']['ShippingAmount'] = $order->get_total_shipping();
            $order_response['Orders'][$orderCount]['OrderInfo']['CustomerNotes'] = $order->customer_note;
            $order_response['Orders'][$orderCount]['OrderInfo']['InternalNotes'] = implode(" | ", $this->get_order_notes($order));

// Custom fields - 1 is used for coupon codes
            $order_response['Orders'][$orderCount]['OrderInfo']['Coupon'] = implode(" | ", $order->get_used_coupons());
// Custom fields 2 and 3 can be mapped to a custom field via the following filters

            if ($meta_key = apply_filters('woocommerce_e_motion_export_custom_field_2', '')) {
                $order_response['Orders'][$orderCount]['OrderInfo']['CustomField2'] = apply_filters('woocommerce_e_motion_export_custom_field_2_value', get_post_meta($order_id, $meta_key, true), $order_id);
            }

            if ($meta_key = apply_filters('woocommerce_e_motion_export_custom_field_3', '')) {
                $order_response['Orders'][$orderCount]['OrderInfo']['CustomField3'] = apply_filters('woocommerce_e_motion_export_custom_field_3_value', get_post_meta($order_id, $meta_key, true), $order_id);
            }


// Customer data
            $order_response['Orders'][$orderCount]['CustomerData']['CustomerCode'] = $order->billing_email;


// Bill to
            $order_response['Orders'][$orderCount]['CustomerData']['BillTo']['FirstName'] = $order->billing_first_name;
            $order_response['Orders'][$orderCount]['CustomerData']['BillTo']['LastName'] = $order->billing_last_name;
            $order_response['Orders'][$orderCount]['CustomerData']['BillTo']['FullName'] = $order->billing_first_name . " " . $order->billing_last_name;


            $order_response['Orders'][$orderCount]['CustomerData']['BillTo']['Company'] = $order->billing_company;
            $order_response['Orders'][$orderCount]['CustomerData']['BillTo']['Phone'] = $order->billing_phone;
            $order_response['Orders'][$orderCount]['CustomerData']['BillTo']['Email'] = $order->billing_email;


// Ship to

            if (empty($order->shipping_country)) {
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['FirstName'] = $order->billing_first_name;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['LastName'] = $order->billing_last_name;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['FullName'] = $order->billing_first_name . " " . $order->billing_last_name;

                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Company'] = $order->billing_company;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Address1'] = $order->billing_address_1;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Address2'] = $order->billing_address_2;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['City'] = $order->billing_city;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['State'] = $order->billing_state;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['PostalCode'] = $order->billing_postcode;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Country'] = $order->billing_country;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Phone'] = $order->billing_phone;
            } else {
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['FirstName'] = $order->shipping_first_name;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['LastName'] = $order->shipping_last_name;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['FullName'] = $order->shipping_first_name . " " . $order->shipping_last_name;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Company'] = $order->shipping_company;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Address1'] = $order->shipping_address_1;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Address2'] = $order->shipping_address_2;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['City'] = $order->shipping_city;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['State'] = $order->shipping_state;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['PostalCode'] = $order->shipping_postcode;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Country'] = $order->shipping_country;
                $order_response['Orders'][$orderCount]['CustomerData']['ShipTo']['Phone'] = $order->shipping_phone;
            }

// Item data
            $found_item = false;
            $itemCount = 0;

            foreach ($order->get_items() as $item_id => $item) {
                $product = $order->get_product_from_item($item);

                if (!$product || !$product->needs_shipping()) {
                    continue;
                }

                $found_item = true;
                $image_id = $product->get_image_id();

                if ($image_id) {
                    $image_url = current(wp_get_attachment_image_src($image_id, 'shop_thumbnail'));
                } else {
                    $image_url = '';
                }


                $order_response['Orders'][$orderCount]['Items'][$itemCount]['LineItemID'] = $item_id;
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['ProductID'] = $this->returnProductCode($product->product_type, $product->id, $product->variation_id);
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['SKU'] = $product->get_sku();
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['Name'] = $product->get_title();
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['ImageUrl'] = $image_url;
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['Weight'] = wc_get_weight($product->get_weight(), 'oz');
                //$order_response['Orders'][$orderCount]['Items'][$itemCount]['WeightUnits'] = 'Ounces';
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['Quantity'] = $item['qty'];
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['UnitPrice'] = $order->get_item_subtotal($item, false, true);


                if ($item['item_meta']) {
                    if (version_compare(WC_VERSION, '2.4.0', '<')) {
                        $item_meta = new WC_Order_Item_Meta($item['item_meta']);
                    } else {
                        $item_meta = new WC_Order_Item_Meta($item);
                    }
                    $formatted_meta = $item_meta->get_formatted('_');

                    $optionCount = 0;

                    if (!empty($formatted_meta)) {


                        foreach ($formatted_meta as $meta_key => $meta) {


                            $order_response['Orders'][$orderCount]['Items'][$itemCount]['Options'][$optionCount]['Name'] = $meta['label'];
                            $order_response['Orders'][$orderCount]['Items'][$itemCount]['Options'][$optionCount]['Value'] = $meta['value'];

                            $optionCount++;
                        }
                    }
                }


                $itemCount++;
            }

            if (!$found_item) {
                continue;
            }


            // Append cart level discount line
            if ($order->get_total_discount()) {
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['SKU'] = 'total-discount';
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['Name'] = __('Total Discount', 'e-motion-m4ec');
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['Adjustment'] = 'true';
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['Quantity'] = 1;
                $order_response['Orders'][$orderCount]['Items'][$itemCount]['UnitPrice'] = $order->get_total_discount() * -1;
            }


            $orderCount++;
        }

        $order_response['export_info']['orderCount'] = $orderCount;
        $order_response['export_info']['exportLimit'] = $export_limit;


        $this->log(sprintf(__("Exported %s orders", 'e-motion-m4ec'), $orderCount));

        $orderExport = array('success' => true, 'code' => 0, 'data' => $order_response);


        wp_send_json($orderExport);
    }

    /**
     * Get shipping method names
     * @param  WC_Order $order
     * @return array
     */
    private function get_shipping_methods($order)
    {
        $shipping_methods = $order->get_shipping_methods();
        $shipping_method_names = array();

        foreach ($shipping_methods as $shipping_method) {
            $shipping_method_names[] = $shipping_method['name'];
        }

        return $shipping_method_names;
    }

    /**
     * Get Order Notes
     * @param  WC_Order $order
     * @return array
     */
    private function get_order_notes($order)
    {
        $args = array(
            'post_id' => $order->id,
            'approve' => 'approve',
            'type' => 'order_note'
        );

        remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);

        $notes = get_comments($args);

        add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);

        $order_notes = array();

        foreach ($notes as $note) {
            if ($note->comment_author !== __('WooCommerce', 'e-motion-m4ec')) {
                $order_notes[] = $note->comment_content;
            }
        }

        return $order_notes;
    }

}

return new WC_e_motion_m4ec_API_Export();
