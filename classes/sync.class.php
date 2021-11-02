<?php
/**
 * Google Sheets Controller
 * 
 * Saving sheet data/info in option key wcgs_{sheetname}_info
 * ==> keys: sheet_name, sync_col, header_data
 * 
 * */

class WCGS_Sync {
    
    static $sheet_id;
    static $sheet;
    function __construct() {
        
        
        self::$sheet_id = '1X9dG492eyzx-s-WVjpGoXGTcQLUHoEkTBTBqmJFqdlQ'; //wcgs_get_option('wcgs_googlesheet_id');
        self::$sheet = new WCGS_Sheet2(self::$sheet_id);
        
        add_action('wp_ajax_wcgs_sync_data_products', array($this, 'chunk_products'), 99, 1);
        add_action('wp_ajax_wcgs_sync_chunk_products', array($this, 'sync_chunk_products'), 99, 1);
    }
    
    // creating chunk
    function chunk_products($send_json=true) {
    
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        $chunks = self::$sheet->create_chunk('products');
        
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
    
    // start syncing products chunk by chunk
    function sync_chunk_products($send_json=true, $chunk=null) {
    
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        $saved_chunked  = get_transient('wcgs_product_chunk');
        $sync_col_index = get_transient('wcgs_product_sync_col_index');
        
        if( $chunk === null ) 
            $chunk = isset($_POST['chunk']) ? $_POST['chunk'] : '';
            
        $response = array();
        if( !isset($saved_chunked[$chunk]) ) {
            $response['status'] = 'error';
            $response['message'] = __("No chunk found to sync","wcgs");
            return $send_json ? wp_send_json($response) : $response;
        }
        
        $chunked_rows = array_map(function($row) use($sync_col_index){
            return array_pad($row, $sync_col_index, "");
        }, $saved_chunked[$chunk]);
        
        wp_send_json($saved_chunked[$chunk]);
        
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
        
}

return new WCGS_Sync();