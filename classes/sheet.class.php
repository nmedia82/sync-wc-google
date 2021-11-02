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
        set_transient('wcgs_product_sync_col_index', $sync_col_index);
        
        $response = ['total_products'=>count($syncable_filter), 'chunks'=>count($chunked_array),'chunk_size'=>self::$chunk_size];
        
        return $response;
    }
    
}