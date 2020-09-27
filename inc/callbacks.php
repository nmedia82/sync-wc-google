<?php
/**
 * WP Callbacks
 * */
use \AvangateClient\Client;
 
add_action('wp_ajax_wcgs_sync_data', 'wcgs_sync_data');
function wcgs_sync_data() {
    
    // if (defined('DOING_AJAX') && DOING_AJAX)
        // wp_send_json($_POST);
    
    $sheet_name = isset($_POST['sheet']) ? sanitize_text_field($_POST['sheet']) : '';
    
    $sync_result = null;
    
    switch( $sheet_name ) {
        case 'categories';
            $category = new WCGS_Categories();
            $sync_result = $category->sync();
        break;
        
        case 'products';
            $product = new WCGS_Products();
            $sync_result = $product->sync();
        break;
        
    }
    
    $response = array();
    $response['raw'] = $sync_result;
    // parse erros
    if( $sync_result['batch_errors']['Batch_Errors'] ){
        foreach($sync_result['batch_errors']['Batch_Errors'] as $error){
            $message = sprintf(__("%s - ID (%s) \r\n", 'wcgs'), $error->error->message, $error->id);
        }
        
        $response['status'] = 'error';
        $response['message'] = $message;
    } else {
        
        $rows_updated = $sync_result['sync_result']['totalUpdatedRows'];
        if( $rows_updated != null ) {
            $message = sprintf(__("Total %d Rows updated", 'wcgs'), $rows_updated);
        }elseif($sync_result['no_sync']){
            $message = __("No data to sync", "wcgs");
        }
        
        $response['status'] = 'success';
        $response['message'] = $message;
        
    }
    
    wp_send_json($response);
}