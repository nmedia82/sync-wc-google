<?php
/**
 * Google Sheet Products Controller
 * 
 * */

class WCGS_Products {
    
    function __construct() {
        
        add_action('wp_ajax_wcgs_sync_data_products', 'chunk_products', 99, 1);
       
    }
    
    function chunk_products($send_json=true) {
        
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
    
   
}