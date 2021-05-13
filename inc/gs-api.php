<?php
/*
** Google Sheet API Class
**/

require WCGS_PATH . '/lib/vendor/autoload.php';

class WCGS_APIConnect {
    
    function __construct() {
        
        // Get the API client and construct the service object.
        // $service = new Google_Service_Sheets($client);
        
        $debug = false;
        if( $debug ) {
            delete_option('wcgs_token');
        }
        
        $gs_sheet_id = wcgs_get_option('wcgs_googlesheet_id');
        $this->auth_link = '';
        $this->need_auth = false;
        
        if( $this->getClient() !== null ) {
            $this->client = $this->getClient();
        }else{
            delete_option('wcgs_token');
        }
            
        $this->sheet_id = $gs_sheet_id;
        // $this->sheet_id = '17uEHwuto1CfmXC9J0GMqPkZXtaCga7UCIaVxgAiZihs'; // NKB Products
        // $this->sheet_id = '1sA55ZG3uo8JLr8eKyDkim0B2QcC1OtVVr26zufW0Fwo'; // Example GS
        // $this->sheet_id = '17uEHwuto1CfmXC9J0GMqPkZXtaCga7UCIaVxgAiZihs'; // NKB Product
    }
    
    private function save_token($token){
        update_option('wcgs_token', $token);
    }
    
    private function get_token(){
        $token = get_option('wcgs_token');
        return $token;
    }
    
    function getClient($authCode=null)
    {
        try{
            
            $client = new Google_Client();
            $client->setApplicationName('NKB Product');
            // FullAccess
            $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
            
            $gs_credentials = wcgs_get_option('wcgs_google_credential');
            $gs_credentials = json_decode($gs_credentials, true);
            $gs_redirect_uri = wcgs_get_option('wcgs_redirect_url');
            
            $client->setAuthConfig( $gs_credentials );
            $client->setAccessType ("offline");
            $client->setApprovalPrompt ("force");
            $client->setRedirectUri($gs_redirect_uri);
            // $client->setPrompt('select_account consent');
        
            // Load previously authorized token from a file, if it exists.
            // The file token.json stores the user's access and refresh tokens, and is
            // created automatically when the authorization flow completes for the first
            // time.
            if ($token = $this->get_token()) {
                $accessToken = json_decode($token, true);
                $client->setAccessToken($accessToken);
            }
        
            // If there is no previous token or it's expired.
            if ($client->isAccessTokenExpired()) {
                // Refresh the token if possible, else fetch a new one.
                // var_dump($client->getRefreshToken());
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                } else {
                    // Request authorization from the user.
                    
                    if( $authCode ) {
                        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                        $client->setAccessToken($accessToken);
                        
                        // Check to see if there was an error.
                        if (array_key_exists('error', $accessToken)) {
                            throw new Exception(join(', ', $accessToken));
                        }
                        
                        $this->save_token( json_encode($accessToken) );
                    } else {
                        $authUrl = $client->createAuthUrl();
                        $this->auth_link = $authUrl;
                    }
                }
            }
            
            delete_transient("wcgs_client_error_notices");
            return $client;
        }catch (\Exception $e)
        {
            wcgs_pa(json_decode($e->getMessage(), true));
            set_transient("wcgs_client_error_notices", $this->parse_message($e), 30);
        }
    }
    
    function setSheetInfo() {
        
        $service = new Google_Service_Sheets($this->client);

        // Prints the names and majors of students in a sample spreadsheet:
        // https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
        // $spreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
        $spreadsheet_info = $service->spreadsheets->get($this->sheet_id);
        
        $gs_info = array();
        foreach ($spreadsheet_info as $item) {
            $sheet_id = $item['properties']['sheetId'];
            $sheet_title = $item['properties']['title'];
            $gs_info[$sheet_id] = $sheet_title;
        }
        
        update_option('wcgs_sheets_info', $gs_info);
    }
    
    
    // Get sheet values
    function get_sheet_rows($range) {
        
        try{
            
            $service = new Google_Service_Sheets($this->client);
    
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
            
            $service = new Google_Service_Sheets($this->client);
            // Create the value range Object
            $valueRange= new Google_Service_Sheets_ValueRange();
            
            // You need to specify the values you insert
            $valueRange->setValues(["values" => $row]); // Add two values
            $range = "{$sheet_name}";
            
            // Then you need to add some configuration
            $conf = ["valueInputOption" => "RAW"];
            
            // Update the spreadsheet
            $result = $service->spreadsheets_values->append($this->sheet_id, $range, $valueRange, $conf);
            // wcgs_log($result);
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
            
            $service = new Google_Service_Sheets($this->client);
            
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
        
        try{
            var_dump($this->client);
            $service = new Google_Service_Sheets($this->client);
            
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
                'valueInputOption' => "RAW",
                'data' => $data
            ]);
            $result = $service->spreadsheets_values->batchUpdate($this->sheet_id, $body);
            do_action('wcgs_after_rows_updated', $result, $ranges_value);
        }
        catch (\Exception $e)
        {
            wcgs_log($e);
            set_transient("wcgs_admin_notices", $this->parse_message($e), 30);
        }
        
        return $result;
    }
    
    // Delete Single Row
    function delete_row($sheetId, $rowNo) {
        
        try {
        
            $service = new Google_Service_Sheets($this->client);
            
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
            
            $service = new Google_Service_Sheets($this->client);
            
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
            // wcgs_pa($data); exit;
            
            
            // $result = $service->spreadsheets_values->batchUpdate($this->sheet_id, $body);
            $result = $service->spreadsheets_values->append($this->sheet_id, $range, $data, $conf);
            return $result;
        }
        catch (\Exception $e)
        {
            set_transient("wcgs_admin_notices", $this->parse_message($e), 30);
        }
        
        return $result;
    }
    
    
    //parse the error message
    function parse_message($e) {
        
        $object = json_decode($e->getMessage(), true);
        $result['message'] = isset($object['error']['message']) ? "Google Sheet API Error: ".$object['error']['message'] : '';
        $result['class'] = 'error';
        return $result;
    }
}