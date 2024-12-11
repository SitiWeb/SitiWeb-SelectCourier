<?php


/**
 * WC_Shipping_SelectCourier class.
 *
 * @class         WC_Shipping_SelectCourier
 * @version        1.0.0
 * @package        Shipping-for-WooCommerce/Classes
 * @category    Class
 * @author         Roberto van SitiWeb
 */
class WC_Shipping_SelectCourier extends WC_Shipping_Method {
    private static $calculate_shipping_executed = false;

    /**
     * Constructor. The instance ID is passed to this.
     */
    public function __construct( $instance_id = 0 ) {
        $this->id                    = 'select_courier';
        $this->instance_id           = absint( $instance_id );
        $this->method_title          = __( 'select_courier Method' );
        $this->method_description    = __( 'select_courier method.' );
        $this->supports              = array(
            'shipping-zones',
            'instance-settings',
        );
        $this->instance_form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable' ),
                'type'             => 'checkbox',
                'label'         => __( 'Enable this shipping method' ),
                'default'         => 'yes',
            ),
            'title' => array(
                'title'         => __( 'Method Title' ),
                'type'             => 'text',
                'description'     => __( 'This controls the title which the user sees during checkout.' ),
                'default'        => __( 'SitiWeb SelectCourier Method' ),
                'desc_tip'        => true
            )
        );
        $this->enabled              = $this->get_option( 'enabled' );
        $this->title                = $this->get_option( 'title' );

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public static function woocommerce_shipping_methods( $methods ) {
        $methods[] = 'Custom_Shipping'; return $methods;
    }
    

    /**
    * calculate_shipping function.
    * @param array $package (default: array())
    */
    public function calculate_shipping( $package = array() ) {
        // Check if calculate_shipping has already been executed
        if (self::$calculate_shipping_executed) {
            // If yes, return early to avoid executing the function again
           // return;
        }
  
        $options = $this->get_shipping_options();
    
        // Check if shipping options are available
        if (!empty($options)) {
            foreach ($options as $option) {
                if (!is_array($option)){
                    continue;
                }
                // Add rate for each shipping option
                
                $this->add_rate( array(
                    'id'    => $this->id . '_' . $option['id'], // Unique ID for the rate
                    'label' => $option['name'], // Displayed label for the rate
                    'cost'  => $option['price'], // Shipping cost for the option
                ) );
            }
        }
        // Mark calculate_shipping as executed to prevent future executions in the same request/operation
        self::$calculate_shipping_executed = true;

   
    }

    private function get_shipping_options() {
        // Start time
        $start_time = microtime(true);

        // Construct the API request data
        $request_data = $this->construct_api_request_data();
        $post_request_data = $request_data;
        $reference = $post_request_data['shipment']['reference'];
        unset($post_request_data['shipment']['reference']);

        // Generate a hash of the request data
        $request_hash = md5(serialize($post_request_data));

        // Check if the cached result exists
        $cached_result = get_transient('select_courier_shipping_options_' . $request_hash);
        error_log( 'Cached: '.print_r($cached_result,1) );

        if ($cached_result !== false) {
            // End time
            $end_time = microtime(true);

            // Calculate the time taken in milliseconds
            $time_taken_ms = ($end_time - $start_time) * 1000;

            // Log the time taken in the error log
            error_log('Fetching shipping options from cache took ' . $time_taken_ms . ' ms');

            return $cached_result; // Return the cached result
        } else {
            // Make the API request
            $response = $this->make_api_request($request_data);

            // Process the API response and extract shipping options
            $shipping_options = $this->process_api_response($response);
            update_option('selectcourier_reference', $reference + 1);

            // Cache the shipping options with the hashed key
            set_transient('select_courier_shipping_options_' . $request_hash, $shipping_options, HOUR_IN_SECONDS); // Cache for 1 hour

            // End time
            $end_time = microtime(true);

            // Calculate the time taken in milliseconds
            $time_taken_ms = ($end_time - $start_time) * 1000;

            // Log the time taken in the error log
            error_log('Fetching shipping options from API took ' . $time_taken_ms . ' ms');

            return $shipping_options;
        }
    }


    private function construct_api_request_data() {
        // Construct and return the API request data based on cart contents and shipping address
        // Include all necessary details required for the API request
        // Construct the API request
        $shipping_address = WC()->customer->get_shipping();
        $auth_method = get_option('selectcourier_auth_method','api_key_secret');
        $items = array();

        $default_country = get_option('woocommerce_default_country');
        if ($default_country){
            $country_parts = explode(':', $default_country);
        }
        

        if (isset($country_parts) && isset($country_parts[0])){
            $store_country = $country_parts[0];
        }
        
        if (isset($store_country) && $store_country){
            $country = get_option('selectcourier_origin_country', $store_country);
        }
        else{
            $country = get_option('selectcourier_origin_country', false);
        }
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            // Fetch product data
            $product = $cart_item['data'];

            // Get product dimensions if available
            $weight = $product->get_weight() ?: 0.1;
            $height = $product->get_height() ?: 0;
            $width = $product->get_width() ?: 0;
            $length = $product->get_length() ?: 0;
            
            $product = $cart_item['data'];

            // Fetching required data for each product
            $quantity = $cart_item['quantity'];
            $product_code = $product->get_sku(); // Assuming SKU is used as product code
            $o_country = 'NL';// $country; // Assuming NL (Netherlands) as the country of origin
            $weight = $product->get_weight() ?: 0.1; // Weight of the product
            $description = $product->get_name(); // Product name as description
            $value = $product->get_price(); // Product price
            $value_currency = get_woocommerce_currency(); // Get the currency set in WooCommerce
            $hs_code = ''; // Harmonized System Code

            // Creating an array for each product
            $product_data = array(
                'quantity' => $quantity,
                'product_code' => $product_code,
                'o_country' => $o_country,
                'weight' => $weight,
                'description' => $description,
                'value' => $value,
                'value_currency' => $value_currency,
                'hs_code' => $hs_code
            );

            // Adding the product data to the products array
            $products[] = $product_data;

         
        }
        // Add item to the items array
        $item = array(
            'weight'    => $weight, 
            'height'    => $height,
            'width'     => $width,
            'length'    => $length,
            'contents'  => 'equipment',
            //'products' => $products
            
        );
        $items[] = $item;
    
        $environment = get_option('selectcourier_environment','development');
        $username = get_option('selectcourier_username', false);
        $password = get_option('selectcourier_password', false);

        $api_key = get_option('selectcourier_api_key', false);
        $api_secret = get_option('selectcourier_api_secret', false);

        $name = get_option('selectcourier_origin_name',false);
        $phone = get_option('selectcourier_origin_phone', false);
        $email = get_option('selectcourier_origin_email',  get_option('admin_email', false));
        $street = get_option('selectcourier_origin_street',  get_option('woocommerce_store_address', false));
        $postcode = get_option('selectcourier_origin_postal', get_option('woocommerce_store_postcode', false));
        $city = get_option('selectcourier_origin_city',  get_option('woocommerce_store_city', false));

        $reference = get_option('selectcourier_reference',0);
    
        
        (WC()->session->set( 'select_courier_shipping_data', $reference ));
       
        
        

        if ((!$username || !$password) && (!$api_key ||! $api_secret)){
            error_log('SelectCourier: Missing password or username');
            error_log('SelectCourier: Missing api key or secret');
            return false;
        }
        
        if (!$name){
            error_log('SelectCourier: Missing contact name');
            return false;
        }

        if (!$phone){
            error_log('SelectCourier: Missing phone ');
            return false;
        }

        if (!$email){
            error_log('SelectCourier: Missing email ');
            return false;
        }

        if (!$street){
            error_log('SelectCourier: Missing street ');
            return false;
        }

        if (!$postcode){
            error_log('SelectCourier: Missing postcode ');
            return false;
        }

        if (!$city){
            error_log('SelectCourier: Missing city ');
            return false;
        }

        if (!$country){
            error_log('SelectCourier: Missing country ');
            return false;
        }

        // Initialize the request array with common elements
        $request = array(
            "environment" => $environment,
            "action"      => "quote",
            "shipment"    => array(
                "o_name"         => $name,
                "o_phone"        => $phone,
                "o_email"        => $email,
                "o_street_1"     => $street,
                "o_postal"       => $postcode,
                "o_city"         => $city,
                "o_country"      => $country,
                "d_name"         => "Recipient",
                "d_phone"        => $phone,
                "d_street_1"     => $shipping_address['address_1'],
                "d_postal"       => $shipping_address['postcode'],
                "d_city"         => $shipping_address['city'],
                "d_country"      => $shipping_address['country'],
                "reference"      => $reference,
                "type"           => "Parcel",
                "contents"       => "Radio Equipment",
                "value"          => "parcel",
                "return_service" => 0,
                "items"          => $items // Include items in the shipment
            )
        );

        // Adjust the request array based on the selected authentication method
        if ($auth_method == 'username_password') {
            // Add username and password to the request for username/password authentication
            $request['username'] = $username;
            $request['password'] = $password;
        }
        error_log(print_r($request,1));
        
        return $request;
    }

    private function make_api_request($request) {
        
        // Start time
        $start_time = microtime(true);
        $auth_method = get_option('selectcourier_auth_method','api_key_secret');
        // Make the API request using cURL
        $url = "https://portal.selectcourier.com/api/json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($auth_method == 'api_key_secret') {
            $api_key = get_option('selectcourier_api_key', false);
            $api_secret = get_option('selectcourier_api_secret', false);
            // Assume you have $api_key and $api_secret variables set from your settings]

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Basic " . base64_encode($api_key . ":" .  $api_secret)
           ));

          
        }
        $response = curl_exec($ch);
     
        curl_close($ch);
        // End time
        $end_time = microtime(true);

        // Calculate the time taken in milliseconds
        $time_taken_ms = ($end_time - $start_time) * 1000;

        // Log the time taken in the error log
        error_log('API request took ' . $time_taken_ms . ' ms');
        $response = json_decode($response, true);
        error_log(print_r($response,1));
        return $response;
        
    }

    private function process_api_response($response) {
        $shipping_options = [];
        if (isset($response["result"]["services"])) {
            foreach ($response["result"]["services"] as $method) {
                if (!empty($method["courier_name"])) {
                
                    $method_name = $method["courier_name"]. ' ' . $method['service_name'];
                    
                    $shipping_options[] = array(
                        'id'    => $method["service_keycode"],
                        'name'  => $method_name,
                        'price' => $method["total_price"],
                        
                    );
                    if (isset($method[ 'courier_logo' ])){
                        $methods = new SitiWeb_SelectCourier_methods();
                        $methods->set_method_in_db($method);
                    }
                   
                }
            }
        }

        return $shipping_options;
    }

}
?>
