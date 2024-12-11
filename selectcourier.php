<?php
/**
 * Plugin Name: Maatwerk Select courier
 * Description: Maatwerk Select courier voor profmbroadcast.nl
 * Version: 1.6.1
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
        require_once( plugin_dir_path( __FILE__ ) . 'class-select-methods.php' );


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
        <p><?php _e( 'Maatwerk Select courier requires WooCommerce to be installed and active.', 'my-custom-plugin-text-domain' ); ?></p>
    </div>
    <?php
}

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
    woocommerce_admin_fields(select_shipping_options_fields());
}

// Define fields for the custom tab
function select_shipping_options_fields() {

    $fields = array(
        'selectcourier_options_title' => array(
            'name' => __('Selectcourier opties', 'woocommerce'),
            'type' => 'title',
            'desc' => '',
            'id' => 'selectcourier_options_title'
        ),
        'selectcourier_auth_method' => array(
            'name' => __('Authentication Method', 'woocommerce'),
            'type' => 'select',
            'desc' => __('Select the authentication method', 'woocommerce'),
            'id' => 'selectcourier_auth_method',
            'options' => array(
                
                'api_key_secret' => __('API Key & Secret (Recommended)', 'woocommerce'),
                'username_password' => __('Username & Password (Not Recommended)', 'woocommerce'),
            )
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
            'id' => 'selectcourier_username',
            'class' => 'selectcourier-auth-username-password' // Add a class for conditional logic
        ),
        'selectcourier_password' => array(
            'name' => __('Password', 'woocommerce'),
            'type' => 'password',
            'desc' => __('Enter the password', 'woocommerce'),
            'id' => 'selectcourier_password',
            'class' => 'selectcourier-auth-username-password' // Add a class for conditional logic
        ),
        'selectcourier_api_key' => array(
            'name' => __('API Key', 'woocommerce'),
            'type' => 'text',
            'desc' => __('Enter the API key', 'woocommerce'),
            'id' => 'selectcourier_api_key',
            'class' => 'selectcourier-auth-api-key-secret' // Add a class for conditional logic
        ),
        'selectcourier_api_secret' => array(
            'name' => __('API Secret', 'woocommerce'),
            'type' => 'password',
            'desc' => __('Enter the API secret', 'woocommerce'),
            'id' => 'selectcourier_api_secret',
            'class' => 'selectcourier-auth-api-key-secret' // Add a class for conditional logic
        ),
       
        'shipping_options_section_end' => array(
            'type' => 'sectionend',
            'id' => 'shipping_options_section_end'
        ),
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

add_action('admin_head','sw_admin_head_selectcourier');
function sw_admin_head_selectcourier(){
    ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    function toggleAuthFields() {
        // Get the selected authentication method
        var authMethod = $('#selectcourier_auth_method').val();

        // Hide all fields initially
        $('.selectcourier-auth-username-password').closest('tr').hide();
        $('.selectcourier-auth-api-key-secret').closest('tr').hide();

        // Show fields based on the selected authentication method
        if (authMethod === 'username_password') {
            $('.selectcourier-auth-username-password').closest('tr').show();
        } else if (authMethod === 'api_key_secret') {
            $('.selectcourier-auth-api-key-secret').closest('tr').show();
        }
    }

    // Run on page load
    toggleAuthFields();

    // Run on authentication method change
    $('#selectcourier_auth_method').change(function() {
        toggleAuthFields();
    });
});
</script>
    <?php
}



add_action('woocommerce_update_options', 'save_select_shipping_options', 100);
function save_select_shipping_options() {
    if(isset($_POST['selectcourier_origin_country'])){
        woocommerce_update_options(select_shipping_options_fields());
    }
}

add_filter( 'woocommerce_cart_shipping_method_full_label', 'filter_woocommerce_cart_shipping_method_full_label', 10, 2 ); 

function filter_woocommerce_cart_shipping_method_full_label( $label, $method ) {  
    $methods = new SitiWeb_SelectCourier_methods();
    $image = $methods->get_method_image($method);
    if ($image){
        $label = '<img style="width:40px;margin-right:10px;" src="data:image/jpeg;base64, '.$image.'" /> '.$label;
    }
  

   return $label; 
}

function save_shipping_meta_to_order( $order, $data ) {
    $shipping_data = WC()->session->get( 'select_courier_shipping_data');
    if ($shipping_data)  {
        $order->update_meta_data( '_select_courier_shipping_data', $shipping_data );
        WC()->session->__unset( 'select_courier_shipping_data' ); // Clear the session data
    }
}
add_action( 'woocommerce_checkout_create_order', 'save_shipping_meta_to_order', 10, 2 );

add_action( 'woocommerce_order_status_changed', 'action_after_order_processed', 10, 4 );

function action_after_order_processed( $order_id, $from, $to, $order ) {
    if (WC()->session) {
        $shipping_data = WC()->session->get('select_courier_shipping_data');
        
        if ($shipping_data) {
            update_post_meta($order_id,'_select_courier_shipping_data',$shipping_data);
        }
    } else {
        error_log('WooCommerce session is not available.');
    }
}



function display_shipping_meta_in_admin_order( $order ) {
    if (!is_admin()){
        return;
    }
    $shipping_data =(get_post_meta($order->get_id(),'_select_courier_shipping_data',true));
    if ( ! empty( $shipping_data ) ) {
        echo '<p><strong>Select Courier reference:</strong> ' . esc_html( $shipping_data ) . '</p>';
    }
}
//add_action( 'woocommerce_admin_order_data_after_shipping_address', 'display_shipping_meta_in_admin_order', 10, 1 );



//add_filter('woocommerce_rest_prepare_shop_order_object', 'custom_modify_order_response_for_specific_ip', 10, 3);

function custom_modify_order_response_for_specific_ip($response, $order, $request) {
    // Check if the X-Real-IP header matches the specified IP address
    $target_ip = '2001:9a8:1ad:0:87:233:77:121';
    if (isset($_SERVER['X_REAL_IP']) && $_SERVER['X_REAL_IP'] === $target_ip) {
        $custom_order_number = get_post_meta($order->get_id(), '_select_courier_shipping_data', true);   
        if (!empty($custom_order_number)) {
            // Modify the response data
            $response->data['order_id'] = $custom_order_number;
        }
    }
    return $response;
}
