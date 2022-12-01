<?php
/**
 * Data formats controller
 **/
 
class WCGS_Format {
    
    function __construct() {
        
        add_filter('wcgs_sync_data_products_before_processing', array($this, 'format_data_products'), 11, 1);
        add_filter('wcgs_products_data_attributes', array($this, 'product_attributes'), 99, 2);
        add_filter('wcgs_products_data_categories', array($this, 'product_extract_id_categories'), 99, 2);
        add_filter('wcgs_products_data_tags', array($this, 'product_extract_id_tags'), 99, 2);
        add_filter('wcgs_products_data_image', array($this, 'variation_image'), 99, 2);
        add_filter('wcgs_products_data_images', array($this, 'product_images'), 99, 2);
        add_filter('wcgs_products_data_dimensions', array($this, 'product_dimensions'), 99, 2);
        add_filter('wcgs_products_data_meta_data', array($this, 'product_meta_data'), 99, 2);
        
        if( wcgs_pro_is_installed() ) {
            add_filter('wcgs_products_synback', array($this, 'syncback_data_products'), 11, 2);
            add_filter('wcgs_categories_synback', array($this, 'syncback_data_categories'), 11, 2);
            
            // Categories
            add_filter('wcgs_sync_data_categories_before_processing', array($this, 'format_data_categories'), 11, 2);
            add_filter('wcgs_categories_data_image', array($this, 'categories_image'), 99, 2);
        }
    
    }
    
    
    function format_data_products($sheet_data) {
        
        $sheet_data = array_map(function($item) {
            foreach(wcgs_fields_format_required() as $key => $type){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = apply_filters("wcgs_products_data_{$key}", $item[$key], $item);
            }
            
            // since version 6.2 integer array values will be parsed here
            foreach(wcgs_fields_integer_array() as $key){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = $this->parsing_integer_sting_to_array($item[$key], $item);
            }
            
            // If row_meta column is not set then create one
            // and set row_id_meta, then remove row_id_meta
            if( empty($item['meta_data']) ){
                $item['meta_data'] = $item['row_id_meta'];
            }
            unset($item['row_id_meta']);
            
            // Adding meta column if found
            $meta_keys = get_option('wcgs_metadata_keys');
            if($meta_keys){
                
                // getting the allowed meta keys and converting to array
                $meta_array = explode(',', $meta_keys);
                $meta_array = array_map('trim', $meta_array);
                // flipping: to intersect with item main data
                $meta_array = array_flip($meta_array);
                // extract only meta data columns
                $meta_column_found = array_intersect_key($item, $meta_array);
                
                // Now exclude the meta columns from main data
                $item = array_diff_key($item, $meta_array);
                
                // Adding the meta cound in meta_data
                foreach($meta_column_found as $key => $val ){
                    $item['meta_data'][] = ['key' => $key, 'value' => $val];
                }
            }
            
            return $item;
            
        }, $sheet_data);
        
        // wcgs_log($sheet_data);
        return $sheet_data;
    }
    
    function format_data_categories($sheet_data, $sheet_info) {
        
        $sheet_data = array_map(function($item) use ($sheet_info) {
            $sheet_name = $sheet_info['sheet_name'];
            foreach(wcgs_fields_format_required() as $key => $type){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = apply_filters("wcgs_{$sheet_name}_data_{$key}", $item[$key], $item);
            }
            
            
            return $item;
            
        }, $sheet_data);
        
        return $sheet_data;
    }
    
    // Categories|Tags Sheet ==> Site
    function product_extract_id_categories($value, $row){
        
        $names_enabled = false;
        $cat_names = [];
        if( 'name' === WCGS_CATEGORIES_TAG_DATA ){
            $cat_names = wcgs_get_taxonomy_names('product_cat');
            $cat_names = array_flip($cat_names);
            $names_enabled = true;
        }
        wcgs_log($row);
        
        // var_dump($value);
        if( ! $value ) return $value;
        $make_array = explode('|', $value);
        $value = array_map(function ($v) use($cat_names, $names_enabled) {
            $item['id'] = $names_enabled ? $cat_names[trim($v)] : $v;
            return $item;
        }, $make_array);
        return $value;
    }
    
    // Tags Sheet ==> Site
    function product_extract_id_tags($value, $row){
        
        $names_enabled = false;
        $cat_names = [];
        if( 'name' === WCGS_CATEGORIES_TAG_DATA ){
            $cat_names = wcgs_get_taxonomy_names('product_tag');
            $cat_names = array_flip($cat_names);
            $names_enabled = true;
        }
        // wcgs_log($row);
        
        // var_dump($value);
        if( ! $value ) return $value;
        $make_array = explode('|', $value);
        $value = array_map(function ($v) use($cat_names, $names_enabled) {
            $item['id'] = $names_enabled ? $cat_names[trim($v)] : $v;
            return $item;
        }, $make_array);
        return $value;
    }
    
    // Parsing value from string to array for all integers
    function parsing_integer_sting_to_array($value, $row){
        
        // var_dump($value);
        if( ! $value ) return $value;
        $make_array = explode('|', $value);
        // $value = array_map(function ($v) {
        //     $item['id'] = $v;
        //     return $item;
        // }, $make_array);
        return $make_array;
    }
    
    
    // Attributes
    function product_attributes($attributes, $row){
        
        if( ! $attributes ) return [];
        $attributes = json_decode($attributes, true);
        // wcgs_log($attributes);
        return $attributes;
        
        if($row['type'] == 'variation') {
            $atts = [];
            foreach($attributes as $name => $option){
                $att['name'] = $name;
                $att['option'] = $option;
                $atts[] = $att; 
            }   
            $attributes = $atts;
        }
        
        return $attributes;
    }
    
    // Variations
    // function product_variations($variations, $row){
        
    //     if( ! $variations ) return $variations;
    //     $variations = json_decode($variations, true);
    //     return $variations;
    // }
    
    // Image (variations)
    function variation_image($image, $row){
        
        if( $image == '' ) return $image;
        $image = trim($image);
        $key = (filter_var($image, FILTER_VALIDATE_URL) === FALSE) ? 'id' : 'src';
        $image_remake[$key] = $image;
        
        return $image_remake;
    }
    
    // Images
    function product_images($images, $row){
        
        if( $images == '' ) return $images;
        $make_array = explode('|', $images);
        $image_remake = [];
        foreach($make_array as $img){
            $img = trim($img);
            $key = (filter_var($img, FILTER_VALIDATE_URL) === FALSE) ? 'id' : 'src';
            $image_remake[][$key] = $img;
        }
        return $image_remake;
    }
    
    // Category Image
    function categories_image($image, $row){
        
        if( $image == '' ) return $image;
        $image = trim($image);
        $key = (filter_var($image, FILTER_VALIDATE_URL) === FALSE) ? 'id' : 'src';
        $image_remake[$key] = $image;
        
        return $image_remake;
    }
    
    // Dimensions
    function product_dimensions($dimensions, $row){
        
        if( $dimensions == '' ) return $dimensions;
        $dimensions = json_decode($dimensions, true);
        return $dimensions;
    }
    
    // Product Meta Data
    function product_meta_data($meta_data, $row) {
        
        if( ! $meta_data ) return $meta_data;
        $meta_data = json_decode($meta_data, true);
        // wcgs_log($meta_data);
        if( isset($row['row_id_meta']) )
            $meta_data= array_merge($meta_data, $row['row_id_meta']);
            
        return $meta_data;
    }
    
    // Formatting products before syncback
    function syncback_data_products($products, $sheet_info) {
        
        global $wpdb;
        $qry = "SELECT post_id, meta_value from {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.meta_key = 'wcgs_row_id'";
        $db_results = $wpdb->get_results($qry);
        $pid_rows = [];
        foreach($db_results as $row){
          $pid_rows[$row->post_id] = $row->meta_value;
        }
        
        // since version 6.2 integer array values will be parsed here
        foreach(wcgs_fields_integer_array() as $key){
            $products = array_map(function($p) use($key){
                // $p['type'] !== "variation" - variation does not have any array data in these keys
                if(in_array($key, array_keys($p)) && $p['type'] !== "variation"){
                    $p[$key] = implode('|', $p[$key]);
                }
                return $p;
            }, $products);
        }
        
        foreach(wcgs_fields_format_required() as $key=>$type){
            $key = trim($key);
            
            $products = array_map(function($p) use($key){
                
                if( isset($p[$key]) ){
            
                    $value = $p[$key];
                    
                    $value = $value === NULL ? "" : $value;
                    $value = apply_filters("wcgs_products_syncback_value", $value, $key);
                    $value = apply_filters("wcgs_products_syncback_value_{$key}", $value, $key);
                    
                    $p[$key] = $value;
                }
                
                return $p;
            }, $products);
        }
        
    
        $products_refined = [];
        foreach($products as $product) {
            // Check if sync column meta exists
            if( isset($pid_rows[$product['id']]) && $wcgs_row_id = $pid_rows[$product['id']] ) {
                 $update_array = array_map( function($item) {
                    $item = $item == "" ? "" : html_entity_decode($item);
                    return $item;
                }, array_values($product));
                $products_refined['update'][$wcgs_row_id] = $update_array;
            }else{
                $create_array = array_map( function($item) {
                    $item = $item == "" ? "" : html_entity_decode($item);
                    return $item;
                }, array_values($product));
                $products_refined['create'][] = $create_array;
            }
        }
        
        // wcgs_log($products_refined); exit;
        // exit;
        return $products_refined;
    }
    
    
    // Formatting categories before syncback
    function syncback_data_categories($categories, $sheet_info) {
    
        $categories_refined = [];
        foreach($categories as $cat) {
            
            if( isset($cat['image']) ) {
                $cat['image'] = apply_filters("wcgs_categories_syncback_value_image", $cat['image'], 'image');
            }
            
            // Check if sync column meta exists
            $wcgs_row_id = get_term_meta($cat['id'], 'wcgs_row_id', true);
            $wcgs_row_id = intval($wcgs_row_id);
            if( $wcgs_row_id ) {
                $categories_refined['update'][$wcgs_row_id] = array_values($cat);
            }else{
                $categories_refined['create'][] = array_values($cat);
            }
        }
        
        // wcgs_log($categories_refined); exit;
        return $categories_refined;
    }

}

new WCGS_Format;