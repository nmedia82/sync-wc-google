<?php
/**
 * Data formats controller
 **/
 
class WBPS_Format {
    
    private static $ins = null;
	
	public static function __instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
    
    function __construct() {
        
        add_filter('wcgs_sync_data_products_before_processing', array($this, 'format_data_products'), 11, 2);
        add_filter('wcgs_products_data_attributes', array($this, 'product_attributes'), 99, 3);
        add_filter('wcgs_products_data_categories', array($this, 'product_extract_id_categories'), 99, 3);
        add_filter('wcgs_products_data_tags', array($this, 'product_extract_id_tags'), 99, 3);
        add_filter('wcgs_products_data_image', array($this, 'variation_image'), 99, 3);
        add_filter('wcgs_products_data_images', array($this, 'product_images'), 99, 3);
        add_filter('wcgs_products_data_dimensions', array($this, 'product_dimensions'), 99, 3);
        add_filter('wcgs_products_data_downloads', array($this, 'product_downloads'), 99, 3);
        // add_filter('wcgs_products_data_meta_data', array($this, 'product_meta_data'), 99, 2);
        
        if( wbps_pro_is_installed() ) {
            add_filter('wbps_products_synback', array($this, 'syncback_data_products'), 11, 3);
            
            // Categories
            add_filter('wcgs_sync_data_categories_before_processing', array($this, 'format_data_categories'), 11, 2);
            add_filter('wcgs_categories_data_image', array($this, 'categories_image'), 99, 3);
        }
    
    }
    
    
    function format_data_products($sheet_data, $general_settings) {
        
        $sheet_data = array_map(function($item) use($general_settings) {
            
            foreach(wbps_fields_format_required() as $key => $type){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = apply_filters("wcgs_products_data_{$key}", $item[$key], $item, $general_settings);
            }
            
            // since version 6.2 integer array values will be parsed here
            foreach(wbps_fields_integer_array() as $key){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = $this->parsing_integer_sting_to_array($item[$key], $item);
            }
            
            
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
        
        // wbps_logger_array($sheet_data);
        return $sheet_data;
    }
    
    function format_data_categories($sheet_data, $general_settings) {
        
        $sheet_data = array_map(function($item) {
            foreach(wbps_fields_format_required() as $key => $type){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = apply_filters("wcgs_categories_data_{$key}", $item[$key], $item, $general_settings);
            }
            
            return $item;
            
        }, $sheet_data);
        
        return $sheet_data;
    }
    
    // Categories|Tags Sheet ==> Site
    function product_extract_id_categories($value, $row, $general_settings){
        
        $return_value = $general_settings['categories_return_value'];
        
        if( $return_value === 'object' ){
            return json_decode($value);
        }
        
        $names_enabled = false;
        $cat_names = [];
        if( 'name' === WBPS_CATEGORIES_TAG_DATA ){
            $cat_names = wcgs_get_taxonomy_names('product_cat');
            $cat_names = array_flip($cat_names);
            $names_enabled = true;
        }
        
        // var_dump($value);
        if( ! $value ) return $value;
        $make_array = explode('|', $value);
        $value = array_map(function ($v) use($cat_names, $names_enabled) {
            $item['id'] = $names_enabled ? $cat_names[trim($v)] : $v;
            return $item;
        }, $make_array);
        
        // wbps_logger_array($value);
        return $value;
    }
    
    // Tags Sheet ==> Site
    function product_extract_id_tags($value, $row, $general_settings){
        
        $return_value = $general_settings['tags_return_value'];
        
        if( $return_value === 'object' ){
            return json_decode($value);
        }
        
        $names_enabled = false;
        $cat_names = [];
        if( 'name' === WBPS_CATEGORIES_TAG_DATA ){
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
    function product_attributes($attributes, $row, $general_settings){
        
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
    // function product_variations($variations, $row, $general_settings){
        
    //     if( ! $variations ) return $variations;
    //     $variations = json_decode($variations, true);
    //     return $variations;
    // }
    
    // Image (variations)
    function variation_image($image, $row, $general_settings){
        
        $return_value = $general_settings['image_return_value'];
        
        if( $return_value === 'object' ){
            return json_decode($image);
        }
        
        if( $image == '' ) return $image;
        $image = trim($image);
        $key = (filter_var($image, FILTER_VALIDATE_URL) === FALSE) ? 'id' : 'src';
        $image_remake[$key] = $image;
        
        return $image_remake;
    }
    
    // Images
    function product_images($images, $row, $general_settings){
        
        $return_value = $general_settings['images_return_value'];
        
        if( $return_value === 'object' ){
            return json_decode($images, true);
        }
        
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
    function categories_image($image, $row, $general_settings){
        
        $return_value = $general_settings['image_return_value'];
        
        if( $return_value === 'object' ){
            return json_decode($image, true);
        }
        
        if( $image == '' ) return $image;
        $image = trim($image);
        $key = (filter_var($image, FILTER_VALIDATE_URL) === FALSE) ? 'id' : 'src';
        $image_remake[$key] = $image;
        
        return $image_remake;
    }
    
    // Dimensions
    function product_dimensions($dimensions, $row, $general_settings){
        
        if( $dimensions == '' ) return $dimensions;
        $dimensions = json_decode($dimensions, true);
        return $dimensions;
    }
    
    // Downloads
    function product_downloads($downloads, $row, $general_settings){
        
        if( $downloads == '' ) return $downloads;
        $downloads = json_decode($downloads, true);
        return $downloads;
    }
    
    // Product Meta Data
    // function product_meta_data($meta_data, $row) {
        
    //     // wbps_logger_array($meta_data);
    //     if( ! $meta_data ) return $meta_data;
    //     if( isset($row['row_id_meta']) )
    //         $meta_data= array_merge($meta_data, $row['row_id_meta']);
            
    //     return $meta_data;
    // }
    
    // Formatting products before syncback
    function syncback_data_products($products, $header, $settings) {
        
        foreach(wbps_fields_integer_array() as $key){
            $products = array_map(function($p) use($key){
                // $p['type'] !== "variation" - variation does not have any array data in these keys
                if(in_array($key, array_keys($p)) && $p['type'] !== "variation"){
                    $p[$key] = implode('|', $p[$key]);
                }
                return $p;
            }, $products);
        }
        
        foreach(wbps_fields_format_required() as $key=>$type){
            $key = trim($key);
            
            $products = array_map(function($p) use($key, $settings){
                
                if( isset($p[$key]) ){
            
                    $value = $p[$key];
                    
                    $value = $value === NULL ? "" : $value;
                    $value = apply_filters("wcgs_products_syncback_value", $value, $key);
                    $value = apply_filters("wcgs_products_syncback_value_{$key}", $value, $key, $settings);
                    
                    $p[$key] = $value;
                }
                
                return $p;
            }, $products);
        }
        
        return $products;
        
    }
    

}

function init_wbps_format(){
	return WBPS_Format::__instance();
}