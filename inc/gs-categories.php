<?php
/**
 * Google Sheet Categories Controller
 * 
 * */

class WCGS_Categories {
    
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
        
        update_option('wcgs_category_header', $this->map);
        
        // wcgs_pa($this->map);
    }
    
    function get_header() {
        
        $header = get_option('wcgs_category_header');
        // wcgs_log($header);
        return $header;
    }
    
    function get_value($column, $row) {
        
        return isset($row[$this->map[$column]]) ? $row[$this->map[$column]] : '';
    }
    
    
    function get_data(){
        
        $gs = new WCGS_APIConnect();
        $range = 'categories';
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
            $id   = isset($row['id']) ? $row['id'] : '';
            $name = isset($row['name']) ? sanitize_key($row['name']) : '';
            $sync = isset($row['sync']) ? $row['sync'] : '';
            
            $batch_data = array();
            if( $id != '' ) {
                $parse_Rows['update'][] = $row;   
                $rowRef[$id] = $rowIndex;
            }else{
                $parse_Rows['create'][] = $row;
                $rowRef[$name] = $rowIndex;
            }
            
            $rowIndex++;
            
        }
        
        // wcgs_pa($parse_Rows); exit;
        $this->rowRef = $rowRef;
        return $parse_Rows;
    }
    
    function build_row_for_wc_api($row) {
        
        $data = array();
        foreach($this->get_header() as $key => $index) {
            
            if( empty($row[$index])  ) continue;
            
            $value = $row[$index];
            
            // getting the datatype
            $data_type = wcgs_get_datatype_by_keys('categories', $key);
            switch($data_type) {
                
                case 'object':
                case 'array':
                    $value = json_decode($value, true);
                    break;
            }
            
            // var_dump($key, $row[$index]);
            $data[ trim($key) ] = apply_filters("wcgs_categories_data_{$key}", $value, $row);
        }
        return $data;
        
    }
    
    // Sync all categories from GS to Site
    function sync() {
        
        $categories = $this->get_data();
        
        if( ! $categories ) return ['no_sync'=>true];
       
        $wcapi = new WCGS_WC_API();
        $googleSheetRows = $wcapi->update_categories_batch($categories, $this->rowRef);
        
        // Get the Range Value for last_sync column
        $header_values = $this->get_header();
        $last_sync_index = $header_values['last_sync'];
        $last_sync_cell = wcgs_get_header_column_by_index($last_sync_index);
        
        $gs = new WCGS_APIConnect();
        
        // If Client is authrized
        $sync_result = '';
        if ( ! $gs->auth_link ) {
            
            $sync_result = $gs->update_rows('categories', $googleSheetRows, $last_sync_cell);
            do_action('wcgs_after_categories_synced', $googleSheetRows, 'categories', $sync_result);
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
    function wc_update_category($data) {
     
        // Check if id exists
        if( isset($data['id']) ) {
            $request = new WP_REST_Request( 'PUT', '/wc/v3/products/categories/'.$data['id'] );    
        } else {
            $request = new WP_REST_Request( 'POST', '/wc/v3/products/categories' );
        }
        
        $request->set_body_params( $data );
        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            $error = $response->as_error();
            return new WP_Error( 'wc_api_error', $error->get_error_message() );
        } else{
            $response = $response->get_data();
        }
         
         do_action('wcgs_after_category_updated', $response, $data);
        //   wcgs_pa($response);
         return $response;
    }   
}