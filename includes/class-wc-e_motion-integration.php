<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_e_motion_m4ec_Integration Class
 */
class WC_e_motion_m4ec_Integration extends WC_Integration
{

//    public static $api_username = null;
    /**
     * @var null
     */
    public static $api_key = null;
    /**
     * @var null
     */
    public static $auth_key = null;
    /**
     * @var null|string
     */
    public static $secret_key = null;
    /**
     * @var array
     */
    public static $export_statuses = array();
    /**
     * @var array
     */
    public static $mode = array();
    /**
     * @var bool
     */
    public static $logging_enabled = true;
    /**
     * @var string
     */
    public static $logo;

    /**
     * Constructor
     */
    public function __construct()
    {

        $this->id = 'e_motion';
        self::$logo = '<img src="' . plugins_url('e-motion-made4ecommerce/images/logo-emotion.svg') . '">';
        $this->method_title = __('e-motion ', 'e-motion-m4ec');
        $this->method_description = __('e-motion allows you to retrieve orders and ship your products with ease.<br>' . self::$logo, 'e-motion-m4ec');
        if (!get_option('woocommerce_e_motion_auth_key', false)) {
            update_option('woocommerce_e_motion_auth_key', $this->generate_key());
        }

        // Load admin form
        $this->init_form_fields();

        // Load settings
        $this->init_settings();


//        self::$api_username = $this->get_option('api_username', false);
        self::$api_key = $this->get_option('api_key', false);

        //generate auth key
        self::$auth_key = get_option('woocommerce_e_motion_auth_key', false);
        self::$secret_key = $this->secret_key();

        self::$export_statuses = $this->get_option('export_statuses', array('wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled'));
        self::$mode = $this->get_option('mode');

        self::$logging_enabled = 'yes' === $this->get_option('logging_enabled', 'yes');
//
//        // Force saved value
        $this->settings['api_key'] = self::$api_key;
//        $this->settings['api_username'] = self::$api_username;
        $this->settings['auth_key'] = self::$auth_key;
        $this->settings['secret_key'] = self::$secret_key;
        $this->settings['mode'] = self::$mode;


        // Hooks
        add_action('woocommerce_update_options_integration_e_motion', array($this, 'process_admin_options'));
        add_filter('woocommerce_subscriptions_renewal_order_meta_query', array($this, 'subscriptions_renewal_order_meta_query'), 10, 4);
        add_filter('plugin_action_links_' . E_MOTION_M4EC_PLUGIN_BASENAME, array($this, 'emt_plugin_action_links'));


//        if (!self::$api_key || !self::$api_username) {
        if (!self::$api_key) {
            add_action('admin_notices', array($this, 'settings_notice'));
        }

    }

    /**
     *
     */
    public function process_admin_options()
    {
        parent::process_admin_options();

        //print_r($this->settings);
    }

    /**
     * Generate auth key
     * @return string
     */
    public function generate_key()
    {
        $to_hash = get_current_user_id() . date('U') . mt_rand();
        return 'WCEMT-' . hash_hmac('md5', $to_hash, wp_hash($to_hash));
    }

    /**
     * Generate secret key
     * @return string
     */
    public function secret_key()
    {

        //print_r(md5(self::$api_key . self::$auth_key));
        return md5(self::$api_key . self::$auth_key);
    }


    /**
     * Init integration form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = include('data/data-settings.php');
    }

    /**
     * Prevents WooCommerce Subscriptions from copying across certain meta keys to renewal orders.
     * @param  array $order_meta_query
     * @param  int $original_order_id
     * @param  int $renewal_order_id
     * @param  string $new_order_role
     * @return array
     */
    public function subscriptions_renewal_order_meta_query($order_meta_query, $original_order_id, $renewal_order_id, $new_order_role)
    {
        if ('parent' == $new_order_role) {
            $order_meta_query .= " AND `meta_key` NOT IN ("
                . "'_tracking_provider', "
                . "'_tracking_number', "
                . "'_date_shipped', "
                . "'_order_custtrackurl', "
                . "'_order_custcompname', "
                . "'_order_trackno', "
                . "'_order_trackurl' )";
        }
        return $order_meta_query;
    }

    /**
     * Settings prompt
     */
    public function settings_notice()
    {
        if (!empty($_GET['tab']) && 'integration' === $_GET['tab']) {
            return;
        }
        ?>
        <div id="message" class="updated woocommerce-message">
            <p><?php _e('<strong>e-motion</strong> is almost ready &#8211; Please configure the plugin to begin exporting orders.', 'e-motion-m4ec'); ?></p>
            <p class="submit"><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=integration'); ?>"
                                 class="button-primary"><?php _e('Settings', 'e-motion-m4ec'); ?></a></p>
        </div>
        <?php
    }

    /**
     * @param $links
     * @return array
     */
    function emt_plugin_action_links($links)
    {

        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=integration') . '" title="' . esc_attr(__('View e-motion Settings', 'e-motion-m4ec')) . '">' . __('Settings', 'e-motion-m4ec') . '</a>',
        );

        return array_merge($action_links, $links);
    }

}
