<?php
/**
 * WP Callbacks
 * */
// use \AvangateClient\Client;
 
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
            $message = sprintf(__("%s - ID (%s) \r\n", 'wcgs'), $error->error->message, $error->id);
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

add_action('wp_ajax_wcgs_sync_chunk_products', 'wcgs_sync_chunk_products', 99, 1);
function wcgs_sync_chunk_products($send_json=true) {
    
    if ( is_admin() && ( defined( 'DOING_AJAX' ) || DOING_AJAX ) ) {
        $send_json = true;
    }
    
    $saved_chunked = get_transient('wcgs_product_chunk');
    $chunk = isset($_POST['chunk']) ? $_POST['chunk'] : '';
    
    if( !isset($saved_chunked[$chunk]) ) {
        $response['status'] = 'error';
        $response['message'] = __("No chunk found to sync","wcgs");
        wp_send_json($response);
    }
    
    // wp_send_json($saved_chunked[$chunk]);
    $chunked_rows = $saved_chunked[$chunk];
    
    $sheet_name = 'products';
    
    $sync_result = null;
    
    $product = new WCGS_Products();
    $sync_result = $product->sync($chunked_rows);
    
    // if( $sync_result == null ) return '';
    // wcgs_pa($sync_result);
    
    $response = array();
    $response['raw'] = $sync_result;
    // parse erros
    if( isset($sync_result['batch_errors']['Batch_Errors']) && count($sync_result['batch_errors']['Batch_Errors']) > 0 ){
        foreach($sync_result['batch_errors']['Batch_Errors'] as $error){
            $message = sprintf(__("%s - ID (%s) \r\n", 'wcgs'), $error->error->message, $error->id);
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
    
    if( $send_json ) {
        wp_send_json($response);
    } else {
        return $response;
    }
}

add_action('wp_ajax_wcgs_sync_data_products', 'wcgs_chunk_products', 99, 1);
function wcgs_chunk_products($send_json=true) {
    
    if ( is_admin() && ( defined( 'DOING_AJAX' ) || DOING_AJAX ) ) {
        $send_json = true;
    }
    
    $product = new WCGS_Products();
    $chunks = $product->get_chunks();
    
    $response['status'] = 'success';
    $response['message'] =  __("No data to sync", "wcgs");
        
    if( $chunks ) {
        $response['status'] = 'chunked';
        $response['chunks'] =  $chunks;
        $response['message'] =  sprintf(__("Total %d Products found, chunked into %d", "wcgs"), $chunks['total_products'], $chunks['chunks']);
    }
    
    if( $send_json ) {
        wp_send_json($response);
    } else {
        return $response;
    }
    
}