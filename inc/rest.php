<?php
/**
 * Rest API Handling
 * 
 * */

if( ! defined('ABSPATH') ) die('Not Allowed.');

/* == rest api == */
add_action( 'rest_api_init', 'wcgs_rest_api_register'); // endpoint url 
function wcgs_rest_api_register() {
    
    
    register_rest_route('nkb/v1', '/auth/', array(
        'methods' => 'GET',
        'callback' => 'wcgs_google_auth_code',
        'permission_callback' => '__return_true',
    ));
    
    // // Google App Script API
    // register_rest_route('wcgs/v1', '/googlesync/', array(
    //     'methods' => 'POST',
    //     'callback' => 'wcgs_sync_row',
    //      'permission_callback' => '__return_true'
    // ));
    
    // Google App Script API
    register_rest_route('wcgs/v1', '/connect-store', array(
        'methods' => 'POST',
        'callback' => 'wcgs_connect_store',
         'permission_callback' => '__return_true'
    ));
    
    // Google App Script API BULK Meta Update
    register_rest_route('wcgs/v1', '/link-data', array(
        'methods' => 'POST',
        'callback' => 'wcgs_link_data',
         'permission_callback' => '__return_true'
    ));
    
    
    // Google App Script: Sync Data from Sheet
    register_rest_route('wcgs/v1', '/sync-sheet-data', array(
        'methods' => 'POST',
        'callback' => 'wcgs_sync_sheet',
        'permission_callback' => '__return_true'
    ));
    
    // PRO: Fetch products
    register_rest_route('wcgs/v1', '/fetch-products', array(
        'methods' => 'POST',
        'callback' => 'wcgs_fetch_products',
        'permission_callback' => '__return_true'
    ));
    
    // PRO: Fetch categories
    register_rest_route('wcgs/v1', '/fetch-categories', array(
        'methods' => 'POST',
        'callback' => 'wcgs_fetch_categories',
        'permission_callback' => '__return_true'
    ));
    
    // PRO: Developer mode
    register_rest_route('wcgs/v1', '/unlink-rows', array(
        'methods' => 'POST',
        'callback' => 'wcgs_unlink_rows',
        'permission_callback' => '__return_true'
    ));
    
    // PRO: Chunker
    register_rest_route('wcgs/v1', '/do-chunks', array(
        'methods' => 'POST',
        'callback' => 'wcgs_create_chunks',
        'permission_callback' => '__return_true'
    ));
    
    register_rest_route('wcgs/v1', '/quickconnect', array(
        'methods' => 'GET',
        'callback' => 'wcgs_quick_connect',
        'permission_callback' => '__return_true'
    ));
}


function wcgs_google_auth_code( $request ) {
    
    if( ! isset($_GET['code']) ) wp_die('Code Not Found', 'Google Code invalid');
    
    $authCode = sanitize_text_field($_GET['code']);
    $gs = new WCGS_APIConnect();
    
    $gs->getClient($authCode);
    
    $url = add_query_arg('wcgs_code', 'added', WCGS_SETTING_URL);
    wp_redirect($url);
    exit;
}

function wcgs_sync_row($request){
    
    
    $params = $request->get_params();
    $sheet_name = $params['sheet_name'];
    
    // wcgs_log($params); return;
    
    $response = [];
    
    switch($sheet_name){
        
        case 'categories':
            $response = wcgs_live_sync_categories($params);
        break;
        
        case 'products':
            $response = wcgs_live_sync_products($params);
        break;
    }
    
    wp_send_json($response);
}


// Update Mete Bulk
function wcgs_link_data($request){
    
    $data = $request->get_params();
    $updatable_rows = json_decode($data['product_rows'], true);
    $sync_col = $data['sync_col'];
    $sheet_name = $data['sheet_name'];
    // wcgs_log($data);
    
    $ranges = [];
    if($updatable_rows){
        foreach($updatable_rows as $row){
            wcgs_resource_update_meta($sheet_name, $row['id'], $row['rowno']);
            $ranges["{$sheet_name}!{$sync_col}{$row['rowno']}"] = ['OK'];
        }
    }
    // wcgs_log($ranges); exit;
    
    if( count($ranges) > 0 ) {
        $gs = new WCGS_APIConnect();
        $resp = $gs->update_rows_with_ranges($ranges);
    }
    
    wp_send_json_success();
}

// Live syncing categories
function wcgs_live_sync_categories($params){
    
    $row = json_decode($params['row']);
    $row = reset( $row );
    $row_no = intval( $params['rowno'] );
    $id_col = $params['id_col'];
    $sync_col = $params['sync_col'];
    
    $wcgs_category = new WCGS_Categories();
    $row = $wcgs_category->build_row_for_wc_api($row);
    
    // wcgs_log($row); exit;
    
    $response = [];
    
    $resp1 = $wcgs_category->wc_update_category($row);
    if( ! is_wp_error($resp1) ) {
        //Building the range => value data for sync-back
        // id and sync column only
        $range = ["categories!{$id_col}{$row_no}" => [$resp1['id']], "categories!{$sync_col}{$row_no}" => ["OK"]];
        $gs = new WCGS_APIConnect();
        $resp2 = $gs->update_rows_with_ranges($range);
        $response = ['status'=> true, 'message'=> 'Sync completed successfully'];
    } else {
        $response = ['status'=> false, 'message'=>$resp1->get_error_message()];
    }
    
    return $response;
    
}


// Live syncing categories
function wcgs_live_sync_products($params){
    
    $row = json_decode($params['row']);
    $row = reset( $row );
    $row_no = intval( $params['rowno'] );
    $id_col = $params['id_col'];
    $sync_col = $params['sync_col'];
    
    $wcgs_product = new WCGS_Products();
    $row = $wcgs_product->build_row_for_wc_api($row,null);
    
    // wcgs_log($row); exit;
    
    $response = [];
    
    $resp1 = $wcgs_product->wc_update_product($row);
    if( ! is_wp_error($resp1) ) {
        //Building the range => value data for sync-back
        // id and sync column only
        $range = ["products!{$id_col}{$row_no}" => [$resp1['id']], "products!{$sync_col}{$row_no}" => ["OK"]];
        $gs = new WCGS_APIConnect();
        $resp2 = $gs->update_rows_with_ranges($range);
        $response = ['status'=> true, 'message'=> 'Sync completed successfully'];
    } else {
        $response = ['status'=> false, 'message'=>$resp1->get_error_message()];
    }
    
    return $response;
    
}

function wcgs_connect_store($request) {
    // wp_send_json($_POST);
    
    $data   = $request->get_params();
    $header = json_decode($request->get_param('header_data'), true);
    $header = reset($header);
    $data['header_data'] = $header;
    
    if( !wcgs_verfiy_connected($data['authcode']) ){
        wp_send_json_error(__("Sorry, but your AuthCode is not valid", 'wcgs'));
    }
    
    if( ! wcgs_is_service_connect() ) {
        wp_send_json_error(__("Connection failed, make sure you have shared your connected Google Sheet with the following email: \r\n\r\n google-sync-service-account-2@lateral-array-290609.iam.gserviceaccount.com", 'wcgs'));
    }
    
    // wcgs_log($data); exit;
    $wcgs_sheet = new WCGS_Sheet();
    $wcgs_sheet->update($data);
    
    wp_send_json_success(__("Good Job! Sheet is connected", 'wcgs'));
}

function wcgs_sync_sheet($request) {
    
    if( ! wcgs_is_connected() ){
        wp_send_json_error(__("Sorry, but your AuthCode is not valid", 'wcgs'));
    }
    
    // wcgs_log($request->get_params()); return 
    $header = json_decode($request->get_param('header_data'), true);
    $header = reset($header);
    
    $sheet_data = json_decode($request->get_param('sheet_data'), true);
    
    $data = $request->get_params();
    $data['header_data'] = $header;
    $data['sheet_data']  = $sheet_data;
            
    // wcgs_log($data);
    
    $wcgs_sheet = new WCGS_Sheet();
    
    switch( $data['sheet_name'] ) {
        case 'products':
            $result = $wcgs_sheet->sync_data_products($data);
        break;
        
        case 'categories':
            $result = $wcgs_sheet->sync_data_categories($data);
        break;
    }
    
    // wcgs_log($result);
    
    if( is_wp_error($result) ) {
        wp_send_json_error($result->get_error_message());
    }else{
        wp_send_json_success($result);
    }
}

// Fetch products from store
function wcgs_fetch_products($request) {
    
    $header = json_decode($request->get_param('header_data'), true);
    $header = reset($header);
    $data   = $request->get_params();
    $data['header_data'] = $header;
    $data['request_args'] = isset($data['request_args']) ? json_decode($data['request_args'], true) : null;
    
    $wcapi = new WCGS_WC_API_V3();
    $result = $wcapi->get_products_for_syncback($data);
    // wcgs_log_dump($result);
    // exit;
    
    $total_rows=$total_create=$total_update=0;
    
    
    if( isset($data['last_row']) ) {
        
        $updatable_range = [];
        $column_no = 'A';
        
        if( isset($result['update']) ) {
            $total_update = count($result['update']);
            foreach($result['update'] as $no => $row){
                $updatable_range["products!{$column_no}{$no}"] = $row;
            }
        }
        
        $last_row = intval($data['last_row']);
        if( isset($result['create']) ) {
            $total_create = count($result['create']);
            foreach($result['create'] as $no => $row){
                $last_row++;
                $updatable_range["products!{$column_no}{$last_row}"] = $row;
                
                // Linking the new products
                $product_id = $row[0];
                wcgs_resource_update_meta("products", $product_id, $last_row);
            }
        }
        
        
        // wcgs_log($updatable_range); exit;
        if( count($updatable_range) > 0 ) {
            $gs = new WCGS_APIConnect();
            $resp = $gs->update_rows_with_ranges($updatable_range);
        }
        
        // wcgs_log($resp);
        $total_rows = $total_create+$total_update;
        
        $message = "{$total_create} = Created, {$total_update} = Updated \r\n";
        $message .= "TOTAL = {$total_rows}";
        
        wp_send_json_success(['message'=>$message, 'create'=>$total_create,'update'=>$total_update]);
        exit;
        // exit;
    
    }
    
    
    if( is_wp_error($result) ) {
        wp_send_json_error($result->get_error_message());
    }else{
        // wcgs_log($result); exit;
        wp_send_json_success($result);
    }
}

// Fetch categories from store
function wcgs_fetch_categories($request) {
    
    $header = json_decode($request->get_param('header_data'), true);
    $header = reset($header);
    $data   = $request->get_params();
    $data['header_data'] = $header;
    $data['request_args'] = isset($data['request_args']) ? json_decode($data['request_args'], true) : null;
    
    // wcgs_log($data); exit;
    
    $wcapi = new WCGS_WC_API_V3();
    $result = $wcapi->get_categories_for_syncback($data);
    
    if( is_wp_error($result) ) {
        wp_send_json_error($result->get_error_message());
    }else{
        // wcgs_log($result); exit;
        wp_send_json_success($result);
    }
}

// Remove all meta link from products & categories
function wcgs_unlink_rows($request) {
    
    $data   = $request->get_params();
    // wcgs_log($data); exit;
    global $wpdb;
    $val = 'wcgs_row_id';
    
    switch( $data['sheet_name'] ) {
        case 'products':
            $table = "{$wpdb->prefix}postmeta";
            $wpdb->delete( $table, array( 'meta_key' => $val ) );
        break;
        
        case 'categories':
            $table = "{$wpdb->prefix}termmeta";
            $wpdb->delete( $table, array( 'meta_key' => $val ) );
        break;
    }
    
    
    wp_send_json_success(['message'=>'All keys removed']);
    
}

function wcgs_create_chunks($request) {
    
    $data = $request->get_params();
    // wcgs_log($data);
    
    $wcapi = new WCGS_WC_API_V3();
    $result = $wcapi->create_product_chunks($data);
    
    
    wp_send_json_success($result);
}


function wcgs_quick_connect($request) {
    
    $authCode = $request->get_param('gcode');
    // wcgs_log($data);
    
    try{
        
        $client = new Google_Client();
        $client->setApplicationName('GoogleSync Connect');
        // // FullAccess
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        
        $gs_credentials = WCGS_PATH.'/quickconnect/creds.json';
        $client->setAuthConfig( $gs_credentials );
        if( $authCode ) {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            // var_dump($accessToken);
            update_option('wcgs_token', json_encode($accessToken));
            $client->setAccessToken($accessToken);
        }
        
        // wcgs_log($accessToken);
        $url = add_query_arg('wcgs_code', 'added', WCGS_SETTING_URL);
        wp_redirect($url);
        exit;
        
    }catch (\Exception $e)
    {
        // wcgs_pa($e);
        $object = json_decode($e->getMessage(), true);
        wp_die("Oops, you cannot connected right try again or contact plugin developer: ".$object['error']['message']);
    }
}

// =========== REMOT POST/GET ==============
function wcgs_send_google_rest_request($action, $args){
    
    $url = wcgs_get_option('wcgs_appurl');
    if( ! $url ) {
        set_transient("wcgs_admin_notices", wcgs_admin_notice_error('Google WebApp URL not defined','wcgs'), 30);
        return;
    }
    
    $url .= "?action={$action}&args=".json_encode($args);
    
    $response = wp_remote_get($url);
    // wcgs_log($response);
    $responseBody = wp_remote_retrieve_body( $response );
    $result = json_decode( $responseBody, true );
    // wcgs_log($result);
    
    if(isset($result['status']) && $result['status'] == 'success'){
        set_transient("wcgs_admin_notices", wcgs_admin_notice_success(__('Sheet is also updated successfully'), 'wcgs'), 30);
    }else{
        set_transient("wcgs_admin_notices", wcgs_admin_notice_error(__('Error while updating Googl Sheet'), 'wcgs'), 30);
    }
}