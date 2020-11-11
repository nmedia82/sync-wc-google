<?php
/**
 * WP Callbacks
 * */
// use \AvangateClient\Client;
 
add_action('wp_ajax_wcgs_sync_data_categories', 'wcgs_sync_data_categories');
function wcgs_sync_data_categories() {
    
    // if (defined('DOING_AJAX') && DOING_AJAX)
        // wp_send_json($_POST);
    
    $sheet_name = isset($_POST['sheet']) ? sanitize_text_field($_POST['sheet']) : '';
    
    $sync_result = null;
    $category = new WCGS_Categories();
    $sync_result = $category->sync();
    
    // if( $sync_result == null ) return '';
    
    // wcgs_pa($sync_result);
    
    $response = array();
    $response['raw'] = $sync_result;
    // parse erros
    if( isset($sync_result['batch_errors']['Batch_Errors']) ){
        foreach($sync_result['batch_errors']['Batch_Errors'] as $error){
            $message = sprintf(__("%s - ID (%s) \r\n", 'wcgs'), $error->error->message, $error->id);
        }
        
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
    
    wp_send_json($response);
}

add_action('wp_ajax_wcgs_sync_data_products', 'wcgs_sync_data_products');
function wcgs_sync_data_products() {
    
    $sheet_name = isset($_POST['sheet']) ? sanitize_text_field($_POST['sheet']) : '';
    
    $sync_result = null;
    
    $product = new WCGS_Products();
    $sync_result = $product->sync();
    
    // if( $sync_result == null ) return '';
    // wcgs_pa($sync_result);
    
    $response = array();
    $message = '';
    $response['raw'] = $sync_result;
    // parse erros
    if( isset($sync_result['batch_errors']['Batch_Errors']) && $sync_result['batch_errors']['Batch_Errors'] != '' ){
        foreach($sync_result['batch_errors']['Batch_Errors'] as $error){
            $message .= '<div class="error updated notice">';
            $message .= sprintf(__("%s - ID (%s) \r\n", 'wcgs'), $error->error->message, $error->id);
            $message .= '</div>';
        }
        
    } 
    
    if( isset($sync_result['sync_result']['totalUpdatedRows']) ) {
        
        $rows_updated = $sync_result['sync_result']['totalUpdatedRows'];
        
        $message .= '<div class="info updated notice">';
        if( $rows_updated != null ) {
            $message .= sprintf(__("Total %d Rows updated", 'wcgs'), $rows_updated);
        }elseif($sync_result['no_sync']){
            $message .= __("No data to sync", "wcgs");
        }
        $message .= '</div>';
        
    }
    
    $response['status'] = 'message_response';
    $response['message'] = $message;
    
    wp_send_json($response);
}