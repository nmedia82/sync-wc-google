<?php
/**
 * Google Sheet Products Controller
 * 
 * */

class WCGS_Products {
    
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
        
        update_option('wcgs_product_header', $this->map);
        // wcgs_pa($this->map);
    }
    
    function get_header() {
        
        $header = wcgs_get_sheet_info('products', 'header_data');
        
        if( !$header ){
            $header = get_option('wcgs_product_header');
        }
        
        return $header;
    }
    
    function get_value($key, $row) {
        
        return isset($row[$this->map[$key]]) ? $row[$this->map[$key]] : '';
    }
    
    // Chunking the GS Rows
    function get_chunks(){
        
        $chunk_size = wcgs_get_chunk_size();
        $gs = new WCGS_APIConnect();
        $range = 'products';
        $gs_rows = $gs->get_sheet_rows($range);
        
        $sync_col = wcgs_get_sheet_info('products','sync_col');
        if( ! $sync_col ) {
            return new WP_Error( 'gs_connection_error', __('Make you connect your sheet from Google Sync Menu','wcgs') );
        }
        
        // Setting mapping (index => $key)
        // $this->set_mapping($gs_rows[0]);
        
        unset($gs_rows[0]);    // Skip heading row
        
        $sync_col_index = wcgs_header_letter_to_index($sync_col);
        
        $syncable_filter = array_filter($gs_rows, function($r) use($sync_col_index){
          return $r[$sync_col_index] != WCGS_SYNC_OK;
        });
        
        if( !$syncable_filter ) return null;
        $chunked_array = array_chunk($syncable_filter, $chunk_size, true);
        set_transient('wcgs_product_chunk', $chunked_array);
        
        $response = ['total_products'=>count($syncable_filter), 'chunks'=>count($chunked_array),'chunk_size'=>$chunk_size];
        
        return $response;
    }
    
    
    function get_data($rows){
        
        $gs = new WCGS_APIConnect();
        $range = 'products';
        
        $parse_Rows = array();
        $rowRef = array();
        // $rowIndex = 2;
        $wcgs_header = $this->get_header();
        foreach($rows as $rowIndex => $row){
            
            $rowIndex++;
            
            $row = $this->build_row_for_wc_api($row);
            $id   = isset($row['id']) ? $row['id'] : '';
            
            // Adding the meta key in new product to keep rowNo
            $row['meta_data'][] = ['key'=>'wcgs_row_id', 'value'=>$rowIndex];
            
            if( $id != '' ) {
                $parse_Rows['update'][$rowIndex] = $row;   
            }else{
                $parse_Rows['create'][$rowIndex] = $row;
            }
            
            $rowIndex++;
        }
        
        // wcgs_pa($parse_Rows);
        return $parse_Rows;
    }
    
    function build_row_for_wc_api($row, $header=null) {
        
        $data = array();
        
        $header = is_null($header) ? $this->get_header() : $header;
        
        foreach($header as $key => $index) {
            
            // wcgs_log($key." => ".$row[$index]);
            if( empty($row[$index])  ) continue;
            
            $value = $row[$index];
            
            // getting the datatype
            $data_type = wcgs_get_datatype_by_keys('products', $key);
            switch($data_type) {
                
                case 'object':
                case 'array':
                    $value = json_decode($value, true);
                    break;
            }
            
            
            $data[ trim($key) ] = apply_filters("wcgs_products_data_{$key}", $value, $row);
        }
        return $data;
    }
    
    
    // Sync all categories from GS to Site
    function sync($rows) {
        
        // Get Data from Google Sheet
        $products = $this->get_data($rows);
        // wcgs_pa($products); exit;
        
        if( ! $products ) return ['no_sync'=>true];
       
        $wcapi = new WCGS_WC_API();
        $googleSheetRows = $wcapi->update_products_batch($products, $rows);
        // wcgs_pa($googleSheetRows); exit;
        
        
        // Get the Range Value for last_sync column
        $header_values = $this->get_header();
        // wcgs_pa($header_values); exit;
        $last_sync_index = $header_values['last_sync'];
        $last_sync_cell = wcgs_get_header_column_by_index($last_sync_index);
        
        // Now getting the ID from newly created product and update Google Sheeet row
        $gs = new WCGS_APIConnect();
        
        // If Client is authrized
        $sync_result = '';
        if ( ! $gs->auth_link ) {
            
            $sync_result = $gs->update_rows('products', $googleSheetRows, $last_sync_cell);
            do_action('wcgs_after_products_synced', $googleSheetRows, 'products', $sync_result);
            // return $result;
        }
        
        $error_message = array();
        if ( false !== ( $batch_update_error = get_transient( 'wcgs_batch_error' ) ) ) {
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
            $request = new WP_REST_Request( 'PUT', '/wc/v3/products/'.$data['id'] );    
        } else {
            $request = new WP_REST_Request( 'POST', '/wc/v3/products' );
        }
        
        $request->set_body_params( $data );
        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            $error = $response->as_error();
            return new WP_Error( 'wc_api_error', $error->get_error_message() );
        } else{
            $response = $response->get_data();
        }
         
         do_action('wcgs_after_product_updated', $response, $data);
        //   wcgs_pa($response);
         return $response;
    }   
}