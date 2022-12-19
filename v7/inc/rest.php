<?php
/**
 * Rest API Handling
 * 
 * */

if( ! defined('ABSPATH') ) die('Not Allowed.');

/* == rest api == */
add_action( 'rest_api_init', 'wcgs_rest_api_register'); // endpoint url 
function wcgs_rest_api_register() {
    
    // Google App Script API
    register_rest_route('wcgs/v7', '/connect-store', array(
            'methods' => 'POST',
            'callback' => 'wcgs_connect_store',
            'permission_callback' => '__return_true'
    ));
}

// connect store
function wcgs_connect_store($request) {
    
    $data   = $request->get_params();
   
    if( !wcgs_verfiy_connected($data['authcode']) ){
        wp_send_json_error(__("Sorry, but your AuthCode is not valid", 'wcgs'));
    }
    
    if( ! wcgs_is_service_connect() ) {
        wp_send_json_error(__("Connection failed, make sure you have shared your connected Google Sheet with the following email: \r\n\r\n google-sync-service-account-2@lateral-array-290609.iam.gserviceaccount.com", 'wcgs'));
    }
    
    // wcgs_log($data); exit;
    // $wcgs_sheet = new WCGS_Sheet();
    // $wcgs_sheet->update($data);
    
    wp_send_json_success(__("Good Job! Sheet is connected", 'wcgs'));
}