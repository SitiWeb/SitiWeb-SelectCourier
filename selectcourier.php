<?php
/**
 * Plugin Name: Maatwerk Select courier
 * Description: Maatwerk Select courier voor profmbroadcast.nl
 * Version: 1.1
 * Author: Roberto van SitiWeb
 * Author URI: https://sitiweb.nl/
 */
if( ! class_exists( 'SitiWeb_Updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}

$updater = new SitiWeb_Updater( __FILE__ );
$updater->set_username( 'SitiWeb' );
$updater->set_repository( 'SitiWeb-SelectCourier' );
$updater->initialize();

add_action('plugins_loaded', 'load_plugins', 0);
function load_plugins() {
    // Check if WooCommerce is active and the WC_Shipping_Method class exists
    if ( class_exists( 'WC_Shipping_Method' ) ) {
        require_once( plugin_dir_path( __FILE__ ) . 'class-custom-shipping.php' );
    } else {
        // Optionally, handle the case where WooCommerce is not active
        // For example, you could deactivate your plugin or display a notice
        add_action( 'admin_notices', 'my_custom_plugin_admin_notice' );
    }
}
// Function to display an admin notice if WooCommerce is not active
function my_custom_plugin_admin_notice() {
    ?>
    <div class="notice notice-warning">
        <p><?php _e( 'My Custom Plugin requires WooCommerce to be installed and active.', 'my-custom-plugin-text-domain' ); ?></p>
    </div>
    <?php
}

add_filter('woocommerce_shipping_methods', array('WC_Shipping_SelectCourier','woocommerce_shipping_methods' ));

add_filter( 'woocommerce_shipping_methods', 'register_select_courier' );

function register_select_courier( $methods ) {

    // $method contains available shipping methods
    $methods[ 'select_courier' ] = 'WC_Shipping_SelectCourier';

    return $methods;
}

// Add a custom tab to WooCommerce settings
add_filter('woocommerce_settings_tabs_array', 'add_shipping_options_tab', 50);
function add_shipping_options_tab($tabs) {
    $tabs['shipping_options'] = __('Shipping Options', 'woocommerce');
    return $tabs;
}

// Add fields to the custom tab
add_action('woocommerce_settings_tabs_shipping_options', 'shipping_options_tab');
function shipping_options_tab() {
    woocommerce_admin_fields(array_merge(shipping_options_fields(), origin_address_fields()));
}

// Define fields for the custom tab
function shipping_options_fields() {
    $fields = array(
        'shipping_options_title' => array(
            'name' => __('Selectcourier opties', 'woocommerce'),
            'type' => 'title',
            'desc' => '',
            'id' => 'shipping_options_title'
        ),
        'selectcourier_environment' => array(
            'name' => __('Selectcourier Environment', 'woocommerce'),
            'type' => 'select',
            'desc' => __('Select the environment', 'woocommerce'),
            'id' => 'selectcourier_environment',
            'options' => array(
                'production' => __('Production', 'woocommerce'),
                'development' => __('Development', 'woocommerce')
            )
        ),
        'selectcourier_username' => array(
            'name' => __('Username', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the username', 'woocommerce'),
            'id' => 'selectcourier_username'
        ),
        'selectcourier_password' => array(
            'name' => __('Password', 'woocommerce'),
            'type' => 'password',
            'desc' => __('Enter the password', 'woocommerce'),
            'id' => 'selectcourier_password'
        ),
        'selectcourier_username' => array(
            'name' => __('Username', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the username', 'woocommerce'),
            'id' => 'selectcourier_username'
        ),
        'shipping_options_section_end' => array(
            'type' => 'sectionend',
            'id' => 'shipping_options_section_end'
        )
    );
    return $fields;
}



// Define fields for the origin address section
function origin_address_fields() {
    $fields = array(
        'origin_address_title' => array(
            'name' => __('Origin Address', 'woocommerce'),
            'type' => 'title',
            'desc' => '',
            'id' => 'origin_address_title'
        ),
        'selectcourier_origin_name' => array(
            'name' => __('Name', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the name', 'woocommerce'),
            'id' => 'selectcourier_origin_name'
        ),
        'selectcourier_origin_phone' => array(
            'name' => __('Phone', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the phone number', 'woocommerce'),
            'id' => 'selectcourier_origin_phone'
        ),
        'selectcourier_origin_email' => array(
            'name' => __('Email', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the email address', 'woocommerce'),
            'id' => 'selectcourier_origin_email'
        ),
        'selectcourier_origin_street' => array(
            'name' => __('Street', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the street address', 'woocommerce'),
            'id' => 'selectcourier_origin_street'
        ),
        'selectcourier_origin_postal' => array(
            'name' => __('Postal Code', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the postal code', 'woocommerce'),
            'id' => 'selectcourier_origin_postal'
        ),
        'selectcourier_origin_city' => array(
            'name' => __('City', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the city', 'woocommerce'),
            'id' => 'selectcourier_origin_city'
        ),
        'selectcourier_origin_country' => array(
            'name' => __('Country', 'woocommerce'),
            'type' => 'select',
            'options' => WC()->countries->get_countries(),
            'desc' => __('Select the country', 'woocommerce'),
            'id' => 'selectcourier_origin_country'
        ),
        
        'origin_address_section_end' => array(
            'type' => 'sectionend',
            'id' => 'origin_address_section_end'
        )
    );
    return $fields;
}

add_action('woocommerce_update_options', 'save_shipping_options');
function save_shipping_options() {
    woocommerce_update_options(shipping_options_fields());
    woocommerce_update_options(origin_address_fields()); // Save origin address fields
}