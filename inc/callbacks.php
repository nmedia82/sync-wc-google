<?php
/**
 * WP Callbacks
 * */

// check service account connention
add_action('wp_ajax_wcgs_check_service_connect', 'wcgs_check_service_connect');

function wcgs_check_service_connect(){
    
    if ( !is_admin() ||  ! ( defined( 'DOING_AJAX' ) || DOING_AJAX ) ) {
        wp_send_json_error('Oops, try again');
    }
    
    $gs = new WCGS_APIConnect();
    $info = $gs->getSheetInfo();
    if( !is_wp_error($info) ) {
        update_option('wcgs_service_connect', true);
        wp_send_json_success('Excellent, Connection Ok.', 'wcgs');
    } else {
        delete_option('wcgs_service_connect');
        $message = $info->get_error_message();
        if( $info->get_error_code() == 'gs_connection_error' ) {
            $message = __('Connection failed, make sure you have shared your connected Google Sheet with following email <br><br> google-sync-service-account-2@lateral-array-290609.iam.gserviceaccount.com', 'wcgs');
        }
        wp_send_json_error($message);
    }
}

add_action('wp_ajax_wcgs_sync_data_categories', 'wcgs_sync_data_categories');
function wcgs_sync_data_categories($send_json=true) {
    
    if ( is_admin() && ( defined( 'DOING_AJAX' ) || DOING_AJAX ) ) {
        $send_json = true;
    }
    
    $sheet_name = 'categories';
    
    $sync_result = null;
    $category = new WCGS_Categories();
    $sync_result = $category->sync();
    
    // if( $sync_result == null ) return '';
    
    // wcgs_pa($sync_result);
    
    $response = array();
    $response['raw'] = $sync_result;
    // parse erros
    if( isset($sync_result['batch_errors']['Batch_Errors']) && count($sync_result['batch_errors']['Batch_Errors']) > 0 ){
        foreach($sync_result['batch_errors']['Batch_Errors'] as $error){
            $message = sprintf(__("%s - ID (%s) \r\n", 'wcgs'), $error['error']['message'], $error['id']);
        }
        
        $response['status'] = 'error';
        $response['message'] = $message;
    }else if( ! empty($sync_result['rest_error']) ){
        
        $link = 'https://clients.najeebmedia.com/forums/topic/error-while-using-google-sync/';
        $message = sprintf(__("%s - <a href='%s' target='_blank'>See this</a>", 'wcgs'), $sync_result['rest_error'], $link);
        
        $response['status'] = 'error';
        $response['message'] = $message;
    } else {
        
        $rows_updated = isset($sync_result['sync_result']['totalUpdatedRows']) ? $sync_result['sync_result']['totalUpdatedRows'] : null;
        if( $rows_updated != null ) {
            $message = sprintf(__("Total %d Rows updated", 'wcgs'), $rows_updated);
        }elseif($sync_result['no_sync']){
            $message = __("No data to sync", "wcgs");
        }
        
        $response['status'] = 'success';
        $response['message'] = $message;
        
    }
    
    if( $send_json ) {
        wp_send_json($response);
    } else {
        return $response;
    }
}

add_action('wp_ajax_wcgs_sync_data_products', 'wcgs_chunk_products', 99, 1);
function wcgs_chunk_products($send_json=true) {
    
    if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        $send_json = true;
    }
    
    $product = new WCGS_Products();
    $chunks = $product->get_chunks();
    
    $response = [];
    
    if( is_wp_error($chunks) ) {
        $response['status'] = 'error';
        $response['message'] =  $chunks->get_error_message();
    } else {
    
        $response['status'] = 'success';
        $response['message'] =  __("No data to sync", "wcgs");
            
        if( $chunks ) {
            $response['status'] = 'chunked';
            $response['chunks'] =  $chunks;
            $response['message'] =  sprintf(__("Total %d Products found, chunked into %d", "wcgs"), $chunks['total_products'], $chunks['chunks']);
        }
    }
    
    if( $send_json ) {
        wp_send_json($response);
    } else {
        return $response;
    }
    
}

// Sending single chunk
add_action('wp_ajax_wcgs_sync_chunk_products', 'wcgs_sync_chunk_products', 99, 1);
function wcgs_sync_chunk_products($send_json=true, $chunk=null) {
    
    if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        $send_json = true;
    }
    
    $saved_chunked = get_transient('wcgs_product_chunk');
    
    if( $chunk === null ) 
        $chunk = isset($_POST['chunk']) ? $_POST['chunk'] : '';
        
    $response = array();
    if( !isset($saved_chunked[$chunk]) ) {
        $response['status'] = 'error';
        $response['message'] = __("No chunk found to sync","wcgs");
        return $send_json ? wp_send_json($response) : $response;
    }
    
    // @TODO: send via ajax
    $sync_col = wcgs_get_sheet_info('products','sync_col');
    $sync_col_index = wcgs_header_letter_to_index($sync_col);
    
    $chunked_rows = array_map(function($row) use($sync_col_index){
        return array_pad($row, $sync_col_index, "");
    }, $saved_chunked[$chunk]);
    
    // wp_send_json($chunked_rows);
    
    $sheet_name = 'products';
    
    $sync_result = null;
    
    $product = new WCGS_Products();
    $sync_result = $product->sync($chunked_rows);
    // wcgs_pa($saved_chunked);
    
    // if( $sync_result == null ) return '';
    // var_dump($sync_result);
    
    $response['raw'] = $sync_result;
    // parse erros
    if( isset($sync_result['batch_errors']['Batch_Errors']) && count($sync_result['batch_errors']['Batch_Errors']) > 0 ){
        foreach($sync_result['batch_errors']['Batch_Errors'] as $error){
            $message = sprintf(__("%s - ID (%s) \r\n", 'wcgs'), $error['error']['message'], $error['id']);
        }
        
        $response['status'] = 'error';
        $response['message'] = $message;
    }else if( ! empty($sync_result['rest_error']) ){
        
        $link = 'https://clients.najeebmedia.com/forums/topic/error-while-using-google-sync/';
        $message = sprintf(__("%s - <a href='%s' target='_blank'>See this</a>", 'wcgs'), $sync_result['rest_error'], $link);
        
        $response['status'] = 'error';
        $response['message'] = $message;
    } else {
        
        $rows_updated = isset($sync_result['sync_result']['totalUpdatedRows']) ? $sync_result['sync_result']['totalUpdatedRows'] : null;
        if( $rows_updated != null ) {
            $message = sprintf(__("Total %d Rows updated", 'wcgs'), $rows_updated);
        }elseif($sync_result['no_sync']){
            $message = __("No data to sync", "wcgs");
        }
        
        $response['status'] = 'success';
        $response['message'] = $message;
        
    }
    
    $response['status'] = 'message_response';
    $response['message'] = $message;
    
    return $send_json ? wp_send_json($response) : $response;
}

// add_action('wp_ajax_wcgs_sync_data_products', 'wcgs_sync_test_rest', 99, 1);
function wcgs_sync_test_rest() {
    
    $request = new WP_REST_Request( 'GET', '/wc/v3/batch' );
    $request->set_query_params( [ 'per_page' => 12 ] );
    $response = rest_do_request( $request );
    wp_send_json($response->get_headers());
}