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
        
    //   $category_header = [
    //                     'id','sync','name','slug','parent','description','display','image','menu_order'
    //       ];
       foreach($header as $order => $key ) {
            $key = trim($key);
            $this->map[$key] = $order;
        }
        
        update_option('wcgs_category_header', $this->map);
        
        // wcgs_pa($this->map);
    }
    
    function get_header() {
        
        $header = get_option('wcgs_category_header');
        return $header;
    }
    
    function get_value($column, $row) {
        
        return isset($row[$this->map[$column]]) ? $row[$this->map[$column]] : '';
    }
    
    
    function get_data(){
        
        $gs = new GoogleSheet_API();
        $range = 'categories';
        $this->rows = $gs->get_sheet_rows($range);
        
        // Setting mapping (index => $key)
        $this->set_mapping($this->rows[0]);
        
        unset($this->rows[0]);    // Skip heading row
        $parse_Rows = array();
        $rowRef = array();
        $rowIndex = 2;
        foreach($this->rows as $row){
            
            $row = $this->build_row_for_wc_api($row);
            $id   = isset($row['id']) ? $row['id'] : '';
            $name = isset($row['name']) ? sanitize_key($row['name']) : '';
            $sync = isset($row['sync']) ? $row['sync'] : '';
            
           
            if( $sync == 1 ) {
                $rowIndex++;
                continue;
            }
            
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
        
        // wcgs_pa($parse_Rows);
        $this->rowRef = $rowRef;
        return $parse_Rows;
    }
    
    function build_row_for_wc_api($row) {
        
        $data = array();
        foreach($this->map as $key => $index) {
            
            $data[ trim($key) ] = $row[$index];
        }
        return $data;
        
    }
    
    // Sync all categories from GS to Site
    function sync() {
        
        $categories = $this->get_data();
        
        if( ! $categories ) return ['no_sync'=>true];
       
        $wcapi = new WCGS_WC_API();
        $googleSheetRows = $wcapi->update_categories_batch($categories, $this->rowRef, $this->rows);
        
        $gs = new GoogleSheet_API();
        
        // If Client is authrized
        $sync_result = '';
        if ( ! $gs->auth_link ) {
            
            $sync_result = $gs->update_rows('categories', $googleSheetRows);
            do_action('wcgs_after_categories_synced', $googleSheetRows, 'categories', $sync_result);
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