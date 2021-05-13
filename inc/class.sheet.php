<?php
/**
 * Google Sheets Controller
 * 
 * Saving sheet data/info in option key wcgs_{sheetname}_info
 * ==> keys: sheet_name, sync_col, header_data
 * 
 * */

class WCGS_Sheet {
    
    function __construct() {
        
        // No code
    }
    
    public function sync_data_products($sheet_info){
        
        if( ! isset($sheet_info['sheet_data']) ) return;
        $header_data = $sheet_info['header_data'];
        
        // Adding extra header for the row_id
        // The row already have extra item in last as row id
        $header_data[] = 'row_id_meta';
        $combined_arr = array_map(function($row) use ($header_data) {
                                        return array_combine($header_data, $row);
                                    }, 
                                    $sheet_info['sheet_data']);
                                    
        // Third party integration
        $combined_arr = apply_filters('wcgs_sync_data_products_before_processing', $combined_arr, $sheet_info);
        
        $variations = array_filter($combined_arr, function($row){
            return $row['type'] == 'variation' && ! empty($row['parent_id']);
        });
        
        $without_variations = array_filter($combined_arr, function($row){
            return $row['type'] != 'variation';
        });
        
        // wcgs_log($combined_arr); exit;
                                    
        // Preparing data for WC API
        $wcapi_data = [];
        // Existing data
        $wcapi_data['update'] = array_filter($without_variations, function($row){
                        return $row['id'] != '';
            });
        // New data
        $wcapi_data['create'] = array_filter($without_variations, function($row){
                        return $row['id'] == '';
            });
            
            
        // Handling Variations
        // Preparing variations data for WC API
        $wcapi_variations = [];
        foreach($variations as $variation){
            
            $id = $variation['id'];
            $parent_id = $variation['parent_id'];
            
            if( $id != '' ) {
                $wcapi_variations[$parent_id]['update'][] = $variation;   
            }else{
                unset($variation['id']);
                $wcapi_variations[$parent_id]['create'][] = $variation;
            }
        }
        
        $result1 = $result2 = [];        
    
        // wcgs_log($wcapi_data); exit;
        $wcapi_v3 = new WCGS_WC_API_V3();
        
        if($wcapi_data) {
            $result1 = $wcapi_v3->batch_update_products($wcapi_data);
            if( is_wp_error($result1) ) {
                wp_send_json_error($result1->get_error_message());
            }
        }
        
        if($wcapi_variations) {
            $result2 = $wcapi_v3->batch_update_variations($wcapi_variations);
            if( is_wp_error($result2) ) {
                wp_send_json_error($result2->get_error_message());
            }
        }
        
        $both_res = array_merge($result1, $result2);
        
        // wcgs_log($result1);
        // wcgs_log($result2);
        // wcgs_log($both_res);
        
        wp_send_json_success($both_res);
    }
    
    // Categories sync
    public function sync_data_categories($sheet_info){
        
        if( ! isset($sheet_info['sheet_data']) ) return;
        $header_data = $sheet_info['header_data'];
        
        // Adding extra header for the row_id
        // The row already have extra item in last as row id
        $header_data[] = 'row_id_meta';
        $combined_arr = array_map(function($row) use ($header_data) {
                                        return array_combine($header_data, $row);
                                    }, 
                                    $sheet_info['sheet_data']);
                                    
        // Third party integration
        $combined_arr = apply_filters('wcgs_sync_data_categories_before_processing', $combined_arr, $sheet_info);
        
        // wcgs_log($combined_arr); exit;
        
        // Saving category name/id and row
        $rowRef = array();
                                    
        // Preparing data for WC API
        $wcapi_data = [];
        
        foreach($combined_arr as $row) {
            
            $id   = isset($row['id']) ? $row['id'] : '';
            $name = isset($row['name']) ? sanitize_key($row['name']) : '';
            
            if( $id != '' ) {
                $wcapi_data['update'][] = $row;   
                $rowRef[$id] = $row['row_id_meta'];
            }else{
                $wcapi_data['create'][] = $row;
                $rowRef[$name] = $row['row_id_meta'];
            }
        }
            
        $wcapi_v3 = new WCGS_WC_API_V3();
        $result = $wcapi_v3->batch_update_categories($wcapi_data, $rowRef);
        if( is_wp_error($result) ) {
            wp_send_json_error($result->get_error_message());
        }
        
        return $result;
    }
    
    public function update($sheet_info){
        
        $sheet_name = $sheet_info['sheet_name'];
        $debug_mode = $sheet_info['debug_mode'] == 'false' ? false : true;
        
        // Saving stock_quantity
        if(isset($sheet_info['stock_col']) ) {
            update_option('wcgs_stock_quantity_column', $sheet_info['stock_col']);
        }
        
        $option = get_option("wcgs_{$sheet_name}_info");
        if( ! $option ) {
            update_option("wcgs_{$sheet_name}_info", $sheet_info);
        }else{
            //Check if header changes found
            if( $sheet_info['header_data'] !== $option['header_data'] && !$debug_mode ) {
                wp_send_json_success(['message'=>__('Header columns are changed, it may cause issue while syncing')]);
            }else{
                update_option("wcgs_{$sheet_name}_info", $sheet_info);
                wp_send_json_success(['message'=>__('Sheet connected successfully')]);
            }
            // wcgs_log($sheet_info['header_data']);
        }
        
    }
    
}