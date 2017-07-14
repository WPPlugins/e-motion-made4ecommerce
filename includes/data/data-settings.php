<?php

$mode = array(
    0 => __('Sandbox (only for test)', 'e-motion-m4ec'),
    1 => __('Live (production)', 'e-motion-m4ec'),
);


$fields = array(
    'mode' => array(
        'title' => __('Mode', 'e-motion-m4ec'),
        'type' => 'select',
        'options' => $mode,
        'description' => __('Choose between Sandbox Mode (only for test) and Live Mode (production)', 'e-motion-m4ec'),
        'desc_tip' => true,
        'default' => 0
    ),
    'api_key' => array(
        'title' => __('Api Key', 'e-motion-m4ec'),
        'description' => __('Copy and paste your e-motion key into this field.', 'e-motion-m4ec'),
        'default' => '',
        'type' => 'text',
        'desc_tip' => __('This is the <code>Auth Key</code> you have in e-motion and allows WooCommerce to communicate with your e-motion profile.', 'e-motion-m4ec'),
        'value' => $this->get_option('api_key', false)
    ),
    'auth_key' => array(
        'title' => __('Authentication Key', 'e-motion-m4ec'),
        'description' => __('Copy and paste this key into e-motion during setup.', 'e-motion-m4ec'),
        'default' => '',
        'type' => 'text',
        'desc_tip' => __('This is the <code>Auth Key</code> you set in e-motion and allows e-motion to communicate with your store.', 'e-motion-m4ec'),
        'custom_attributes' => array(
            'readonly' => 'readonly'
        ),
        'value' => WC_e_motion_m4ec_Integration::$auth_key
    ),
    'logging_enabled' => array(
        'title' => __('Logging', 'e-motion-m4ec'),
        'label' => __('Enable Logging', 'e-motion-m4ec'),
        'type' => 'checkbox',
        'description' => __('Log all API interations.', 'e-motion-m4ec'),
        'desc_tip' => true,
        'default' => 'yes'
    ),
);


return $fields;


