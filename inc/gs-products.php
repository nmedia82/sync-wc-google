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
        
        $header = get_option('wcgs_product_header');
        return $header;
    }
    
    function get_value($key, $row) {
        
        return isset($row[$this->map[$key]]) ? $row[$this->map[$key]] : '';
    }
    
    // Chunking the GS Rows
    function get_chunks(){
        
        $wcgs_chunks = 5;
        $gs = new WCGS_APIConnect();
        $range = 'products';
        $gs_rows = $gs->get_sheet_rows($range);
        
        // Setting mapping (index => $key)
        $this->set_mapping($gs_rows[0]);
        
        unset($gs_rows[0]);    // Skip heading row
        
        $syncable_filter = array_filter($gs_rows, function($r){
          return $r[WCGS_SYNC_COLUMN_INDEX] != 1;
        });
        
        // wcgs_pa($filters);
        if( !$syncable_filter ) return null;
        $chunked_array = array_chunk($syncable_filter, $wcgs_chunks, true);
        set_transient('wcgs_product_chunk', $chunked_array);
        
        $response = ['total_products'=>count($syncable_filter), 'chunks'=>count($chunked_array),'chunk_size'=>$wcgs_chunks];
        
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
            
            $row = $this->build_row_for_wc_api($row, $wcgs_header);
            $id   = isset($row['id']) ? $row['id'] : '';
            
            // Adding the meta key in new product to keep rowNo
            $row['meta_data'] = [['key'=>'wcgs_row_id', 'value'=>$rowIndex]];
            
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
    
    function build_row_for_wc_api($row, $wcgs_header) {
        
        $data = array();
        foreach($wcgs_header as $key => $index) {
            
            if( ! isset($row[$index]) ) continue;
            
            // var_dump($key, $row[$index]);
            $data[ trim($key) ] = apply_filters("wcgs_row_data_{$key}", $row[$index], $row);
        }
        return $data;
    }
    
    
    // Sync all categories from GS to Site
    function sync($rows) {
        
        // Get Data from Google Sheet
        // wcgs_pa($rows); exit;
        $products = $this->get_data($rows);
        
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
}