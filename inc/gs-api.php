<?php
/*
** Google Sheet API Class
**/

require WCGS_PATH . '/lib/googleclient/vendor/autoload.php';

class WCGS_APIConnect {
    
    static $cred_file;
    function __construct() {
        
        // Get the API client and construct the service object.
        // $service = new Google_Service_Sheets($client);
        
        self::$cred_file = WCGS_PATH.'/quickconnect/service.json';
        
        $debug = false;
        if( $debug ) {
            delete_option('wcgs_service_connect');
        }
        
        $gs_sheet_id = wcgs_get_option('wcgs_googlesheet_id');
        
        $this->sheet_id = $gs_sheet_id;
        
    }
    
    function getSheetInfo() {
        
        try{
            
            $service = $this->get_google_service();

            $spreadsheet_info = $service->spreadsheets->get($this->sheet_id);
            
            $gs_info = array();
            foreach ($spreadsheet_info as $item) {
                $sheet_id = $item['properties']['sheetId'];
                $sheet_title = $item['properties']['title'];
                $gs_info[$sheet_id] = $sheet_title;
            }
        
        update_option('wcgs_sheets_info', $gs_info);
        return $gs_info;
            
        }catch (\Exception $e)
        {
            // wcgs_pa($e);
            $err = self::parse_message($e);
            return new WP_Error( 'gs_connection_error', $err['message'] );
        }
        
    }
    
    
    // Get sheet values
    function get_sheet_rows($range) {
        
        try{
            
            $service = $this->get_google_service();
    
            $response = $service->spreadsheets_values->get($this->sheet_id, $range);
            $values = $response->getValues();
            return $values;
        }
        catch (\Exception $e)
        {
            // wcgs_pa($e);
            set_transient("wcgs_admin_notices", $this->parse_message($e), 30);
        }
    }
    
    
    function add_row($sheet_name, $row) {
        
        try{
            
            $service = $this->get_google_service();
            // Create the value range Object
            $valueRange= new Google_Service_Sheets_ValueRange();
            
            // You need to specify the values you insert
            $valueRange->setValues(["values" => $row]); // Add two values
            $range = "{$sheet_name}";
            
            // Then you need to add some configuration
            $conf = ["valueInputOption" => "RAW"];
            
            // Update the spreadsheet
            $result = $service->spreadsheets_values->append($this->sheet_id, $range, $valueRange, $conf);
            return $result->getUpdates()->getUpdatedRange();
        }
        catch (\Exception $e)
        {
            // wcgs_pa($e);
            set_transient("wcgs_admin_notices", $this->parse_message($e), 30);
        }
        
    }
    
    function update_rows($sheet_name, $Rows, $last_sync=null) {
        
        try {
            
            $service = $this->get_google_service();
            
            $end = count($Rows)+1;
            global $wpdb;
            
            $values = [];
            $data = [];
            foreach($Rows as $row_no=>$value){
                $range = "{$sheet_name}!A{$row_no}:B{$row_no}";
                $values[] = $value;
                
                $data[] = new Google_Service_Sheets_ValueRange([
                    'range' => $range,
                    'values' => [$value]
                ]);
            }
            
            // Last sync value
            if( $last_sync ) {
                $data[] = new Google_Service_Sheets_ValueRange([
                    'range' => "{$sheet_name}!{$last_sync}{$row_no}",
                    'values' => [ [wcgs_get_last_sync_date()] ]
                ]);
            }
            
            // wcgs_pa($data); exit;
            // Additional ranges to update ...
            $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
                'valueInputOption' => "RAW",
                'data' => $data
            ]);
            $result = $service->spreadsheets_values->batchUpdate($this->sheet_id, $body);
            return $result;
        }
        catch (\Exception $e)
        {
            // wcgs_pa($e);
            set_transient("wcgs_admin_notices", $this->parse_message($e), 30);
        }
        
        // wcgs_pa($result);
    }
    
    // Update Rows with defined ranges
    function update_rows_with_ranges($ranges_value, $row=NULL) {
        
        $result = [];
        
        try{
            
            $service = $this->get_google_service();
            
            foreach($ranges_value as $range => $value) {
                
                $data[] = new Google_Service_Sheets_ValueRange([
                    'range' => $range,
                    'values' => [$value],
                    // 'majorDimension' => 'COLUMNS',
                ]);
            }
            
            // wcgs_log($data);
            // Additional ranges to update ...
            $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
                'valueInputOption' => "USER_ENTERED",
                'data' => $data
            ]);
            $result = $service->spreadsheets_values->batchUpdate($this->sheet_id, $body);
            do_action('wcgs_after_rows_updated', $result, $ranges_value);
        }
        catch (\Exception $e)
        {   
            // wcgs_log($this->parse_message($e));
            $err = $this->parse_message($e);
            $msg = $err['message']." Make sure you have used same email account for GoogleSheet as you use for App Connect";
            return new WP_Error( 'gs_connection_error', $msg );
        }
        
        // wcgs_log($result);
        return $result;
    }
    
    // Delete Single Row
    function delete_row($sheetId, $rowNo) {
        
        try {
        
            $service = $this->get_google_service();
            
            $start = intval($rowNo)-1;
            $end   = $start+1;
            $deleteOperation = array(
                                'range' => array(
                                    'sheetId'   => $sheetId, // <======= This mean the very first sheet on worksheet
                                    'dimension' => 'ROWS',
                                    'startIndex'=> $start, //Identify the starting point,
                                    'endIndex'  => ($end) //Identify where to stop when deleting
                                )
                            );
            $deletable_row[] = new Google_Service_Sheets_Request(
                                    array('deleteDimension' =>  $deleteOperation)
                                );
                                
            $body    = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                            'requests' => $deletable_row
                        )
                    );
            // wcgs_pa($body);
            $result = $service->spreadsheets->batchUpdate($this->sheet_id, $body);
            return $result;
        }
        catch (\Exception $e)
        {
            // wcgs_pa($e);
            set_transient("wcgs_admin_notices", $this->parse_message($e), 30);
        }
    }
    
    // Add new rows/products (sync-back)
    function add_new_rows($sheet_name, $Rows) {
        
        try {
            
            $service = $this->get_google_service();
            
            $end = count($Rows)+1;
            global $wpdb;
            
            $values = [];
            $data = [];
            $range = "{$sheet_name}!A1";
            foreach($Rows as $key=>$value){
                // $range = "{$sheet_name}!A{$key}:B{$key}";
                
                $values[] = $value;
                
            }
            
            $data = new Google_Service_Sheets_ValueRange([
                    'range' => $range,
                    'values' => $values,
                    "majorDimension"=>"ROWS"
                ]);
            
            // Then you need to add some configuration
            $conf = ["valueInputOption" => "RAW"];
            // wcgs_pa($data);
            
            
            // $result = $service->spreadsheets_values->batchUpdate($this->sheet_id, $body);
            $result = $service->spreadsheets_values->append($this->sheet_id, $range, $data, $conf);
            return $result;
        }
        catch (\Exception $e)
        {   
            wcgs_log($this->parse_message($e));
            set_transient("wcgs_admin_notices", $this->parse_message($e), 30);
        }
        
        return $result;
    }
    
    static function get_google_client(){
        
        
        if( ! class_exists('Google_Client') ) {
            $err['error']['message'] = __('Google Library not found, please re-install plugin or disable any other plugin using Google Services.', 'wcgs');
            throw new Exception(json_encode($err));
        }
        return new Google_Client();
    }
    
    function get_google_service(){
        
        try{
            
            $client = self::get_google_client();
            $client->setApplicationName('GoogleSync-WooCommerce');
            // FullAccess
            $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
            $client->setAuthConfig( self::$cred_file );
            return new Google_Service_Sheets($client);
        }catch (\Exception $e)
        {
            $err = self::parse_message($e);
            return new WP_Error( 'gs_client_error', $err['message'] );
        }
    }
    
    
    //parse the error message
    function parse_message($e) {
        
        $object = json_decode($e->getMessage(), true);
        $result['message'] = isset($object['error']['message']) ? "Google Sheet API Error: ".$object['error']['message'] : '';
        $result['class'] = 'error';
        return $result;
    }
    
}