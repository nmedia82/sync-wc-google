<?php
/**
 * Data formats controller
 **/
 
class WCGS_Format {
    
    function __construct() {
        
        add_filter('wcgs_sync_data_products_before_processing', array($this, 'format_data_products'), 11, 2);
        add_filter('wcgs_products_data_categories', array($this, 'product_categories'), 99, 2);
        add_filter('wcgs_products_data_tags', array($this, 'product_tags'), 99, 2);
        add_filter('wcgs_products_data_attributes', array($this, 'product_attributes'), 99, 2);
        add_filter('wcgs_products_data_variations', array($this, 'product_variations'), 99, 2);
        add_filter('wcgs_products_data_image', array($this, 'variation_image'), 99, 2);
        add_filter('wcgs_products_data_images', array($this, 'product_images'), 99, 2);
        add_filter('wcgs_products_data_meta_data', array($this, 'product_meta_data'), 99, 2);
        
        // PRO
        add_filter('wcgs_products_synback', array($this, 'syncback_data_products'), 11, 2);
        add_filter('wcgs_categories_synback', array($this, 'syncback_data_categories'), 11, 2);
        
        // Categories
        // add_filter('wcgs_sync_data_categories_before_processing', array($this, 'format_data_categories'), 11, 2);
    
    }
    
    
    function format_data_products($sheet_data, $sheet_info) {
        
        $sheet_data = array_map(function($item) use ($sheet_info) {
            $sheet_name = $sheet_info['sheet_name'];
            foreach(wcgs_fields_format_required() as $key => $type){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = apply_filters("wcgs_{$sheet_name}_data_{$key}", $item[$key], $item);
            }
            
            // If row_meta column is not set then create one
            // and set row_id_meta, then remove row_id_meta
            if( empty($item['meta_data']) ){
                $item['meta_data'] = $item['row_id_meta'];
            }
            unset($item['row_id_meta']);
            
            return $item;
            
        }, $sheet_data);
        
        return $sheet_data;
    }
    
    function format_data_categories($sheet_data, $sheet_info) {
        
        $sheet_data = array_map(function($item) use ($sheet_info) {
            $sheet_name = $sheet_info['sheet_name'];
            foreach(wcgs_fields_format_required() as $key => $type){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = apply_filters("wcgs_{$sheet_name}_data_{$key}", $item[$key], $item);
            }
            
            // If row_meta column is not set then create one
            // and set row_id_meta, then remove row_id_meta
            if( !isset($item['meta_data']) ){
                $item['meta_data'] = $item['row_id_meta'];
            }
            unset($item['row_id_meta']);
            
            return $item;
            
        }, $sheet_data);
        
        return $sheet_data;
    }
    
    // Categories
    function product_categories($categories, $row){
        
        // var_dump($categories);
        if( ! $categories ) return $categories;
        $make_array = explode(',', $categories);
        $categories = array_map(function ($category) {
            $cat['id'] = $category;
            return $cat;
        }, $make_array);
        return $categories;
    }
    
    // Tags
    function product_tags($tags, $row){
        
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
    function product_attributes($attributes, $row){
        
        if( ! $attributes ) return [];
        
        $attributes = json_decode($attributes, true);
        
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
    function product_variations($variations, $row){
        
        if( ! $variations ) return $variations;
        $variations = json_decode($variations, true);
        return $variations;
    }
    
    // Image (variations)
    function variation_image($image, $row){
        
        if( $image == '' ) return $image;
        
        $image_from = get_option('wcgs_image_import');
        return [$image_from=>$image];
    }
    
    // Images
    function product_images($images, $row){
        
        if( $images == '' ) return $images;
        $make_array = explode(',', $images);
        // wcgs_pa($images);
        $image_from = get_option('wcgs_image_import');
        $image_remake = [];
        foreach($make_array as $img){
            $image_remake[][$image_from] = $img;
        }
        
        return $image_remake;
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
    
        $products_refined = [];
        foreach($products as $product) {
            
            foreach(wcgs_fields_format_required() as $key=>$type){
                $key = trim($key);
                
                if( !isset($product[$key]) ) continue;
                
                $value = $product[$key];
                
                $value = $value === NULL ? '' : $value;
                $value = apply_filters("wcgs_products_syncback_value", $value, $key);
                $value = apply_filters("wcgs_products_syncback_value_{$key}", $value, $key);
                
                $product[$key] = $value;
            }
            
            // Check if sync column meta exists
            $wcgs_row_id = get_post_meta($product['id'], 'wcgs_row_id', true);
            if( $wcgs_row_id ) {
                $products_refined['update'][$wcgs_row_id] = array_values($product);
            }else{
                $products_refined['create'][] = array_values($product);
            }
        }
        
        // wcgs_log($products_refined);
        return $products_refined;
    }
    
    
    // Formatting categories before syncback
    function syncback_data_categories($categories, $sheet_info) {
    
        $categories_refined = [];
        foreach($categories as $cat) {
            
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