<?php
/**
 * WP Hooks
 * Created Date: January 10, 2022
 * Created By: Ben Rider
 * */
 
class WBPS_Hooks {
    
    private static $ins = null;
	
	public static function __instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
    
    function __construct(){
        
        // Adding variations into products lists
        add_filter('wbps_products_list_before_syncback', [$this, 'add_variations'], 11, 2);
        add_filter('wbps_products_list_before_syncback', [$this, 'add_meta_columns'], 21, 2);
        
        add_action('wcgs_after_categories_synced', [$this, 'categories_row_update']);
        
        add_action('wbps_after_categories_synced', [$this, 'link_category_with_sheet'], 11, 1);
        
        // modify webhook before it trigger, added sheets properties
        add_filter('woocommerce_webhook_payload', [$this, 'modify_webhook_payload'], 10, 4);
        
    }
    
    
    function categories_row_update($rowRef) {
 
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
    
    // Add variation before syncback via hook
    function add_variations($products, $header){
        
      
        // $header  = apply_filters('wcgs_page_header_data', $sheet_info['header_data']);
        // $header = array_fill_keys($header, '');
        
        $variable_products = array_filter($products, function($product){
                    return $product['type'] == 'variable';
                  });
        
        // Variations
        $variations = [];
        foreach($variable_products as $index => $item){
            
                $product_variations = wc_get_products(
          			array(
          				'parent' => $item['id'],
          				'type'   => array( 'variation' ),
          				'return' => 'array',
          				'limit'  => -1,
          			)
          		);
          		
          		foreach($product_variations as $variation){
          		  
          		    $variation_data = $variation->get_data();
          		    
          		    /**
          		     * since attributes returned does not have name or id keys
          		     * we are adding here
          		    */
          		    $variation_data['attributes'] = array_map(function($key, $value) {
                        return array(
                            'name' => $key,
                            'option' => $value
                        );
                    }, array_keys($variation_data['attributes']), $variation_data['attributes']);
          		    // wbps_logger_array($variation_data);
          		    
          		    $variation_data['type'] = 'variation';
          		    
          		    /**
          		     * since we are pulling variation via wc_get_products (not with WC API)
          		     * Some keys are not matched like image_id is returned instead of image
          		     **/
          		    $variation_data['image'] = $variation_data['image_id'];
          		    $variations[] = $variation_data;
          		}
        }
        
        // wbps_logger_array($variations);
        $combined_arr = array_merge($products, $variations);
        return $combined_arr;
    }
  
    // Adding meta columns if found
    function add_meta_columns($products, $header_data){
    
        $meta_keys = get_option('wcgs_metadata_keys');
        if( !$meta_keys ) return $products;
        
        // getting the allowed meta keys and converting to array
        $meta_array = explode(',', $meta_keys);
        $meta_array = array_map('trim', $meta_array);
        // extract only meta data columns
        $meta_column_found = array_intersect($meta_array, $header_data);
        if( $meta_column_found ) {
          
            $products = array_map(function($p) use ($meta_column_found){
            
            $meta_cols = [];
            foreach($meta_column_found as $meta_col){
              
              $p[$meta_col] = wcgs_get_product_meta_col_value($p, $meta_col);
              
            }
            return $p;
            
          }, $products);
        }
        
        // wbps_logger_array($products);
        return $products;
        
    }
    
    // Linking categories with sheet row
    function link_category_with_sheet($rowRef) {
     
        if( count($rowRef) <= 0 ) return;
        
        global $wpdb;
        $termmeta_table = $wpdb->prefix.'termmeta';
        
        $wpsql = "INSERT INTO {$termmeta_table} (term_id,meta_key,meta_value) VALUES ";
        $delqry = "DELETE FROM {$termmeta_table} WHERE term_id IN (";
        $metakey = 'wbps_row_id';
        
        foreach($rowRef as $ref){
            
            if( $ref['row'] == 'ERROR' ) continue;
            
            $termid = $ref['id'];    // term id
            $metaval = $ref['row'];
            
            // Delete existing terms meta if any
            $delqry .= "{$termid},";
            // Term meta sql
            $wpsql .= "({$termid}, '{$metakey}', '{$metaval}'),";
        
        }
        
        // Delete query
        $delqry = rtrim($delqry, ',');
        $delqry .= ") AND meta_key='{$metakey}'";
        $wpdb->query($delqry);
        
        //insert query
        $wpsql = rtrim($wpsql, ',');
        
        // wbps_logger_array($wpsql);
        
        $wpdb->query($wpsql);
    }
    
    function modify_webhook_payload($payload, $resource, $resource_id, $webhook_id) {
        
        if( $resource !== 'product' ) return $payload;
        $sheet_props    = get_option('wbps_sheet_props');
        unset($sheet_props['product_mapping']); // removing overloaded data
        unset($sheet_props['webhook_status']); // removing overloaded data
        
        // in case of delete
        if(count($payload) === 1){
            $payload_new['row_id']  = get_post_meta($payload['id'],'wbps_row_id', true);
            $payload_new['sheet_props']     = $sheet_props;
            // wbps_logger_array($payload_new);
            return $payload_new;
        }
    
        $sheet_header   = json_decode($sheet_props['header']);
        
        // Get only the keys from $payload that exist in $sheet_header
        $payload_keys = array_intersect($sheet_header, array_keys($payload));
       
        $sheet_header = array_flip($sheet_header);
        $sheet_header['sync'] = 'OK';
        
        // Create a new array that has the keys from $sheet_header in the order they appear in $sheet_header, and the values from the corresponding keys in $payload
        $ordered_payload = array_merge($sheet_header, array_intersect_key($payload, array_flip($payload_keys)));

        $items = [$ordered_payload];
        
        $settings_keys = ['categories_return_value','tags_return_value','images_return_value','image_return_value'];
        $settings = array_intersect_key($sheet_props, array_flip($settings_keys));
        
        $items = apply_filters('wbps_products_synback', $items, $header, $settings);
        $payload_new['row_id']  = get_post_meta($payload['id'],'wbps_row_id', true);
        $payload_new['row']     = array_map('array_values', $items);
        $payload_new['product_id']     = $payload['id'];
        $payload_new['sheet_props']     = $sheet_props;

        return $payload_new;
    }
}

function init_wpbs_hooks(){
	return WBPS_Hooks::__instance();
}