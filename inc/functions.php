<?php
/**
 * Helper functions
 * */
 

function wcgs_pa($arr) {
    echo '<pre>'; print_r($arr); echo '</pre>';
}


function wcgs_load_template_file($file_name, $vars=null) {
         
   if( is_array($vars))
    extract( $vars );
    
   $file_path =  WCGS_PATH . '/templates/'.$file_name;
   if( file_exists($file_path))
   	include ($file_path);
   else
   	die('File not found'.$file_path);
}

// check if PPOM PRO is installed
function wcgs_pro_is_installed() {
	
	$return = false;
	    
    if( class_exists('WCGS_PRO_INIT') ) 
        $return = true;
    return $return;
}

// Get sheet ID by title saved in option meta: wcgs_sheets_info
function wcgs_get_sheetid_by_title($title) {
    
    $gs_info = get_option('wcgs_sheets_info');
    
    // If sheet product/categories ids not saved then get it once.
    if( !isset($gs_info['product']) || !isset($gs_info['categories']) ) {
        $gs = new WCGS_APIConnect();
        $gs->setSheetInfo();
    }
    
    // wcgs_pa($gs_info);
    $sheetId = '';
    if($gs_info) {
        
        foreach($gs_info as $id => $sheet_title) {
            
            if( $title === $sheet_title ) {
                $sheetId = $id;
                break;
            }
        }
    }
    
    return $sheetId;
}

function wcgs_get_option($key, $default_val=false) {
	
    $value = get_option($key);
	if( ! $value ) {
		
		$value = $default_val;
	}
		
	$value = sprintf(__("%s", 'wcgs') , trim($value) );
	return $value;
}

// ignore header keys
// function wcgs_system_header_keys() {
//     return ['sync', 'last_sync','last_sync_source'];
// }
// // system header with keys
// function wcgs_system_header_key_values($key){
    
//     $header = ['sync' => 1, 'last_sync' => current_datetime(),'last_sync_source'=>get_bloginfo('name')];
//     return isset($header[$key]) ? $header[$key] : false;
// }

// WCGS Settings Admin
function wcgs_array_settings() {
	
    $wcgs_settings = array(
       
		array(
			'title' => 'Google Connect',
			'type'  => 'title',
			'desc'	=> '',
			'id'    => 'wcgs_google_creds',
		),
		
// 		array(
//             'title'		=> __( 'Google Credentials:', 'wcgs' ),
//             'type'		=> 'text',
//             'desc'		=> __( 'Copy/paste google credentials you downloaded from Google Console', 'wcgs' ),
//             'default'	=> __('', 'wcgs'),
//             'id'		=> 'wcgs_google_credential',
//             'css'   	=> 'min-width:300px;',
// 			'desc_tip'	=> true,
//         ),


        array(
            'title'		=> __( 'Google Sheet ID:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'Paste here the Google Sheet ID to import products/categories from', 'wcgs' ),
            'default'	=> __('', 'wcgs'),
            'id'		=> 'wcgs_googlesheet_id',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
//         array(
//             'title'		=> __( 'Redirect URL:', 'wcgs' ),
//             'type'		=> 'text',
//             'desc'		=> __( 'Copy this redirect URL and paste into Google credentials as per guide.', 'wcgs' ),
//             'default'	=> get_rest_url(null, 'nkb/v1/auth'),
//             'id'		=> 'wcgs_redirect_url',
//             'css'   	=> 'min-width:300px;',
//             'custom_attributes' => array('readonly' => 'readonly'),
// 			'desc_tip'	=> true,
//         ),  
        
        array(
            'title'		=> __( 'GoogleSync AuthCode:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'Paste your AuthCode from Goole Sheet Settings', 'wcgs' ),
            'default'	=> '',
            'id'		=> 'wcgs_authcode',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
        
        array(
            'title'		=> __( 'GoogleSync WebApp URL:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'Paste your Google WebApp URL after Deploy', 'wcgs' ),
            'default'	=> '',
            'id'		=> 'wcgs_appurl',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
        
        array(
			'type' => 'sectionend',
			'id'   => 'wcgs_google_creds',
		),
        
//         array(
// 			'type' => 'sectionend',
// 			'id'   => 'wcgs_woocommerce_creds',
// 		),
		
// 		array(
// 			'title' => __('General Settings', 'wcgs'),
// 			'type'  => 'title',
// 			'desc'	=> __(''),
// 			'id'    => 'wcgs_woocommerce_gs',
// 		),
		
// 		array(
//             'title'             => __( 'Imports Limit', 'wcgs' ),
//             'type'              => 'select',
//             'label'             => __( 'Button', 'wcgs' ),
//             'default'           => '20',
//             'options' => array( '20'=>__('20','wcgs'),
//                                 '50'=> __('50','wcgs'),
                              
//                             ),
//             'id'       => 'wcgs_imports_limit',
//             'desc'       => __( 'Set product import limit at a single sync.', 'wcgs' ),
//             'desc_tip'      => true,
//         ),
		
// 		array(
//             'title'             => __( 'Images Import', 'wcgs' ),
//             'type'              => 'select',
//             'label'             => __( 'Button', 'wcgs' ),
//             'default'           => 'id',
//             'options' => array( 'id'=>__('Image ID','wcgs'),
//                                 'src'=> __('Image URL','wcgs'),
                              
//                             ),
//             'id'       => 'wcgs_image_import',
//             'desc'       => __( 'Set image import type using existing Id or external url', 'wcgs' ),
//             'desc_tip'      => true,
//         ),
        
//         array(
//             'title'		=> __( 'Chunks size', 'wcgs' ),
//             'type'		=> 'number',
//             'desc'		=> __( 'Set chunk size to import/export larger group of data', 'wcgs' ),
//             'default'	=> __('30', 'wcgs'),
//             'id'		=> 'wcgs_wc_chunk_size',
//             'css'   	=> 'min-width:300px;',
// 			'desc_tip'	=> true,
//         ),
        
		array(
			'type' => 'sectionend',
			'id'   => 'wcgs_woocommerce_gs',
		),
		
    	);
        
        
	return apply_filters('wcgs_settings_data', $wcgs_settings);
		
}

// Get last_sync date
function wcgs_get_last_sync_date() {
    
    return date('Y-m-d h:i:sa', time());
}

// Chunk size
function wcgs_get_chunk_size(){
    $chunksize = get_option('wcgs_wc_chunk_size', 30);
    return apply_filters('wcgs_chunk_size', intval($chunksize));
}

// Chunk size Syncback
function wcgs_syncback_get_chunk_size(){
    $chunksize = get_option('wcgs_wc_chunk_size', 50);
    return apply_filters('wcgs_syncback_chunk_size', intval($chunksize));
}


function wcgs_log ( $log )  {
    
    if ( WCGS_LOG ) {
        if ( is_array( $log ) || is_object( $log ) ) {
              $resp = error_log( print_r( $log, true ), 3, WCGS_PATH.'/log/wcgs.txt' );
        } else {
              $resp = error_log( $log, 3, WCGS_PATH.'/log/wcgs.txt' );
        }
    }
}

function wcgs_log_dump ( $log )  {
    
    if ( WCGS_LOG ) {
        ob_start();                    // start buffer capture
        var_dump( $log );           // dump the values
        $contents = ob_get_clean(); // put the buffer into a variable
        $resp = error_log( $contents, 3, WCGS_PATH.'/log/wcgs.txt' );
    }
}

// Set item meta with key: wcgs_row_id
function wcgs_resource_update_meta($resource, $id, $row_no){
    
    $id = intval($id);
    $row_no = intval($row_no);
    switch($resource){
        case 'categories':
            update_term_meta($id, 'wcgs_row_id', $row_no);
            break;
        case 'products':
            update_post_meta($id, 'wcgs_row_id', $row_no);
            break;
    }
}

// Category: create category range to sync-back
function wcgs_category_range_for_update($category_id){
    
    $row_id = (int) get_term_meta($category_id, 'wcgs_row_id', true);
    if( !$row_id ) return null;

    $wcapi = new WCGS_WC_API();
    $sync_val = 'SYNCBACK';
    $row = $wcapi->get_category_for_gsheet($category_id, $sync_val);
    
    // ppom_pa($row); exit;
    
    $updatable_data = array('id','name', 'slug', 'parent', 'last_sync');
    $updatable_data = apply_filters('wcgs_category_update_data', $updatable_data);
    
    $sync_col = wcgs_get_sheet_info('categories', 'sync_col');
    
    $range = "categories!A{$row_id}:{$sync_col}{$row_id}";
    // $ranges_value[$range] = $row;   
    
    return [$range => $row];
}

// Getting last_sync index by category
function wcgs_get_las_sync_index_by_sheet($sheet){
    
    $header = [];
    switch($sheet){
        case 'categories':
            $header = get_option('wcgs_category_header');
            break;
    }
    
    $index = isset($header['last_sync']) ? $header['last_sync'] : null;
    return $index;
}

// Getting sheet info
function wcgs_get_sheet_info($sheet, $key) {
    
    $value = '';
    $options = get_option("wcgs_{$sheet}_info", true);
    if($options){
        $value =  $options[$key];
    }
    return  $value;
}

// Admin notices array
function wcgs_admin_notice_error($msg){
    return ['message'=>$msg, 'class'=>'error'];
}

function wcgs_admin_notice_success($msg){
    return ['message'=>$msg, 'class'=>'success'];
}

// get categories linked
function wcgs_get_linked_categories_ids() {
    
    global $wpdb;
    $qry = "SELECT DISTINCT term_id FROM {$wpdb->prefix}term_taxonomy WHERE";
    $qry .= " taxonomy = 'product_cat'";
    $syncback_setting = get_option('wcgs_syncback_settings');
    $qry .= " AND EXISTS (SELECT * from {$wpdb->prefix}termmeta where {$wpdb->prefix}termmeta.term_id = {$wpdb->prefix}term_taxonomy.term_id AND {$wpdb->prefix}termmeta.meta_key = 'wcgs_row_id');";
    
    $result = $wpdb->get_results($qry, ARRAY_N);
    $result = array_map(function($c){
        return $c[0];
    }, $result);
    
    return apply_filters('wcgs_non_linked_categories_ids', $result);
}

// get categories not linked
function wcgs_get_non_linked_categories_ids() {
    
    global $wpdb;
    $qry = "SELECT DISTINCT term_id FROM {$wpdb->prefix}term_taxonomy WHERE";
    $qry .= " taxonomy = 'product_cat'";
    $syncback_setting = get_option('wcgs_syncback_settings');
    $qry .= " AND NOT EXISTS (SELECT * from {$wpdb->prefix}termmeta where {$wpdb->prefix}termmeta.term_id = {$wpdb->prefix}term_taxonomy.term_id AND {$wpdb->prefix}termmeta.meta_key = 'wcgs_row_id');";
    
    $result = $wpdb->get_results($qry, ARRAY_N);
    $result = array_map(function($c){
        return $c[0];
    }, $result);
    
    return apply_filters('wcgs_non_linked_categories_ids', $result);
}

// get products not linked
function wcgs_get_linked_products_ids() {
    
    global $wpdb;
    
    $qry = "SELECT DISTINCT ID FROM {$wpdb->prefix}posts WHERE";
    $qry .= " post_type = 'product'";
    $qry .= " AND post_status = 'publish'";
    $qry .= " AND EXISTS (SELECT * from {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID AND {$wpdb->prefix}postmeta.meta_key = 'wcgs_row_id');";
    
    $result = $wpdb->get_results($qry, ARRAY_N);
    $result = array_map(function($c){
        return $c[0];
    }, $result);
    
    return apply_filters('wcgs_non_linked_products_ids', $result);
}

// get products not linked
function wcgs_get_non_linked_products_ids() {
    
    global $wpdb;
    
    $qry = "SELECT DISTINCT ID FROM {$wpdb->prefix}posts WHERE";
    $qry .= " post_type = 'product'";
    $qry .= " AND post_status = 'publish'";
    $qry .= " AND NOT EXISTS (SELECT * from {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID AND {$wpdb->prefix}postmeta.meta_key = 'wcgs_row_id');";
    
    $result = $wpdb->get_results($qry, ARRAY_N);
    $result = array_map(function($c){
        return $c[0];
    }, $result);
    
    return apply_filters('wcgs_non_linked_products_ids', $result);
}

// Check if store is connected with valid authcode
function wcgs_verfiy_connected($sheet_authcode){
    $return = false;
    $authcode = wcgs_get_option('wcgs_authcode');
    if( intval($sheet_authcode) == $authcode ) {
        update_option('wcgs_wcgs_connected', true);
        $return = true;
    }else{
        delete_option('wcgs_wcgs_connected');
    }
    
    return $return;
}

function wcgs_is_connected(){
    $return = false;
    $result = get_option('wcgs_wcgs_connected', false);
    if( $result ){
        $return = true;
    }
    return $return;
}


function wcgs_get_product_meta_col_value($product, $col_key){
    
    $meta_cols = array_filter($product['meta_data'], function($m) use($col_key){
      return $m->key == $col_key;
    });
    
    $value_found = '';
    if(isset($meta_cols[0])){
        $value_found = is_array($meta_cols[0]->value) ? json_encode($meta_cols[0]->value) : $meta_cols[0]->value;
        // $value_found = $meta_cols[0]->value;
    }
    
    return $value_found;
}

function wcgs_is_service_connect() {
    
    $service_conn = get_option('wcgs_service_connect', false);
    return $service_conn;
}


function wcgs_quick_connect_url() {
    
    $args = ['request_from' => get_bloginfo('url'), 'redirect_url'=>get_rest_url(null, 'wcgs/v1/quickconnect')];
    $url = add_query_arg($args, WCGS_QCONN_URL);
    return $url;
}