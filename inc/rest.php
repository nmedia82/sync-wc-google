<?php
/**
 * Rest API Handling
 * 
 * */

if( ! defined('ABSPATH') ) die('Not Allowed.');

function wcgs_rest_api_register() {
    
    
    register_rest_route('nkb/v1', '/auth/', array(
        'methods' => 'GET',
        'callback' => 'wcgs_google_auth_code',
    ));
}


function wcgs_google_auth_code( $request ) {
    
    if( ! isset($_GET['code']) ) wp_die('Code Not Found', 'Google Code invalid');
    
    $authCode = $_GET['code'];
    $gs = new GoogleSheet_API();
    
    $gs->getClient($authCode);
    
    $url = add_query_arg('wcgs_code', 'added', WCGS_SETTING_URL);
    wp_redirect($url);
    exit;
}