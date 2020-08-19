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
    
    
    function get_data(){
        
        $gs = new GoogleSheet_API();
        $range = 'products';
        $this->rows = $gs->get_sheet_rows($range);
        
        // Setting mapping (index => $key)
        $this->set_mapping($this->rows[0]);
        
        unset($this->rows[0]);    // Skip heading row
        $parse_Rows = array();
        $rowRef = array();
        $rowIndex = 2;
        foreach($this->rows as $row){
            
            $row = $this->build_row_for_wc_api($row);
            $id   = $row['id'];
            $name = $row['name'];
            $sync = $row['sync'];
            
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
            
            $data[ trim($key) ] = apply_filters("wcgs_row_data_{$key}", $row[$index], $row);
        }
        return $data;
        
    }
    // Sync all categories from GS to Site
    function sync() {
        
        // Get Data from Google Sheet
        $products = $this->get_data();
       
        $wcapi = new WCGS_WC_API();
        $googleSheetRows = $wcapi->update_products_batch($products, $this->rowRef, $this->rows);
        
        // Now getting the ID from newly created product and update Google Sheeet row
        // foreach($googleSheetRows)
        
        $gs = new GoogleSheet_API();
        
        // If Client is authrized
        if ( ! $gs->auth_link ) {
            
            $result = $gs->update_rows('products', $googleSheetRows);
            do_action('wcgs_after_products_synced', $googleSheetRows, 'products', $result);
            return $result;
        }
        
        return null;
    }
}