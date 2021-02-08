<?php
/**
 * Rest API Handling
 * 
 * */

if( ! defined('ABSPATH') ) die('Not Allowed.');

/* == rest api == */
add_action( 'rest_api_init', 'wcgs_rest_api_register'); // endpoint url 
function wcgs_rest_api_register() {
    
    
    register_rest_route('nkb/v1', '/auth/', array(
        'methods' => 'GET',
        'callback' => 'wcgs_google_auth_code',
        'permission_callback' => '__return_true',
    ));
}


function wcgs_google_auth_code( $request ) {
    
    if( ! isset($_GET['code']) ) wp_die('Code Not Found', 'Google Code invalid');
    
    $authCode = sanitize_text_field($_GET['code']);
    $gs = new WCGS_APIConnect();
    
    $gs->getClient($authCode);
    
    $url = add_query_arg('wcgs_code', 'added', WCGS_SETTING_URL);
    wp_redirect($url);
    exit;
}