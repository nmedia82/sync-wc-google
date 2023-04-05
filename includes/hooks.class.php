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
    
    function modify_webhook_payload($payload, $resource, $resource_id, $event_type) {
      // Modify the payload data here
      $payload['sheet_props']   = get_option('wbps_sheet_props');
      $product_id               = $payload['id'];
      $payload['row_no']        = get_post_meta($product_id,'wbps_row_id', true);
    //   wbps_logger_array($payload);
      return $payload;
    }
}

function init_wpbs_hooks(){
	return WBPS_Hooks::__instance();
}