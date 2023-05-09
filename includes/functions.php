<?php 
/**
 * Helper functions
 **/


function wbps_logger_array($msg){
    wc_get_logger()->debug( wc_print_r( $msg, true ), array( 'source' => 'WCBulkProductSync' ) );
}

function wbps_load_file($file_name, $vars=null) {
         
   if( is_array($vars))
    extract( $vars );
    
   $file_path =  WBPS_PATH . '/templates/'.$file_name;
   if( file_exists($file_path))
   	include ($file_path);
   else
   	die('File not found'.$file_path);
}

function wbps_pro_is_installed() {
    
    if( !defined('WCGS_PRO_VERSION') ) return false;
    if( intval(WCGS_PRO_VERSION) < 7 ) return false;
    
    return true;
}

// Field that need to be formatted
function wbps_fields_format_required() {
    
    return apply_filters('wbps_fields_format_required', 
                        ['categories'=>'array', 'upsell_ids'=>'array','tags'=>'array','downloads'=>'array','images'=>'array', 'attributes'=>'array','image'=>'array','meta_data'=>'array','dimensions'=>'array']);
}

// Field with integer arrays
function wbps_fields_integer_array() {
    
    return apply_filters('wcgs_fields_integer_array', 
                        ['variations','grouped_products','cross_sell_ids','upsell_ids','related_ids']
                        );
}


// return product ids which needs to be fetched.
// $product_status: ['publish','draft']
function wbps_get_syncback_product_ids($product_status=['publish']) {
    
    $include_products = [];
    
    // better to use wp_query method, as wc_get_products not working with status=>draft
    if( apply_filters('wbps_use_wp_query', true) ) {
    
        global $wpdb;
        $qry = "SELECT DISTINCT ID FROM {$wpdb->prefix}posts WHERE";
        $qry .= " post_type = 'product'";
        // product status
        // adding single qoute
        $product_status = array_map(function($status){
            return "'{$status}'";
        }, $product_status);
        
        $product_status = implode(",",$product_status);
        $qry .= " AND post_status IN ({$product_status})";
        
        // disabling for now
        // $syncback_setting = get_option('wbps_syncback_settings');
        // if( $syncback_setting == 'not_linked' ){
            
        //     $qry .= " AND NOT EXISTS (SELECT * from {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID AND {$wpdb->prefix}postmeta.meta_key = 'wbps_row_id');";
        // }
        
        $qry = apply_filters('wbps_chunk_query', $qry);
        
        $products_notsync = $wpdb->get_results($qry, ARRAY_N);
        $include_products = array_map(function($item){ return $item[0]; }, $products_notsync);
        
    } else {
    
        // Get product ids.
        $args = array(
          'return'  => 'ids',
          'orderby' => 'id',
          'order'   => 'ASC',  
          'limit'   => -1,
          'status'  => $product_status,
        );
        
        
        $include_products = wc_get_products( $args );
    }
    
    // wbps_log($include_products); exit;
    return apply_filters('wbps_get_syncback_product_ids', $include_products);
  
}

function wbps_get_webapp_url(){
    $url = get_option('wbps_webhook_url', true);
    return $url;
}

function wbps_get_product_meta_col_value($product, $col_key){
    
    $value_found = '';
    $value_found = get_post_meta($product['id'], $col_key, true);
    if( $value_found ) return $value_found;
    // wbps_logger_array($value_found);
    
    // backup meta value check
    $value_found = array_reduce($product['meta_data'], function($acc, $meta) use ($col_key) {
        if ($meta->key === $col_key) {
            return $meta->value;
        }
        return $acc;
    });
    
    return $value_found;
}