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
        $range = "{$sheet_name}!A{$key}:E{$key}";
        
        $termid = $value[2];    // term id
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

// When WC categories updated
add_action( "edited_product_cat", "wcgs_update_gsheet_edit_cat", 99, 2);
function wcgs_update_gsheet_edit_cat($term_id, $tt_id){
    
    if ( !isset($_POST['action']) && $_POST['action'] != 'editedtag') return '';
    
    $range = get_term_meta($term_id, 'gs_range', true);
    if( !$range ) return;
    $wcapi = new WCGS_WC_API();
    $row = $wcapi->get_category_for_gsheet($term_id);
    
    $gs = new GoogleSheet_API();
    $gs->update_single_row($range, $row);
    // exit;
}

// When WC categories deleted
add_action( "pre_delete_term", "wcgs_update_gsheet_delete_cat", 99, 2);
function wcgs_update_gsheet_delete_cat($term_id, $taxonomy){
    
    // if ( !isset($_POST['action']) && $_POST['action'] != 'delete-tag') return '';
    
    $sheetId = wcgs_get_sheetid_by_title('categories');
    $range = get_term_meta($term_id, 'gs_range', true);
    // if( !$range ) return;
    $rowNo = substr($range, -1);
    // var_dump($range); exit;
    
    $gs = new GoogleSheet_API();
    $gs->delete_row($sheetId, $rowNo);
    
    // exit;
}