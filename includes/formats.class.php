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
        add_filter('wcgs_products_data_brands', array($this, 'product_extract_id_brands'), 99, 3);
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
    
    // syncing: format data before saving
    function format_data_products($sheet_data, $general_settings) {
        
        $taxonomy_found = wpbs_get_taxonomy_names();
        
        $sheet_data = array_map(function($item) use($general_settings, $taxonomy_found) {
            
            foreach(wbps_fields_format_required() as $key => $type){
                
                if( !isset($item[$key]) ) continue;
                
                $item[$key] = apply_filters("wcgs_products_data_{$key}", $item[$key], $item, $general_settings);
            }
            
            // since version 6.2 integer array values will be parsed here
            foreach(wbps_fields_integer_array() as $key){
                
                if( !isset($item[$key]) ) continue;
                $item[$key] = $this->parsing_integer_sting_to_array($item[$key], $item);
            }
            
            if (isset($item['meta_data']) && is_array($item['meta_data'])) {
                $item['meta_data'] = array_map(function ($meta) {
                    // Apply the decode function to the 'value' field if it exists
                    if (isset($meta['value'])) {
                        $meta['value'] = wbps_decode_if_json($meta['value']);
                    }
                    return $meta;
                }, $item['meta_data']);
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
        
        if( ! $value ) return $value;
        
        $return_value = $general_settings['categories_return_value'];
        $names_enabled = false;
        $tag_data = [];
        
        
        if( $return_value === 'object' ){
            $value = json_decode($value);
        } elseif($return_value === 'name'){
            $value = wbps_get_taxonomy_ids_by_names('product_cat', $value);
            $value = array_map( function($id){
                $item['id'] = $id;
                return $item;
            }, $value);
        } else {
            $value = explode('|', $value);
            $value = array_map( function($id){
                $item['id'] = trim($id);
                return $item;
            }, $value);
        }
        
        return $value;
    }
    
    
    // Categories|Tags Sheet ==> Site
    function product_extract_id_brands($value, $row, $general_settings){
        
        if( ! $value ) return $value;
        
        $return_value = isset($general_settings['brands_return_value']) ? $general_settings['brands_return_value'] : 'id';
        $names_enabled = false;
        $tag_data = [];
        
        
        if( $return_value === 'object' ){
            $value = json_decode($value);
        } elseif($return_value === 'name'){
            $value = wbps_get_taxonomy_ids_by_names('product_brand', $value);
            $value = array_map( function($id){
                $item['id'] = $id;
                return $item;
            }, $value);
        } else {
            $value = explode('|', $value);
            $value = array_map( function($id){
                $item['id'] = trim($id);
                return $item;
            }, $value);
        }
        
        return $value;
    }
    
    // Tags Sheet ==> Site
    function product_extract_id_tags($value, $row, $general_settings){
        
        if( ! $value ) return $value;
        
        $return_value = $general_settings['tags_return_value'];
        $names_enabled = false;
        $tag_data = [];
        
        
        if( $return_value === 'object' ){
            return json_decode($value);
        } elseif($return_value === 'name'){
            $value = wbps_get_taxonomy_ids_by_names('product_tag', $value);
            $value = array_map( function($id){
                $item['id'] = $id;
                return $item;
            }, $value);
        } else {
            $value = explode('|', $value);
            $value = array_map( function($id){
                $item['id'] = trim($id);
                return $item;
            }, $value);
        }
        
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
    
    function syncback_data_products($products, $header, $settings) {
        // Pre-fetch integer array fields and format required fields to avoid repeated function calls
        $integerArrayFields = wbps_fields_integer_array();
        $formatRequiredFields = wbps_fields_format_required();
        
        foreach ($products as &$product) {
            foreach ($product as $key => &$value) {
                $key = trim($key);
    
                // Apply basic filter
                $value = apply_filters("wcgs_products_syncback_value", $value, $key);
                
                if (in_array($key, $integerArrayFields, true)) {
                    // If key is in integer array fields, implode value if it's an array
                    $value = is_array($value) ? implode('|', $value) : $value;
                } elseif (isset($formatRequiredFields[$key])) {
                    // If key exists in format-required fields, apply formatting and filtering
                    $value = $value === null ? "" : $value;
                    $value = apply_filters("wcgs_products_syncback_value_{$key}", $value, $key, $settings);
                } elseif (is_array($value)) {
                    // If value is an array, encode it as JSON
                    $value = json_encode($value);
                }
            }
        }
        
        return $products;
    }

}

function init_wbps_format(){
	return WBPS_Format::__instance();
}