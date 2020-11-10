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
            
            $row = $this->build_row_for_wc_api($row);
            
            // If no product attached, no use
            if( ! isset($row['product_id']) ) continue;
            $product_id = $row['product_id'];
            
            $id   = isset($row['id']) ? $row['id'] : '';
            $sync = isset($row['sync']) ? $row['sync'] : '';
            
            // Adding the meta key in new product to keep rowNo
            $row['meta_data'] = [['key'=>'wcgs_row_id', 'value'>$rowIndex]];
            
            if( $sync == 1 ) {
                $rowIndex++;
                continue;
            }
            
            unset($row['product_id']);
            unset($row['id']);
            
            if( $id != '' ) {
                $parse_Rows[$product_id]['update'][$rowIndex] = $row;   
            }else{
                $parse_Rows[$product_id]['create'][$rowIndex] = $row;
            }
            
            $rowIndex++;
            
        }
        
        // wcgs_pa($parse_Rows);
        return $parse_Rows;
    }
    
    function build_row_for_wc_api($row) {
        
        $data = array();
        foreach($this->map as $key => $index) {
            
            if( ! isset($row[$index]) ) continue;
            
            $data[ trim($key) ] = apply_filters("wcgs_row_data_{$key}", $row[$index], $row);
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
        wcgs_pa($googleSheetRows);
        
        // Now getting the ID from newly created product and update Google Sheeet row
        
        $gs = new WCGS_APIConnect();
        
        // If Client is authrized
        $sync_result = '';
        if ( ! $gs->auth_link ) {
            
            $sync_result = $gs->update_rows('variations', $googleSheetRows);
            do_action('wcgs_after_variations_synced', $googleSheetRows, 'variations', $sync_result);
            // return $result;
        }
        
        $error_message = array();
        if ( null !== ( $batch_update_error = get_transient( 'wcgs_batch_error' ) ) ) {
            $error_message = array('Batch_Errors' => $batch_update_error);
            delete_transient('wcgs_batch_error');
        }
        
        $response = ['sync_result'=>$sync_result, 'batch_errors'=>$error_message];
        
        return $response;
    }
}