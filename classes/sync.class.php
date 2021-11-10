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
    // static $sheet;
    function __construct() {
        
        
        self::$sheet_id = wcgs_get_sheet_id();
        
        
        // callbacks
        add_action('wp_ajax_wcgs_sync_data_products', array($this, 'chunk_data'), 99, 1);
        add_action('wp_ajax_wcgs_sync_chunk_products', array($this, 'sync_chunk_products'), 99, 1);
        add_action('wp_ajax_wcgs_sync_data_categories', array($this, 'chunk_data'), 99, 1);
        add_action('wp_ajax_wcgs_sync_chunk_categories', array($this, 'sync_chunk_products'), 99, 1);
    }
    
    // creating chunk
    function chunk_data() {
    
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        $sheet_name = isset($_POST['sheet']) ? $_POST['sheet'] : '';
        
        $sheet_obj = new WCGS_Sheet2($sheet_name);
        $chunks = $sheet_obj->create_chunk();
        
        // wcgs_log($chunks);
        
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
                $response['message'] =  sprintf(__("Total %d Row(s) found, chunked into %d", "wcgs"), $chunks['total_rows'], $chunks['chunks']);
            }
        }
        
        if( $send_json ) {
            wp_send_json($response);
        } else {
            return $response;
        }
        
    }
    
    // start syncing products chunk by chunk
    function sync_chunk_products() {
    
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        $sheet_name = isset($_POST['sheet']) ? $_POST['sheet'] : '';
        
        $sheet_obj = new WCGS_Sheet2($sheet_name);
        $sync_result = $sheet_obj->sync();
        // wcgs_log($sync_result); exit;
        
        $response = [];
        if( is_wp_error($sync_result) ) {
            
            $response['status'] = 'error';
            $response['message'] = $sync_result->get_error_message();
        } else {
            
            $message = sprintf(__("%s Rows updated successfully and %d errors found", 'wcgs'), $sync_result['success_rows'], $sync_result['error_rows']);
            $message .= $sync_result['error_msg'];
            $response['status'] = 'success';
            $response['message'] = $message;
        }
        
        return $send_json ? wp_send_json($response) : $response;
    }
    
    // creating chunk
    function chunk_categories() {
    
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        $sheet_obj = new WCGS_Sheet2('categories');
        $chunks = $sheet_obj->create_chunk();
        
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
                $response['message'] =  sprintf(__("Total %d Products found, chunked into %d", "wcgs"), $chunks['total_rows'], $chunks['chunks']);
            }
        }
        
        if( $send_json ) {
            wp_send_json($response);
        } else {
            return $response;
        }
        
    }
        
}

return new WCGS_Sync();