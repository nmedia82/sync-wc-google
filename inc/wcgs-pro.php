<?php
// Google Sync PRO Class

class WCGS_PRO {
    
    function __construct() {
        
        // Add variations into Sync Array
        add_filter('wcgs_sync_array', array($this, 'add_varition_option') );
        // add_action('wcgs_after_products_updated', array($this, 'add_variations'), 11, 3);
        
        add_action('wp_ajax_wcgs_sync_data_variations', array($this, 'wcgs_sync_variations') );
    }
    
    
    function add_varition_option($sync_array){
        
        $sync_array['variations'] = __('Variations','wcgs');
        return $sync_array;
    }
    
    function wcgs_sync_variations(){
        
        $sheet_name = isset($_POST['sheet']) ? sanitize_text_field($_POST['sheet']) : '';
        
        $variation = new WCGS_Variations();
        $sync_result = $variation->sync();
        // wcgs_pa($sync_result);
        
        $response = array();
        $message = '';
        $response['raw'] = $sync_result;
        // parse erros
        if( isset($sync_result['batch_errors']['Batch_Errors']) && $sync_result['batch_errors']['Batch_Errors'] != '' ){
            foreach($sync_result['batch_errors']['Batch_Errors'] as $product_id => $error){
                $variation_id = isset($error->error->data->resource_id) ? $error->error->data->resource_id : '';
                
                $message .= '<div class="error updated notice">';
                if( $variation_id ) {
                    $message .= sprintf(__("%s - [Variation ID = %s, Product ID = %s] \r\n", 'wcgs'), $error->error->message, $variation_id,$product_id);
                }else{
                    $message .= sprintf(__("%s - [Product ID = %s] \r\n", 'wcgs'), $error->error->message, $product_id);
                }
                
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
            
        }
        
        $response['status'] = 'message_response';
        $response['message'] = $message;
        
        wp_send_json($response);
    }
    
    function add_variations($item, $data, $product_ids){
        
        $batch_variation = [];
        // wcgs_pa($data);
        if( count($product_ids) > 0 ) {
            
            if( isset($product_ids['create']) ) {
                foreach($product_ids['create'] as $row_no => $product_id){
                        if( isset($data['create'][$row_no]['variations']) ) 
                            $batch_variation['create'][] = $data['create'][$row_no]['variations'];   
                }
            }
            
            if( isset($product_ids['update']) ) {
                foreach($product_ids['update'] as $row_no => $product_id){
                        if( isset($data['update'][$row_no]['variations']) )
                            $batch_variation['update'][] = $data['update'][$row_no]['variations'];
                }
            }
        }
        
        wcgs_pa($batch_variation);
    }
}

new WCGS_PRO;