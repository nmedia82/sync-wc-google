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
    $metakey = 'gs_range';
    
    foreach($categories as $key=>$value){
        // $range = "{$sheet_name}!A{$key}:E{$key}";
        $range = "{$sheet_name}!{$key}:{$key}";
        
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

// When WC categories created
add_action( "created_product_cat", "wcgs_update_gsheet_create_cat", 99, 2);
function wcgs_update_gsheet_create_cat($term_id, $tt_id){
    
    if ( !isset($_POST['action']) && $_POST['action'] != 'add-tag') return '';
    
   
    $wcapi = new WCGS_WC_API();
    $row = $wcapi->get_category_for_gsheet($term_id);
    $gs = new GoogleSheet_API();
    $sheet_name = "categories";
    $range = $gs->add_row($sheet_name, $row);
    update_term_meta($term_id, 'gs_range', $range);
}

// When WC categories updated
add_action( "edited_product_cat", "wcgs_update_gsheet_edit_cat", 99, 2);
function wcgs_update_gsheet_edit_cat($term_id, $tt_id){
    
    if ( !isset($_POST['action']) && $_POST['action'] != 'editedtag') return '';
    
    $range = get_term_meta($term_id, 'gs_range', true);
    if( !$range ) return;
    $wcapi = new WCGS_WC_API();
    $row = $wcapi->get_category_for_gsheet($term_id);
    // var_dump($row);
    $gs = new GoogleSheet_API();
    $gs->update_single_row($range, $row);
    // exit;
}

// When WC categories deleted
add_action( "pre_delete_term", "wcgs_update_gsheet_delete_cat", 99, 2);
function wcgs_update_gsheet_delete_cat($term_id, $taxonomy){
    
    if ( !isset($_POST['action']) && $_POST['action'] != 'delete-tag') return '';
    
    $sheetId = wcgs_get_sheetid_by_title('categories');
    $range = get_term_meta($term_id, 'gs_range', true);
    if( !$range ) return;
    $rowNo = substr($range, -1);
    // var_dump($range); exit;
    
    $gs = new GoogleSheet_API();
    $gs->delete_row($sheetId, $rowNo);
    
    // exit;
}

/***
 * ============= Product Row Data Filter Sheet ==> WC API =================
 * **/
add_filter('wcgs_row_data_categories', 'wcgs_product_category_data', 2, 99);
function wcgs_product_category_data($categories, $row){
    
    if( ! $categories ) return $categories;
    $make_array = explode(',', $categories);
    $categories = array_map(function ($category) {
        $cat['id'] = $category;
        return $cat;
    }, $make_array);
    // wcgs_pa($categories);
    return $categories;
}


/** ================ Product Update/Create Hooks ================ **/
add_action('wcgs_after_products_synced', 'wcgs_update_product_meta', 99, 3);
function wcgs_update_product_meta($products, $sheet_name, $synced_result) {
 
    if( count($products) <= 0 ) return;
    
    global $wpdb;
    $postmeta_table = $wpdb->prefix.'postmeta';
    
    $wpsql = "INSERT INTO {$postmeta_table} (post_id,meta_key,meta_value) VALUES ";
    $delqry = "DELETE FROM {$postmeta_table} WHERE post_id IN (";
    $metakey = 'gs_range';
    
    foreach($products as $key=>$value){
        // $range = "{$sheet_name}!A{$key}:E{$key}";
        $range = "{$sheet_name}!{$key}:{$key}";
        
        $postid = $value[0];    // post id
        $metaval = $range;
        
        // Delete existing posts meta if any
        $delqry .= "{$postid},";
        // post meta sql
        $wpsql .= "({$postid}, '{$metakey}', '{$metaval}'),";
    
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

// On product save/update
// add_action('save_post_product', 'wcgs_update_gsheet_edit_product', 99, 3);
function wcgs_update_gsheet_edit_product($id, $product, $update){
    
    // If this is a revision, get real post ID
    if ( $parent_id = wp_is_post_revision( $id ) ) 
        $id = $parent_id;
    
    if( $update ) {
        $range = get_post_meta($id, 'gs_range', true);
        if( !$range ) return;
        $wcapi = new WCGS_WC_API();
        $row = $wcapi->get_product_for_gsheet($id);
        wcgs_pa($row); exit;
        $gs = new GoogleSheet_API();
        // $gs->update_single_row($range, $row);
    }
}

