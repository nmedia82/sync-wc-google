<?php
/**
 * Plugin Meta Array
 * 
 * */

if( ! defined('ABSPATH') ) die('Not Allowed.');

// REST API
function wbps_get_rest_endpoints() {
    
    $endpionts =    [
                    ['slug'=>'check-pro','callback'=>'check_pro','method'=>'POST'],
                    ['slug'=>'connection-check','callback'=>'connection_check','method'=>'GET'],
                    ['slug'=>'verify-authcode','callback'=>'verify_authcode','method'=>'POST'],
                    ['slug'=>'product-sync','callback'=>'product_sync','method'=>'POST'],
                    ['slug'=>'category-sync','callback'=>'category_sync','method'=>'POST'],
                    ['slug'=>'prepare-fetch','callback'=>'prepare_fetch','method'=>'POST'],
                    ['slug'=>'product-fetch','callback'=>'product_fetch','method'=>'POST'],
                    ['slug'=>'category-fetch','callback'=>'category_fetch','method'=>'POST'],
                    ['slug'=>'attributes-fetch','callback'=>'attributes_fetch','method'=>'GET'],
                    ['slug'=>'disconnect-store','callback'=>'disconnect_store','method'=>'POST'],
                    ['slug'=>'webhook-callback','callback'=>'webhook_callback','method'=>'POST'],
                    ['slug'=>'link-new-product','callback'=>'link_new_product','method'=>'POST'],
                    ['slug'=>'save-sheet-props','callback'=>'save_sheet_props','method'=>'POST'],
                    ['slug'=>'relink-products','callback'=>'relink_products','method'=>'POST'],
                    ['slug'=>'enable-webhook','callback'=>'enable_webhook','method'=>'POST'],
                    ['slug'=>'disable-webhook','callback'=>'disable_webhook','method'=>'POST'],
                    ];
                    
    return apply_filters('wbps_rest_endpoints', $endpionts);
}