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

add_action('wcgs_after_categories_updated_v3', 'wcgs_categories_row_update');
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
// add_action( "created_product_cat", "wcgs_update_gsheet_create_cat", 99, 2);
function wcgs_update_gsheet_create_cat($term_id, $tt_id){
    
    if( ! array_key_exists ('action', $_POST) ) return '';
    if ( $_POST['action'] != 'add-tag') return '';
    
    $wcapi = new WCGS_WC_API();
    $row = $wcapi->get_category_for_gsheet($term_id);
    // wcgs_log($row); exit;
    $gs = new WCGS_APIConnect();
    $sheet_name = "categories";
    $range = $gs->add_row($sheet_name, $row);
    // extrating the row number from range
    $range = substr($range, strrpos($range, ':') + 1);
    $rowno = preg_replace('/[^0-9.]+/', '', $range);
    update_term_meta($term_id, 'wcgs_row_id', $rowno);
}

// When WC categories updated
// add_action( "edited_product_cat", "wcgs_update_gsheet_edit_cat", 99, 2);
function wcgs_update_gsheet_edit_cat($term_id, $tt_id){
    
    if( ! array_key_exists ('action', $_POST) ) return '';
    if ( $_POST['action'] != 'editedtag') return '';
    
    $ranges_value = wcgs_category_range_for_update($term_id);
    if( !$ranges_value ) return;
    
    // wcgs_pa($ranges_value); exit;
    $gs = new WCGS_APIConnect();
    $resp = $gs->update_rows_with_ranges($ranges_value);
    // exit;
}

// When WC categories deleted
add_action( "pre_delete_term", "wcgs_update_gsheet_delete_cat", 99, 2);
function wcgs_update_gsheet_delete_cat($term_id, $taxonomy){
    
    if( ! array_key_exists ('action', $_POST) ) return '';
    if ( $_POST['action'] != 'delete-tag') return '';
    
    // if ( !isset($_POST['action']) && $_POST['action'] != 'delete-tag') return '';
    
    $sheetId = wcgs_get_sheetid_by_title('categories');
    $range = get_term_meta($term_id, 'wcgs_row_id', true);
    if( !$range ) return;
    $rowNo = substr($range, -1);
    // var_dump($rowNo, $sheetId); exit;
    
    
    $gs = new WCGS_APIConnect();
    $gs->delete_row($sheetId, $rowNo);
    
    // exit;
}


/** ================ Product Update/Create Hooks ================ **/

// On product save/update
add_action('save_post_product', 'wcgs_update_gsheet_edit_product', 99, 3);
function wcgs_update_gsheet_edit_product($id, $product, $update){
    
    // If this is a revision, get real post ID
    if ( $parent_id = wp_is_post_revision( $id ) ) 
        $id = $parent_id;
    
    if( $update ) {
        $row_id = get_post_meta($id, 'wcgs_row_id', true);
        if( !$row_id ) return;
        
        $updatable_data = array('id','name', 'description', 'status', 'short_description', 'sku', 'regular_price', 'sale_price', 'last_sync');
        $updatable_data = apply_filters('wcgs_product_updatble_data', $updatable_data);
        
        $wcapi = new WCGS_WC_API();
        $row = $wcapi->get_product_for_gsheet($id);
        
        $header = get_option('wcgs_product_header');
        // wcgs_pa($header);
        
        $ranges_value = array();
        foreach($updatable_data as $value) {
            
            $index = isset($header[$value]) ? $header[$value] : null;
            if( $index === null ) continue;
            
            $column = wcgs_get_header_column_by_index($index);
            
            if( !$column ) continue;
            
            $range = "products!{$column}{$row_id}";
            $cell_value = $value == 'last_sync' ? [wcgs_get_last_sync_date()] : [ wp_specialchars_decode($row[$index]) ];
            $ranges_value[$range] = $cell_value; 
        }
        
        // wcgs_pa($ranges_value); exit;
        $gs = new WCGS_APIConnect();
        $gs->update_rows_with_ranges($ranges_value, $row);
    }
}

// Before deleting the product remove from Sheet
add_action( 'before_delete_post', 'wcgs_delete_sheet_product' );
function wcgs_delete_sheet_product($porduct_id){
    
    $row_id = get_post_meta($porduct_id, 'wcgs_row_id', true);
        if( !$row_id ) return;
        
    $sheetId = wcgs_get_sheetid_by_title('products');
    // var_dump($row_id, $sheetId); exit;
    
    $gs = new WCGS_APIConnect();
    $result = $gs->delete_row($sheetId, $row_id);
    // wcgs_pa($result); exit;
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

// add_filter( 'manage_products_posts_columns', 'create_synced_category_column', 20);
// function create_synced_category_column($columns){
//     $columns['wcgs_column'] = __('Sync Row#' , 'wcgs_category');
//     return  $columns;
    
// }

// // add_filter( 'manage_product&s_posts_custom_column', 'create_synced_category_column_data', 20, 2 );
// //manage cpt custom column data callback
// function create_synced_category_column_data( $column, $post_id){

//     switch ($column) {
//         case 'wcgs_column':
//             $rowno = get_post_meta($post_id,'wcgs_row_id', true);
//             if($rowno){
//                 echo $rowno;
//             }else{
//                 _e("Not synced", 'wcgs');
//             }
//         break;
//     }
// }
    








        