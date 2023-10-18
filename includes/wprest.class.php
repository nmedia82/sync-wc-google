<?php
/**
 * Rest API Handling
 * 
 * */

if( ! defined('ABSPATH') ) die('Not Allowed.');


class WBPS_WP_REST {
	
	private static $ins = null;
	
	public static function __instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
	
	public function __construct() {
	    
	    add_filter('woocommerce_rest_check_permissions', '__return_true');
		
		add_action( 'rest_api_init', function()
            {
                header( "Access-Control-Allow-Origin: *" );
            }
        );
		
		add_action( 'rest_api_init', [$this, 'init_api'] ); // endpoint url

	}
	
	
	function init_api() {
	    
	    foreach(wbps_get_rest_endpoints() as $endpoint) {
	        
            register_rest_route('wbps/v1', $endpoint['slug'], array(
                'methods' => $endpoint['method'],
                'callback' => [$this, $endpoint['callback']],
                'permission_callback' => [$this, 'permission_check'],
    	    
            ));
	    }
        
    }
    
    function check_pro($request){
        
        if( wbps_pro_is_installed() ){
            $wc_keys = get_option('wbps_woocommerce_keys');
            wp_send_json_success($wc_keys);
        }else{
            wp_send_json_error('Not installed');
        }
    }
    
    // validate request
    function permission_check($request){
        
        return true;
    }
    
    // 1. check connection
    function connection_check($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        $wc_keys = get_option('wbps_woocommerce_keys');
        if( $wc_keys ){
            // also setting connection_status in sheet_props
            $sheet_props = get_option('wbps_sheet_props');
            $sheet_props['connection_status'] = 'verified';
            update_option('wbps_sheet_props', $sheet_props);
            wp_send_json_success(__('Congratulations! setup is successfully completed.', 'wbps'));
        }else{
            wp_send_json_error(__('Oops, it seems that an error occurred while verifying. Please close this window and try reconnecting. If the problem persists, please contact our support team for assistance.', 'wbps'));
        }
    }
    
    // 2. verifying the authcode generated from Addon.
    function verify_authcode($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        $data   = $request->get_params();
        extract($data);
        $saved = get_option('wbps_authcode');
        
        if( $authcode !== $saved ) {
            wp_send_json_error(__('AuthCode is not valid','wbps'));
        }
        
        $response = __("Authcode saved successfully",'wbps');
        wp_send_json_success($response);
    }
    
    // product sync
    function product_sync($request) {
        // Check if the POST data size exceeds the limit
        $postMaxSizeBytes = wbps_return_bytes(ini_get('post_max_size'));
        $postDataSize = strlen(file_get_contents('php://input'));
        // wbps_logger_array($postMaxSizeBytes);
    
        if ($postDataSize > $postMaxSizeBytes) {
            // Handle the situation where the POST data size exceeds the limit
            wp_send_json_error(['message' => 'The size of the POST data exceeds the limit.']);
        }
    
        // Continue with the rest of the function
        if (!$request->sanitize_params()) {
            wp_send_json_error(['message' => $request->get_error_message()]);
        }

        
    
        $data   = $request->get_params();
        extract($data);
        
        wbps_logger_array($data);
        
        // since version 7.5.2 products are being sent as json
        $decodedChunk = json_decode($chunk);
        if ($decodedChunk !== null && is_string($chunk) && json_last_error() === JSON_ERROR_NONE) {
            // 'chunk' is a valid JSON string
            $chunk = json_decode($chunk, true);
        }
        
        
        // Parse $general_settings if it's a string
        if (is_string($general_settings)) {
            $general_settings = json_decode($general_settings, true);
        }

        // will remove extra indexed level
        $chunk = array_replace(...$chunk);
        // return;
        $products_ins = init_wbps_products();
        $response = $products_ins::sync($chunk, $general_settings);
        if( is_wp_error($response) ) {
            wp_send_json_error($response->get_error_message());
        }
        
        // sleep(intval($chunk));
        
        wp_send_json_success($response);
    }
    
    // category sync
    function category_sync($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        $data   = $request->get_params();
        extract($data);
        
        // wbps_logger_array($data);
        
        // Parse $general_settings if it's a string
        if (is_string($general_settings)) {
            $general_settings = json_decode($general_settings, true);
        }
        // will remove extra indexed level
        $chunk = array_replace(...$chunk);
        $categories_ins = init_wbps_categories();
        $response = $categories_ins::sync($chunk, $general_settings);
        if( is_wp_error($response) ) {
            wp_send_json_error($response->get_error_message());
        }
        
        // sleep(intval($chunk));
        
        wp_send_json_success($response);
    }
    
    // prepare fetch, return fetchable products/category ids
    function prepare_fetch($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        if( ! wbps_pro_is_installed() ){
            $url = 'https://najeebmedia.com/wordpress-plugin/woocommerce-google-sync/';
            $msg = 'Pro Version is not installed or deactivated. Learn more about <a href="'.esc_url($url).'" target="_blank">Pro Version</a>';
            wp_send_json_error( ['message'=>$msg] );
        }
        
        $data = $request->get_params();
        extract($data);
        
        $refresh = isset($data['refresh_fetch']) && $data['refresh_fetch'] == 'yes' ? true : false;
        
        if ($refresh) {
            global $wpdb;
            $val = 'wbps_row_id';
            
            $table = "{$wpdb->prefix}postmeta";
            $wpdb->delete($table, array('meta_key' => $val));
        }

        
        
        $response = [];
        if( $sheet_name === 'products' ) {
            $response = wbps_get_syncback_product_ids( $product_status );
        }
        
        // wbps_logger_array($data);
        
        wp_send_json_success($response);
    }
    
    // now fetch products from store to sheet
    function product_fetch($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        $data   = $request->get_params();
        extract($data);
        
        
        // since version 7.5.2 products are being sent as json
        $decodedChunk = json_decode($chunk);
        if ($decodedChunk !== null && is_string($chunk) && json_last_error() === JSON_ERROR_NONE) {
            // 'chunk' is a valid JSON string
            $chunk = json_decode($chunk, true);
        }
        
        // wbps_logger_array($response);
         
        /**
         * chunk, sheet_header, general_settings, last_row
         * */
        
        $products_ins = init_wbps_products();
        $response = $products_ins::fetch($chunk, $sheet_header, $general_settings, $last_row);
       
        wp_send_json_success(['products'=>json_encode($response)]);
    }
    
    // now fetch categories from store to sheet
    function category_fetch($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        if( ! wbps_pro_is_installed() ){
            $url = 'https://najeebmedia.com/wordpress-plugin/woocommerce-google-sync/';
            $msg = 'Pro Version is not installed or deactivated. Learn more about <a href="'.esc_url($url).'" target="_blank">Pro Version</a>';
            wp_send_json_error( ['message'=>$msg] );
        }
        
        $data   = $request->get_params();
        extract($data);
        
        $refresh = isset($data['refresh_fetch']) && $data['refresh_fetch'] == 'yes' ? true : false;
        
        if ($refresh) {
            global $wpdb;
            $val = 'wbps_row_id';
            
            $table = "{$wpdb->prefix}termmeta";
            $wpdb->delete( $table, array( 'meta_key' => $val ) );
        }
        
        // wbps_logger_array($data);
        
        /**
         * sheet_header, general_settings, last_row
         * */
        
        $categories_ins = init_wbps_categories();
        $response = $categories_ins::fetch($sheet_header, $general_settings, $last_row);
       
        wp_send_json_success(['categories'=>json_encode($response)]);
    }
    
    
    
    function disconnect_store($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        global $wpdb;
        $val = 'wbps_row_id';
        
        $table = "{$wpdb->prefix}postmeta";
        $wpdb->delete( $table, array( 'meta_key' => $val ) );
        
        $table = "{$wpdb->prefix}termmeta";
        $wpdb->delete( $table, array( 'meta_key' => $val ) );
        
        // delete webhook url:
        delete_option('wbps_webhook_url');
        
        $wc_keys = get_option('wbps_woocommerce_keys');
        $key_id = isset($wc_keys['key_id']) ? $wc_keys['key_id'] : null;
        
        // deleting WC REST keys
        if($key_id) {
		    $delete = $wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ), array( '%d' ) );
        }
        
        // wc keys
        delete_option('wbps_woocommerce_keys');
        
        // sheet props
        delete_option('wbps_sheet_props');
        
        wp_send_json_success(__("Store is unlinked","wbps"));
    
    }
    
    // Webhook handling
    // function webhook_product($request) {
        
    //     if( ! $request->sanitize_params() ) {
    //         wp_send_json_error( ['message'=>$request->get_error_message()] );
    //     }
        
    //     $data   = $request->get_params();
        
    //     // Set the token value Googlesync User
    //     // $token = 'ya29.a0AVvZVsq0GaA1KZB1TboLjkhS0WHNAEiwBqeDFmeEwtE54cYEzacyk8wiYnAxAAUblxvaEZA2PKRxiO5Am_RgycrBJ3jHOGl4F-YYvWNCNj_y8h9vd_HmeZZDZzW6gBjCICN5mIqY5GDbUi4YFgBroy8wlcfy-5k-s3Rz254njgaCgYKATUSARMSFQGbdwaIXwlbp86RJ_Sdn4Sg7umYMg0177';
    //     // $sheet_id = '1SeyeQkVEn612abKQmC_HQwbol9Jl0Xa1IrDb_B9Z5Ek';
        
    //     $token = 'ya29.a0AVvZVspl8jFVU2NxooHesaWuQ_n4qjjOprzjK3uD5BzfhwNLTELqdACHZHCeZ6MirHZDz4i2EAsqMGn8FB79E8Ogb-TOh2UKWF93blQKkklOr6URvl5sLiWD-EKhogx-R4_4Px874RG1aVvjqhaUa0hHW5ZB2yGuC6w9tp1BeQaCgYKAYYSARMSFQGbdwaIcOr2BLvK_9hoI9VFF_8Rxg0177';
    //     $sheet_id = '1yjk3QBYqfvPxT7C7RyuB6dWMZPeic_CYQUD-umuYTDk';
    //     $sheet_name = 'Test';
    //     $range = 'A2:B2';
    //     $range = $sheet_name . '!' . $range;
    //     $values = [["32","Fancy x3"]];
        
    //     // Set the endpoint URL where you want to send the request
    //     $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $sheet_id . '/values/' . $range . '?valueInputOption=USER_ENTERED';
    //     wbps_logger_array($url);
        
    //     // Set the headers for the request, including the Authorization header with the bearer token
    //     $headers = array(
    //         'Authorization' => 'Bearer ' . $token,
    //         'Content-Type' => 'application/json'
    //     );
        
    //     // // Set the data you want to send in the request body
    //     $body = array(
    //         'values' => $values
    //     );
        
    //     // Set the arguments for the wp_remote_post function
    //     $args = array(
    //         'method' => 'PUT',
    //         'headers' => $headers,
    //         'body' => json_encode($body),
    //         'timeout' => '30'
    //     );
        
    //     // wbps_logger_array($args);
        
    //     // Send the request using wp_remote_post function
    //     $response = wp_remote_post( $url, $args );
        
    //     // wbps_logger_array($response);
        
    //     // Check if there was an error with the request
    //     if( is_wp_error( $response ) ) {
    //         $error_message = $response->get_error_message();
    //         echo "Error: $error_message";
    //     } else {
    //         // Get the response body
    //         $response_body = wp_remote_retrieve_body( $response );
    //         wbps_logger_array($response_body);
    //         // Do something with the response body
    //     }

    // }
    
    // when product is created inside via webhook, now link it inside store
    function link_new_product($request) {
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        $data   = $request->get_params();
        extract($data);
        
        $response = update_post_meta($product_id, 'wbps_row_id', intval($row_id));
        // wbps_logger_array($response);
        
        wp_send_json($response);
    }
    
    // when connecting, all webhook will be sent here after WC Auth
    // to save woocommerce keys
    function webhook_callback($request){
        
        $data   = $request->get_params();
        
        // wbps_logger_array($data);
        
        delete_option('wbps_woocommerce_keys');
        // saving woocommerce keys
        update_option('wbps_woocommerce_keys', $data);
        return '';
    }
    
    // Enabling the webhook
    function enable_webhook($request){
        
        $data   = $request->get_params();
        
        update_option('wbps_webhook_url', $data['webapp_url']);
        return '';
    }
    
    // Disabling the webhook
    function disable_webhook($request){
        
        $data   = $request->get_params();
        
        delete_option('wbps_webhook_url');
        return '';
    }
    
    function save_sheet_props($request){
        
        $data   = $request->get_params();
        
        // wbps_logger_array($data);
        update_option('wbps_sheet_props', $data);
        
        wp_send_json_success(__("Properties updated successfully.", 'wbps'));
    }
    
    function relink_products($request){
        
        $data   = $request->get_params();
        
        $prodcts_links = json_decode($data['product_links'],true);
        // wbps_logger_array($prodcts_links);
        
        global $wpdb;
        $postmeta_table = $wpdb->prefix.'postmeta';
        $metakey = 'wbps_row_id';
        
        $wpsql = "INSERT INTO {$postmeta_table} (post_id,meta_key,meta_value) VALUES ";
        $delqry = "DELETE FROM {$postmeta_table} WHERE meta_key='{$metakey}'";
        
        foreach($prodcts_links as $link){
            
            $row_id = $link['row_id'];
            $prod_id = $link['product_id'];
            
            $metaval    = $row_id;
            $postid     = $prod_id;    // term id
            
            // Term meta sql
            $wpsql .= "({$postid}, '{$metakey}', '{$metaval}'),";
        
        }
        
        // wbps_logger_array($delqry);
        $wpdb->query($delqry);
        
        //insert query
        $wpsql = rtrim($wpsql, ',');
        
        // wbps_logger_array($wpsql);
        
        $wpdb->query($wpsql);
        
        wp_send_json_success(__("Properties updated successfully.", 'wbps'));
    }
    
    
}

function init_wbps_wp_rest(){
	return WBPS_WP_REST::__instance();
}