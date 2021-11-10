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
    function __construct($sheet_name) {
        
        $this->sheet_name = $sheet_name;
        self::$sheet_id = wcgs_get_sheet_id();    
        self::$chunk_size = wcgs_get_chunk_size();    
    }
    
    function create_chunk(){
        
        $range = $this->sheet_name;
        $gs_rows = WCGS_APIConnect::get_sheet_rows(self::$sheet_id, $range);
        if( is_wp_error($gs_rows) ) {
            return new WP_Error( 'gs_client_error', $service->get_error_message() );
        }
        
        self::$header = $gs_rows[0];  // setting header
        unset($gs_rows[0]);    // Skip heading row
        
        $sync_col_index = array_search('sync', self::$header);
        if( ! $sync_col_index ) {
            return new WP_Error( 'gs_heading_error', __('Make sure sheet has correct header format','wcgs') );
        }
        
        // saving images, image col index as well
        $images_col_index = array_search('images', self::$header);
        $image_col_index = array_search('image', self::$header);
        
        // actually the the syncable rows are not equal == header index
        // so we will first fetch nonsyncabel then subtract (array_diff_key) from org data
        $nonsyncable_rows = array_filter($gs_rows, function($r) use($sync_col_index){
          return isset($r[$sync_col_index]) && $r[$sync_col_index] == WCGS_SYNC_OK;
        });
        
        $syncable_data = array_diff_key($gs_rows, $nonsyncable_rows);
        
        // if( !$syncable_data ) return null;
        $chunked_array = array_chunk($syncable_data, self::$chunk_size, true);
        
        $sync_transient = [ 'chunk'    => $chunked_array,
                            'header'   => self::$header,
                            'sync_col_index'   => $sync_col_index,
                            'images_col_index' => $images_col_index,
                            'image_col_index'  => $image_col_index
                            ];
                            
        wcgs_set_transient("sync_{$this->sheet_name}_transient", $sync_transient);
        
        $response = ['total_rows'=>count($syncable_data), 'chunks'=>count($chunked_array),'chunk_size'=>self::$chunk_size];
        
        return $response;
    }
    
    function sync() {
        
        $sync_transient = wcgs_get_transient("sync_{$this->sheet_name}_transient");
        $saved_chunked  = $sync_transient['chunk'];
        $header         = $sync_transient['header'];
        $sync_col_index = $sync_transient['sync_col_index'];
        $images_col_index = $sync_transient['images_col_index'];
        $image_col_index = $sync_transient['image_col_index'];
        
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
        
        
        // adjusting the syn column
        $chunked_rows = array_map(function($row) use($sync_col_index){
            return array_pad($row, $sync_col_index, "");
        }, $saved_chunked[$chunk]);
        
        // wcgs_pa($chunked_rows); exit;
        // headers => rows (associative)
        $combined_arr = array_map(function($row) use ($header) {
                                        return array_combine($header, $row);
                                    }, 
                                    $chunked_rows);
        
        $synced_data = [];
        switch( $this->sheet_name ) {
            
            case 'products':
                $synced_data = WCGS_Products::sync($combined_arr);
                break;
                
            case 'categories':
                $synced_data = WCGS_Categories::sync($combined_arr);
                break;
        }
        
        if( is_wp_error($synced_data) ) {
            return $synced_data;
        }
        
        // FILTER ERRORS
        $rows_error = array_filter($synced_data, function($a){
            return $a['row'] == 'ERROR';
        });
        
        // FILTER NON-ERRORS
        $rows_ok = array_filter($synced_data, function($a){
            return $a['row'] != 'ERROR';
        });
        
        // building error msg string
        $err_msg = '';
        foreach($rows_error as $err){
            $err_msg .= '<p style="color:red">FAILED: '.$err['message'].' (Resource ID: '.$err['id'].')</p><hr>';
        }
        
        // wcgs_log($err_msg);
        
        $id_col = 'A';
        $sync_col = wcgs_get_header_column_by_index($sync_col_index);
        $images_col = wcgs_get_header_column_by_index($images_col_index);
        $image_col = wcgs_get_header_column_by_index($image_col_index);
        
        $ranged_data = [];
        foreach($rows_ok as $row){
            $ranged_data["{$this->sheet_name}!{$id_col}{$row['row']}"] = [$row['id']];
            $ranged_data["{$this->sheet_name}!{$sync_col}{$row['row']}"] = ['OK'];
            if( $images_col && isset($row['images']) ){
                $ranged_data["{$this->sheet_name}!{$images_col}{$row['row']}"] = [$row['images']];
            }
            if( $image_col && isset($row['image']) ){
                $ranged_data["{$this->sheet_name}!{$image_col}{$row['row']}"] = [$row['image']];
            }
        }
        
        wcgs_log($ranged_data);
        if( count($ranged_data) > 0 ) {
            // $service = WCGS_APIConnect::getSheetService();
            $resp = WCGS_APIConnect::update_rows_with_ranges($ranged_data, self::$sheet_id);
            if( is_wp_error($resp) ) {
                return $resp;
            }
        }
        
        $resp = ['success_rows' => count($rows_ok),
                'error_rows'    => count($rows_error),
                'error_msg'     => $err_msg
                    ];
        
        return $resp;
        
    }
    
}