<?php
/**
 * Google Sheets Controller
 * 
 * Saving sheet data/info in option key wcgs_{sheetname}_info
 * ==> keys: sheet_name, sync_col, header_data
 * 
 * */

class WCGS_Sheet2 {
    
    static $sheet_id;
    static $chunk_size;
    static $header;
    function __construct($id) {
        
        self::$sheet_id = $id;    
        self::$chunk_size = wcgs_get_chunk_size();    
    }
    
    function create_chunk($sheet){
        
        $service = WCGS_APIConnect::getSheetService();
        if( is_wp_error($service) ) {
            return new WP_Error( 'gs_client_error', $service->get_error_message() );
        }
        
        $range = $sheet;
        
        $response = $service->spreadsheets_values->get(self::$sheet_id, $range);
        $gs_rows = $response->getValues();
        
        self::$header = $gs_rows[0];  // setting header
        unset($gs_rows[0]);    // Skip heading row
        
        $sync_col_index = array_search('sync', self::$header);
        if( ! $sync_col_index ) {
            return new WP_Error( 'gs_heading_error', __('Make sure sheet has correct header format','wcgs') );
        }
        
        $syncable_filter = array_filter($gs_rows, function($r) use($sync_col_index){
          return $r[$sync_col_index] != WCGS_SYNC_OK;
        });
        
        // if( !$syncable_filter ) return null;
        $chunked_array = array_chunk($syncable_filter, self::$chunk_size, true);
        set_transient('wcgs_product_chunk', $chunked_array);
        set_transient('wcgs_product_header', self::$header);
        set_transient('wcgs_product_sync_col_index', $sync_col_index);
        
        $response = ['total_products'=>count($syncable_filter), 'chunks'=>count($chunked_array),'chunk_size'=>self::$chunk_size];
        
        return $response;
    }
    
    function sync($sheet) {
        
        $saved_chunked  = get_transient('wcgs_product_chunk');
        $header  = get_transient('wcgs_product_header');
        $sync_col_index = get_transient('wcgs_product_sync_col_index');
        // wcgs_log($saved_chunked);
        
        // remove sync column from header
        array_pop($header);
        
        if( $chunk === null ) 
            $chunk = isset($_POST['chunk']) ? $_POST['chunk'] : '';
            
        $response = array();
        if( !isset($saved_chunked[$chunk]) ) {
            $response['status'] = 'error';
            $response['message'] = __("No chunk found to sync","wcgs");
            return $send_json ? wp_send_json($response) : $response;
        }
        
        $chunked_rows = array_map(function($row) use($sync_col_index){
            return array_pad($row, $sync_col_index, "");
        }, $saved_chunked[$chunk]);
        
        $combined_arr = array_map(function($row) use ($header) {
                                        return array_combine($header, $row);
                                    }, 
                                    $chunked_rows);
        
        /**
         * Defined: class.formats.php
         * 1. formatting each column data with wcgs_{$sheet}_data_{$key}
         * 2. Setting meta_data key for the product
         * 3. product meta columns handling
         **/
        $combined_arr = apply_filters('wcgs_sync_data_products_before_processing', $combined_arr, $sheet);
        
        
        
        $variations = array_filter($combined_arr, function($row){
            return $row['type'] == 'variation' && ! empty($row['parent_id']);
        });
        
        $without_variations = array_filter($combined_arr, function($row){
            return $row['type'] != 'variation';
        });
        
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
        wcgs_log($both_res);
        
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
        
        
        $id_col = 'A';
        $sync_col = $sheet_info['sync_col'];
        $images_col = isset($sheet_info['images_col']) ? $sheet_info['images_col'] : null;
        $image_col = isset($sheet_info['image_col']) ? $sheet_info['image_col'] : null;
        
        $updatable_range = [];
        foreach($rows_ok as $row){
            $updatable_range["{$sheet}!{$id_col}{$row['row']}"] = [$row['id']];
            $updatable_range["{$sheet}!{$sync_col}{$row['row']}"] = ['OK'];
            if( $images_col && isset($row['images']) ){
                $updatable_range["{$sheet}!{$images_col}{$row['row']}"] = [$row['images']];
            }
            if( $image_col && isset($row['image']) ){
                $updatable_range["{$sheet}!{$image_col}{$row['row']}"] = [$row['image']];
            }
            
        }
        
        
        wcgs_log($updatable_range);
        return $wcapi_data;
        // wp_send_json($saved_chunked[$chunk]);
    }
    
}