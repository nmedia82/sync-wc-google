<?php
/**
 * WP Action/Filters Hooks
 * 
 * */
 
 
add_action('wcgs_after_categories_synced', 'wcgs_update_termmeta', 99, 3);
function wcgs_update_termmeta($categories, $sheet_name, $synced_result) {
 
    if( count($categories) <= 0 ) return;
    
    global $wpdb;
    $termmeta_table = $wpdb->prefix.'termmeta';
    
    $wpsql = "INSERT INTO {$termmeta_table} (term_id,meta_key,meta_value) VALUES ";
    $delqry = "DELETE FROM {$termmeta_table} WHERE term_id IN (";
    $metakey = 'wcgs_row_id';
    
    foreach($categories as $key=>$value){
        
        $range = $key;
        
        $termid = $value[0];    // term id
        $metaval = $range;
        
        // Delete existing terms meta if any
        $delqry .= "{$termid},";
        // Term meta sql
        $wpsql .= "({$termid}, '{$metakey}', '{$metaval}'),";
    
    }
    
    // var_dump($wpsql); exit;
    
    // Delete query
    $delqry = rtrim($delqry, ',');
    $delqry .= ") AND meta_key='{$metakey}'";
    $wpdb->query($delqry);
    
    //insert query
    $wpsql = rtrim($wpsql, ',');
    $wpdb->query($wpsql);
}

add_action('wcgs_after_categories_synced_v3', 'wcgs_categories_row_update');
function wcgs_categories_row_update($rowRef) {
 
    if( count($rowRef) <= 0 ) return;
    
    global $wpdb;
    $termmeta_table = $wpdb->prefix.'termmeta';
    
    $wpsql = "INSERT INTO {$termmeta_table} (term_id,meta_key,meta_value) VALUES ";
    $delqry = "DELETE FROM {$termmeta_table} WHERE term_id IN (";
    $metakey = 'wcgs_row_id';
    
    foreach($rowRef as $ref){
        
        if( $ref['row'] == 'ERROR' ) continue;
        
        $termid = $ref['id'];    // term id
        $metaval = $ref['row'];
        
        // Delete existing terms meta if any
        $delqry .= "{$termid},";
        // Term meta sql
        $wpsql .= "({$termid}, '{$metakey}', '{$metaval}'),";
    
    }
    
    // var_dump($wpsql); exit;
    
    // Delete query
    $delqry = rtrim($delqry, ',');
    $delqry .= ") AND meta_key='{$metakey}'";
    $wpdb->query($delqry);
    
    //insert query
    $wpsql = rtrim($wpsql, ',');
    
    $wpdb->query($wpsql);
}

// When WC categories created
add_action( "created_product_cat", "wcgs_update_gsheet_create_cat", 99, 2);
function wcgs_update_gsheet_create_cat($term_id, $tt_id){
    
    
    if( ! array_key_exists ('action', $_POST) ) return '';
    if ( $_POST['action'] != 'add-tag') return '';
    
    $action = 'fetch-categories';
    $args = ['new_only'=>true];
    wcgs_send_google_rest_request($action, $args);
}

// When WC categories updated
add_action( "edited_product_cat", "wcgs_update_gsheet_edit_cat", 99, 2);
function wcgs_update_gsheet_edit_cat($term_id, $tt_id){
    
    wcgs_log($_POST);
    if( ! array_key_exists ('action', $_POST) ) return '';
    if ( $_POST['action'] != 'editedtag' && $_POST['action'] != 'inline-save-tax') return '';
    
    $row_id = (int) get_term_meta($term_id, 'wcgs_row_id', true);
    if( !$row_id ) return '';
    
    $action = 'fetch-categories';
    $args = ['ids'=>[$term_id]];
    wcgs_send_google_rest_request($action, $args);
}

// When WC categories deleted
add_action( "pre_delete_term", "wcgs_update_gsheet_delete_cat", 99, 2);
function wcgs_update_gsheet_delete_cat($term_id, $taxonomy){
    
    if( ! array_key_exists ('action', $_POST) ) return '';
    if ( $_POST['action'] != 'delete-tag') return '';
    
    $row_id = get_term_meta($term_id, 'wcgs_row_id', true);
    if( !$row_id ) return;
    
    $action = 'delete-row';
    $args = ['row'=>$row_id, 'sheet_name'=>'categories'];
    wcgs_send_google_rest_request($action, $args);
}


/** ================ Product Update/Create Hooks ================ **/

// When product creatd
add_action('woocommerce_new_product', 'wcgs_create_product_gsheet', 99, 2);
function wcgs_create_product_gsheet($id, $product){
    
    if( isset($_POST['request_type']) && $_POST['request_type'] == 'sync-sheet-data')
        return;
    
    $action = 'fetch-products';
    $args = ['new_only'=>true];
    wcgs_send_google_rest_request($action, $args);
}

// On product save/update
add_action('woocommerce_update_product', 'wcgs_updat_product_gsheet', 99, 2);
function wcgs_updat_product_gsheet($id, $product){
    
    if( isset($_POST['request_type']) && $_POST['request_type'] == 'sync-sheet-data')
        return;
        
    $action = 'fetch-products';
    $args = ['ids'=>[$id]];
    wcgs_send_google_rest_request($action, $args);
    return;
}

// Before deleting the product remove from Sheet
add_action( 'before_delete_post', 'wcgs_delete_sheet_product' );
function wcgs_delete_sheet_product($porduct_id){
    
    $row_id = get_post_meta($porduct_id, 'wcgs_row_id', true);
        if( !$row_id ) return;
        
    $action = 'delete-row';
    $args = ['row'=>$row_id, 'sheet_name'=>'products'];
    wcgs_send_google_rest_request($action, $args);
    return;
}

// Sync Back Simple Products & Categories
add_action('wcgs_after_sync_button', 'wcgs_sync_back_free', 99, 1);
function wcgs_sync_back_free($google_client) {
    
    wcgs_load_template_file('admin/sync-back.php', ['google_client'=>$google_client]);
}

add_filter( 'manage_product_posts_columns', 'create_synced_product_column', 20);
function create_synced_product_column($columns){
    $columns['wcgs_column'] = __('Sync Row#' , 'wcgs_product');
    return  $columns;
    
}

add_filter( 'manage_product_posts_custom_column', 'create_synced_product_column_data', 20, 2 );
//manage cpt custom column data callback
function create_synced_product_column_data( $column, $post_id){

    switch ($column) {
        case 'wcgs_column':
            $rowno = get_post_meta($post_id,'wcgs_row_id', true);
            if($rowno){
                echo $rowno;
            }else{
                _e("Not synced", 'wcgs');
            }
        break;
    }
}

add_filter( 'rest_product_collection_params', 'wcgs_change_post_per_page', 10, 1 );
function wcgs_change_post_per_page( $params ) {
    if ( isset( $params['per_page'] ) ) {
        $params['per_page']['maximum'] = 400;
    }
    
    return $params;
}