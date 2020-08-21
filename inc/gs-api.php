<?php
/*
** Google Sheet API Class
**/

require WCGS_PATH . '/lib/vendor/autoload.php';

class GoogleSheet_API {
    
    function __construct() {
        
        // Get the API client and construct the service object.
        // $service = new Google_Service_Sheets($client);
        
        $debug = false;
        if( $debug ) {
            delete_option('wcgs_token');
        }
        
        
        $this->token_path = WCGS_PATH.'/token.js';
        $this->auth_link = '';
        $this->client = $this->getClient();
        // $sheet_id = '17uEHwuto1CfmXC9J0GMqPkZXtaCga7UCIaVxgAiZihs'; // NKB Products
        $this->sheet_id = '1sA55ZG3uo8JLr8eKyDkim0B2QcC1OtVVr26zufW0Fwo'; // Example GS
        // $this->sheet_id = '17uEHwuto1CfmXC9J0GMqPkZXtaCga7UCIaVxgAiZihs'; // NKB Product
    }
    
    private function save_token($token){
        update_option('wcgs_token', $token);
    }
    
    private function get_token(){
        $token = get_option('wcgs_token');
        return $token;
    }
    
    function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('NKB Product');
        // FullAccess
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        // $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $client->setAuthConfig(WCGS_PATH.'/gs-cred.json');
        $client->setAccessType('offline');
        $client->setRedirectUri('https://nmdevteam.com/ppom/wp-json/nkb/v1/auth');
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
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                
                $this->auth_link = $authUrl;
            }
        }
        
        return $client;
    }
    
    function setAuthCode($authCode) {
        
        $client = new Google_Client();
        $client->setApplicationName('NKB Online');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $client->setAuthConfig(WCGS_PATH.'/gs-cred.json');
        $client->setAccessType('offline');
        $client->setRedirectUri('https://nmdevteam.com/ppom/wp-json/nkb/v1/auth');
        // $client->setPrompt('select_account consent');
        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $client->setAccessToken($accessToken);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }
        
        // Save the token to a file.
        // if (!file_exists(dirname($this->token_path))) {
        //     mkdir(dirname($this->token_path), 0700, true);
        // }
        // file_put_contents($this->token_path, json_encode($client->getAccessToken()));
        $this->save_token( json_encode($accessToken) ); 
        // $this->setSheetInfo();
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
        
        $service = new Google_Service_Sheets($this->client);

        $response = $service->spreadsheets_values->get($this->sheet_id, $range);
        $values = $response->getValues();
        return $values;
    }
    
    
    function add_row($sheet_name, $row) {
        
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
        return $result->getUpdates()->getUpdatedRange();
        
    }
    
    function update_rows($sheet_name, $Rows) {
        
        $service = new Google_Service_Sheets($this->client);
        
        $end = count($Rows)+1;
        global $wpdb;
        
        $values = [];
        $data = [];
        foreach($Rows as $key=>$value){
            $range = "{$sheet_name}!A{$key}:B{$key}";
            $values[] = $value;
            
            $data[] = new Google_Service_Sheets_ValueRange([
                'range' => $range,
                'values' => [$value]
            ]);
        }
        
        // wcgs_pa($data);
        // Additional ranges to update ...
        $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
            'valueInputOption' => "RAW",
            'data' => $data
        ]);
        $result = $service->spreadsheets_values->batchUpdate($this->sheet_id, $body);
        return $result;
        // wcgs_pa($result);
    }
    
    // Update Single Row
    function update_single_row($range, $row) {
        
        $service = new Google_Service_Sheets($this->client);
        
        
        $data[] = new Google_Service_Sheets_ValueRange([
            'range' => $range,
            'values' => [$row]
        ]);
        
        // wcgs_pa($data);
        // Additional ranges to update ...
        $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
            'valueInputOption' => "RAW",
            'data' => $data
        ]);
        $result = $service->spreadsheets_values->batchUpdate($this->sheet_id, $body);
        do_action('wcgs_after_category_synced', $row, $range);
        // wcgs_pa($result);
    }
    
    // Delete Single Row
    function delete_row($sheetId, $rowNo) {
        
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
    }
}