<?php
/**
 * Google Sheet Variations Controller
 * 
 * */

class WCGS_Variations {
    
    function __construct() {
        
        $this->map = array();
        $this->rowRef = array();
        $this->rows = array();
    }
    
    function set_mapping($header) {
        
       foreach($header as $order => $key ) {
            $key = trim($key);
            $this->map[$key] = $order;
        }
        
        update_option('wcgs_variation_header', $this->map);
        // wcgs_pa($this->map);
    }
    
    function get_header() {
        
        $header = get_option('wcgs_variation_header');
        return $header;
    }
    
    function get_value($key, $row) {
        
        return isset($row[$this->map[$key]]) ? $row[$this->map[$key]] : '';
    }
    
    
    function get_data(){
        
        $gs = new WCGS_APIConnect();
        $range = 'variations';
        $this->rows = $gs->get_sheet_rows($range);
        
        // Setting mapping (index => $key)
        $this->set_mapping($this->rows[0]);
        
        unset($this->rows[0]);    // Skip heading row
        $parse_Rows = array();
        $rowRef = array();
        $rowIndex = 2;
        foreach($this->rows as $row){
            
            if( $row[WCGS_SYNC_COLUMN_INDEX] == 1 ) {
                $rowIndex++;
                continue;
            }
            
            $row = $this->build_row_for_wc_api($row);
            
            // If no product attached, no use
            if( ! isset($row['product_id']) ) continue;
            $product_id = $row['product_id'];
            
            $id   = isset($row['id']) ? $row['id'] : '';
            $sync = isset($row['sync']) ? $row['sync'] : '';
            
            // Adding the meta key in new product to keep rowNo
            $row['meta_data'][] = ['key'=>'wcgs_row_id', 'value'=>$rowIndex];
            
            
            if( $id != '' ) {
                $parse_Rows[$product_id]['update'][$rowIndex] = $row;   
            }else{
                
                unset($row['id']);
                $parse_Rows[$product_id]['create'][$rowIndex] = $row;
            }
            
            $rowIndex++;
            
        }
        
        // wcgs_pa($parse_Rows); exit;
        return $parse_Rows;
    }
    
    function build_row_for_wc_api($row, $header=null) {
        
        $data = array();
        
        $header = is_null($header) ? $this->get_header() : $header;
        
        // wcgs_pa($this->map); exit;
        foreach($header as $key => $index) {
            
            // if( empty($row[$index])  ) continue;
            
            $value = $row[$index];
            
            // getting the datatype
            $data_type = wcgs_get_datatype_by_keys('variations', $key);
            switch($data_type) {
                
                case 'object':
                case 'array':
                    $value = json_decode($value, true);
                    break;
            }
            
            // var_dump($key, $row[$index]);
            $data[ trim($key) ] = apply_filters("wcgs_variations_data_{$key}", $value, $row);
        }
        return $data;
        
    }
    
    // Sync all categories from GS to Site
    function sync() {
        
        // Get Data from Google Sheet
        $variations = $this->get_data();
        // wcgs_pa($variations); exit;
        
        if( ! $variations ) return ['no_sync'=>true];
       
        $wcapi = new WCGS_WC_API();
        $googleSheetRows = $wcapi->update_variations_batch($variations, $this->rows);
        // wcgs_pa($googleSheetRows);
        
        // Get the Range Value for last_sync column
        $header_values = $this->get_header();
        $last_sync_index = $header_values['last_sync'];
        $last_sync_cell = wcgs_get_header_column_by_index($last_sync_index);
        
        // Now getting the ID from newly created product and update Google Sheeet row
        $gs = new WCGS_APIConnect();
        
        // If Client is authrized
        $sync_result = '';
        if ( ! $gs->auth_link ) {
            
            $sync_result = $gs->update_rows('variations', $googleSheetRows, $last_sync_cell);
            do_action('wcgs_after_variations_synced', $googleSheetRows, 'variations', $sync_result);
            // return $result;
        }
        
        $error_message = array();
        if ( false !== ( $batch_update_error = get_transient( 'wcgs_batch_error' ) ) ) {
            // wcgs_pa($batch_update_error);
            $error_message = array('Batch_Errors' => $batch_update_error);
            delete_transient('wcgs_batch_error');
        }
        
        $rest_error_message = '';
        if ( false !== ( $rest_api_error = get_transient( 'wcgs_rest_api_error' ) ) ) {
            $rest_error_message = $rest_api_error;
            delete_transient('wcgs_rest_api_error');
        }
        
        $response = ['sync_result'=>$sync_result, 'batch_errors'=>$error_message, 'rest_error'=>$rest_error_message];
        
        return $response;
    }
    
    // Updating categories via WC API
    // Return WC_API Response
    function wc_update_product($data) {
     
        // Check if id exists
        if( isset($data['id']) ) {
            $request = new WP_REST_Request( 'PUT', '/wc/v3/products/'.$data['parent_id'].'/variations/'.$data['id'] );    
        } else {
            $request = new WP_REST_Request( 'POST', '/wc/v3/products/'.$data['parent_id'].'/variations' );    
        }
        
        $request->set_body_params( $data );
        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            $error = $response->as_error();
            return new WP_Error( 'wc_api_error', $error->get_error_message() );
        } else{
            $response = $response->get_data();
        }
         
         do_action('wcgs_after_variation_updated', $response, $data);
        //   wcgs_pa($response);
         return $response;
    }   
}