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
        
        wp_send_json_success('connection_ok');
    }
    
    // 2. verifying the authcode generated from Addon.
    function verify_authcode($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        $data   = $request->get_params();
        extract($data);
        $saved = get_option('wbps_authcode');
        
        wbps_logger_array($saved);
        wbps_logger_array($data);
        
        if( $authcode !== $saved ) {
            wp_send_json_error(__('AuthCode is not valid','wbps'));
        }
        
        update_option('wbps_connection_status', 'verified');
        
        $wc_keys = get_option('wbps_woocommerce_keys');
        
        
        $response = ['wc_keys'=>$wc_keys, 'is_pro'=>wbps_pro_is_installed()];
        wp_send_json_success($response);
    }
    
    function disconnect_store($request){
        
        if( ! $request->sanitize_params() ) {
            wp_send_json_error( ['message'=>$request->get_error_message()] );
        }
        
        wpbs_disconnect();
        
        wp_send_json_success(__("Store is unlinked","wbps"));
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
        
        // wbps_logger_array($data);
        
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
    
    function attributes_fetch($request){
        
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
        
        $attributes_data = array();

        foreach (wc_get_attribute_taxonomies() as $values) {
            $attribute_data = array(
                'id' => $values->attribute_id,
                'name' => $values->attribute_label,
                'terms' => array()
            );
        
            // Get the array of term objects for each product attribute
            $term_objects = get_terms(array('taxonomy' => 'pa_' . $values->attribute_name, 'hide_empty' => false));
        
            // Extract term names from term objects
            foreach ($term_objects as $term) {
                $attribute_data['terms'][] = $term->name;
            }
        
            $attributes_data[] = $attribute_data;
        }

       
        wp_send_json_success(['attributes'=>json_encode($attributes_data)]);
    }
    
    
    
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
        
        if( ! wbps_pro_is_installed() ) {
            $url = 'https://najeebmedia.com/googlesync';
            wp_send_json_error(sprintf(__('Pro version is not installed or active <a target="_blank" href="%s">Get Pro</a>'), $url));
        }
        
        $data   = $request->get_params();
        update_option('wbps_webhook_url', $data['webapp_url']);
        
        wp_send_json_success('AutoFetch is enabled');
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