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
    register_rest_route('wcgs/v1', '/googlesync/update-meta', array(
        'methods' => 'POST',
        'callback' => 'wcgs_update_meta',
         'permission_callback' => '__return_true'
    ));
    
    // Google App Script API BULK Meta Update
    register_rest_route('wcgs/v1', '/googlesync/update-meta-bulk', array(
        'methods' => 'POST',
        'callback' => 'wcgs_update_meta_bulk',
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
    
    wcgs_log($params); return;
    
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

// Update Mete
function wcgs_update_meta($request){
    
    $params = $request->get_params();
    // wcgs_log('==== Updating meta ===');
    // wcgs_log($params);
    
    wcgs_resource_update_meta($params['sheet_name'], $params['item_id'], $params['row_no']);
}

// Update Mete Bulk
function wcgs_update_meta_bulk($request){
    
    $params = $request->get_params();
    // wcgs_log('==== Updating meta ===');
    $updatable_rows = json_decode($params['product_rows'], true);
    // wcgs_log($updatable_rows); exit;
    
    if($updatable_rows){
        foreach($updatable_rows as $data){
            wcgs_resource_update_meta($params['sheet_name'], $data['id'], $data['rowno']);
        }
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