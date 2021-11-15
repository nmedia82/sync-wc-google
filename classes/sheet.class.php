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
        
    }
    
    
    
    
    
}