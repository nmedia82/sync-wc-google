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

// When WC categories created
add_action( "created_product_cat", "wcgs_update_gsheet_create_cat", 99, 2);
function wcgs_update_gsheet_create_cat($term_id, $tt_id){
    
    if ( !isset($_POST['action']) && $_POST['action'] != 'add-tag') return '';
    
   
    $wcapi = new WCGS_WC_API();
    $row = $wcapi->get_category_for_gsheet($term_id);
    $gs = new WCGS_APIConnect();
    $sheet_name = "categories";
    $range = $gs->add_row($sheet_name, $row);
    update_term_meta($term_id, 'gs_range', $range);
}

// When WC categories updated
add_action( "edited_product_cat", "wcgs_update_gsheet_edit_cat", 99, 2);
function wcgs_update_gsheet_edit_cat($term_id, $tt_id){
    
    if ( !isset($_POST['action']) && $_POST['action'] != 'editedtag') return '';
    
    $row_id = get_term_meta($term_id, 'gs_range', true);
    if( !$row_id ) return;

    $wcapi = new WCGS_WC_API();
    $row = $wcapi->get_category_for_gsheet($term_id);
    
    $updatable_data = array('name', 'slug', 'parent');
    $updatable_data = apply_filters('wcgs_category_updatble_data', $updatable_data);
    
    $wcapi = new WCGS_WC_API();
    
    $header = get_option('wcgs_category_header');
    // wcgs_pa($header);
    
    $ranges_value = array();
    foreach($updatable_data as $value) {
        
        $index = isset($header[$value]) ? $header[$value] : null;
        if( !$index ) continue;
        
        $column = wcgs_get_header_column_by_index($index);
        
        if( !isset($row[$index]) || !$column ) continue;
        
        $range = "categories!{$column}{$row_id}";
        $value = [ wp_specialchars_decode($row[$index]) ];
        $ranges_value[$range] = $value; 
    }
    
    // wcgs_pa($ranges_value); exit;
    $gs = new WCGS_APIConnect();
    $gs->update_single_row($ranges_value, $row);
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
    // var_dump($rowNo, $sheetId); exit;
    
    
    $gs = new WCGS_APIConnect();
    $gs->delete_row($sheetId, $rowNo);
    
    // exit;
}

/***
 * ============= Product Row Data Filter Sheet ==> WC API =================
 * **/
// short_descriptions esc_html
// add_filter('wcgs_row_data_short_description', 'wcgs_product_short_description_data', 2, 99);
function wcgs_product_short_description_data($description, $row){
    
    if( ! $description ) return $description;
    $description = esc_html($description);
    return $description;
    
}
// Categories
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

// Categories
add_filter('wcgs_row_data_tags', 'wcgs_product_tags_data', 2, 99);
function wcgs_product_tags_data($tags, $row){
    
    if( ! $tags ) return $tags;
    $make_array = explode(',', $tags);
    $tags = array_map(function ($category) {
        $cat['id'] = $category;
        return $cat;
    }, $make_array);
    // wcgs_pa($tags);
    return $tags;
}
// Attributes
add_filter('wcgs_row_data_attributes', 'wcgs_product_attribute_data', 2, 99);
function wcgs_product_attribute_data($attributes, $row){
    
    if( ! $attributes ) return $attributes;
    $attributes = json_decode($attributes, true);
    // $make_array = explode(';', $attributes);
    // $attributes = array_map(function ($attribute) {
    //     $breakup = explode('|', $attribute);
    //     var_dump($breakup);
    //     $attr_id = $breakup[0];
    //     $attr_options = explode(",", $breakup[1]);
        
    //     $attr['id'] = $attr_id;
    //     $attr['visible'] = true;
    //     $attr['variation'] = true;
    //     $attr['options'] = $attr_options;
        
    //     return $attr;
    // }, $make_array);
    // var_dump($attributes);
    return $attributes;
}

// Variations
add_filter('wcgs_row_data_variations', 'wcgs_product_variations_data', 2, 99);
function wcgs_product_variations_data($variations, $row){
    
    if( ! $variations ) return $variations;
    $variations = json_decode($variations, true);
    // $make_array = explode(';', $variations);
    // $variations = array_map(function ($attribute) {
    //     $breakup = explode('|', $attribute);
    //     var_dump($breakup);
    //     $attr_id = $breakup[0];
    //     $attr_options = explode(",", $breakup[1]);
        
    //     $attr['id'] = $attr_id;
    //     $attr['visible'] = true;
    //     $attr['variation'] = true;
    //     $attr['options'] = $attr_options;
        
    //     return $attr;
    // }, $make_array);
    // var_dump($variations);
    return $variations;
}
// Images
add_filter('wcgs_row_data_images', 'wcgs_product_images_data', 2, 99);
function wcgs_product_images_data($images, $row){
    
    if( ! $images ) return $images;
    $make_array = explode(',', $images);
    $images = array_map(function ($image) {
        // $img['src'] = $image;
        $img['id'] = $image;
        return $img;
    }, $make_array);
    // wcgs_pa($images);
    return $images;
}


/** ================ Product Update/Create Hooks ================ **/
// add_action('wcgs_after_products_synced', 'wcgs_update_product_meta', 99, 3);
// WE DDON'T NEED THIS HOOK WE ADDING META_DATA IN BATCH UPDATE WITH KEY: wcgs_row_id
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
        
        if( $postid === 'ERROR' ) continue;
        
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
add_action('save_post_product', 'wcgs_update_gsheet_edit_product', 99, 3);
function wcgs_update_gsheet_edit_product($id, $product, $update){
    
    // If this is a revision, get real post ID
    if ( $parent_id = wp_is_post_revision( $id ) ) 
        $id = $parent_id;
    
    if( $update ) {
        $row_id = get_post_meta($id, 'wcgs_row_id', true);
        if( !$row_id ) return;
        
        $updatable_data = array('name', 'description', 'status', 'short_description', 'sku', 'regular_price', 'sale_price', 'last_sync');
        $updatable_data = apply_filters('wcgs_product_updatble_data', $updatable_data);
        
        $wcapi = new WCGS_WC_API();
        $row = $wcapi->get_product_for_gsheet($id);
        
        $header = get_option('wcgs_product_header');
        // wcgs_pa($header);
        
        $ranges_value = array();
        foreach($updatable_data as $value) {
            
            $index = isset($header[$value]) ? $header[$value] : null;
            if( !$index ) continue;
            
            $column = wcgs_get_header_column_by_index($index);
            
            if( !$column ) continue;
            
            $range = "products!{$column}{$row_id}";
            $cell_value = $value == 'last_sync' ? [date('Y-m-d h:i:sa', time())] : [ wp_specialchars_decode($row[$index]) ];
            $ranges_value[$range] = $cell_value; 
        }
        
        // wcgs_pa($ranges_value); exit;
        $gs = new WCGS_APIConnect();
        $gs->update_single_row($ranges_value, $row);
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