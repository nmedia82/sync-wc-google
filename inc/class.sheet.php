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
                                    
        /**
         * Defined: class.formats.php
         * 1. formatting each column data with wcgs_{$sheet_name}_data_{$key}
         * 2. Setting meta_data key for the product
         * 3. product meta columns handling
         **/
        $combined_arr = apply_filters('wcgs_sync_data_products_before_processing', $combined_arr, $sheet_info);
        
        $variations = array_filter($combined_arr, function($row){
            return $row['type'] == 'variation' && ! empty($row['parent_id']);
        });
        
        $without_variations = array_filter($combined_arr, function($row){
            return $row['type'] != 'variation';
        });
        
        // wcgs_log($sheet_info);
                                    
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
        // wcgs_log($both_res);
        
        // FILTER ERRORS
        $errors = array_filter($both_res, function($a){
            return $a['row'] == 'ERROR';
        });
        
        // FILTER NON-ERRORS
        $rows_ok = array_filter($both_res, function($a){
            return $a['row'] != 'ERROR';
        });
        
        // building error msg string
        $err_msg = '';
        foreach($errors as $err){
            $err_msg .= '<p style="color:red">FAILED: '.$err['message'].' (Resource ID: '.$err['id'].')</p><hr>';
        }
        
        
        // Since version 3.2, updating google sheet back via PHP API
        $sheet_name = $sheet_info['sheet_name'];
        $id_col = 'A';
        $sync_col = $sheet_info['sync_col'];
        $images_col = isset($sheet_info['images_col']) ? $sheet_info['images_col'] : null;
        $image_col = isset($sheet_info['image_col']) ? $sheet_info['image_col'] : null;
        
        $updatable_range = [];
        foreach($rows_ok as $row){
            $updatable_range["{$sheet_name}!{$id_col}{$row['row']}"] = [$row['id']];
            $updatable_range["{$sheet_name}!{$sync_col}{$row['row']}"] = ['OK'];
            if( $images_col && isset($row['images']) ){
                $updatable_range["{$sheet_name}!{$images_col}{$row['row']}"] = [$row['images']];
            }
            if( $image_col && isset($row['image']) ){
                $updatable_range["{$sheet_name}!{$image_col}{$row['row']}"] = [$row['image']];
            }
            
        }
        
        
        // wcgs_log($gs);
        if( count($updatable_range) > 0 ) {
            $gs = new WCGS_APIConnect();
            $resp = $gs->update_rows_with_ranges($updatable_range);
            if( is_wp_error($resp) ) {
                return $resp;
            }
        }
        
        $resp = ['success_rows' => count($rows_ok),
                    'error_rows'    => count($errors),
                    'error_msg' => $err_msg
                    ];
        
        return $resp;
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
        
        // wcgs_log($result);
        // FILTER ERRORS
        $errors = array_filter($result, function($a){
            return $a['row'] == 'ERROR';
        });
        
        // FILTER NON-ERRORS
        $rows_ok = array_filter($result, function($a){
            return $a['row'] != 'ERROR';
        });
        
        // building error msg string
        $err_msg = '';
        foreach($errors as $err){
            $err_msg .= '<p style="color:red">FAILED: '.$err['message'].' (Resource ID: '.$err['id'].')</p><hr>';
        }
        
        // Since version 3.2, updating google sheet back via PHP API
        $sheet_name = $sheet_info['sheet_name'];
        $id_col = 'A';
        $sync_col = $sheet_info['sync_col'];
        $images_col = isset($sheet_info['images_col']) ? $sheet_info['images_col'] : null;
        
        $updatable_range = [];
        foreach($rows_ok as $row){
            $updatable_range["{$sheet_name}!{$id_col}{$row['row']}"] = [$row['id']];
            $updatable_range["{$sheet_name}!{$sync_col}{$row['row']}"] = ['OK'];
            if( $images_col ){
                $updatable_range["{$sheet_name}!{$images_col}{$row['row']}"] = [$row['images']];
            }
        }
        
        // wcgs_log($updatable_range);
        
        if( count($updatable_range) > 0 ) {
            $gs = new WCGS_APIConnect();
            $resp = $gs->update_rows_with_ranges($updatable_range);
        }
        
        $resp = ['success_rows' => count($rows_ok),
                    'error_rows'    => count($errors),
                    'error_msg' => $err_msg
                    ];
        
        return $resp;
    }
    
    public function update($sheet_info){
        
        $sheet_name = $sheet_info['sheet_name'];
        $debug_mode = $sheet_info['debug_mode'] == 'false' ? false : true;
        
        // Saving stock_quantity
        if(isset($sheet_info['stock_col']) ) {
            update_option('wcgs_stock_quantity_column', $sheet_info['stock_col']);
        }
        
        update_option("wcgs_{$sheet_name}_info", $sheet_info);
    }
    
}