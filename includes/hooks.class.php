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
        // add_filter('woocommerce_webhook_payload', [$this, 'modify_webhook_payload'], 10, 4);
       
        // when product is updated in wc
        add_action( 'woocommerce_update_product', function($product_id){
            
            if ( $_SERVER['REQUEST_URI'] === '/wp-json/wbps/v1/product-sync' ) {
                return;
            }
            
            
            $wc_product = wc_get_product( $product_id );
            $wbps_row_id = $wc_product->get_meta( 'wbps_row_id' );
            if ( $wbps_row_id ) {
                $this->trigger_webhook_on_product_update($product_id);
            }
            
        }, 10, 1 );
        
        
       add_action( 'save_post_product', function( $post_id, $post, $update ) {
            
            // Check if this is an auto-save
            if ( wp_is_post_autosave( $post_id ) ) {
                return;
            }
            
            
            if ( $_SERVER['REQUEST_URI'] === '/wp-json/wbps/v1/product-sync' ) {
                return;
            }

        	// If an old book is being updated, exit
        	if ( $update ) {
        		return '';
        	}
        	
            $this->trigger_webhook_on_new_product($post_id);
        
        }, 10, 3 );


        
        add_action( 'transition_post_status', [$this, 'handle_product_trashed'], 10, 3 );
        
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
    
        $sheet_properties = get_option('wbps_sheet_props');
        
        if( !$sheet_properties ) return $products;
        
        if( !isset($sheet_properties['product_mapping']) ) return $products;
        
        $product_mapping = json_decode($sheet_properties['product_mapping'], true);
        
        if( !$product_mapping ) return $products;
        // now getting only custom fields
        $filtered_array = array_filter($product_mapping, function($item) {
            return $item['source'] === 'custom';
        });
        
        $custom_keys = array_column($filtered_array, 'key');
        
        if( !$custom_keys ) return $products;
        
        $custom_keys = array_map('trim', $custom_keys);
        // extract only meta data columns
        $meta_column_found = array_intersect($custom_keys, array_keys($header_data));
        if( $meta_column_found ) {
          
            $products = array_map(function($p) use ($meta_column_found){
            
            $meta_cols = [];
            foreach($meta_column_found as $meta_col){
              
              $p[$meta_col] = wbps_get_product_meta_col_value($p, $meta_col);
              
            }
            return $p;
            
          }, $products);
        }
        
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
    
    function build_payload_for_webhook($product) {
        
        $sheet_props    = get_option('wbps_sheet_props');
        if( !$sheet_props ) return;
        
        unset($sheet_props['product_mapping']); // removing overloaded data
        unset($sheet_props['webhook_status']); // removing overloaded data
        
        $header   = json_decode($sheet_props['header']);
        
        // $products_ins = init_wbps_products();
        // $response = $products_ins::fetch($chunk, $header, $sheet_props, $last_row);
        
        $header = array_fill_keys($header, '');
        $items = [$product];
        
        // adding variation and meta_data based on this hook
        $items = apply_filters('wbps_products_list_before_syncback', $items, $header);
        
        $sortby_id = array_column($items, 'id');
        array_multisort($sortby_id, SORT_ASC, $items);
        
        $header['sync'] = 'OK';
        $items = array_map(function($data) use($header){
            return array_replace($header, array_intersect_key($data, $header));
        }, $items);
        
        $settings_default = ['categories_return_value'=>'id',
                            'tags_return_value'=>'id',
                            'images_return_value'=>'id',
                            'image_return_value'=>'id'];
                            
        // $settings = array_intersect_key($sheet_props, array_flip($settings_keys));
        $settings = isset($sheet_props['settings']) ? json_decode($sheet_props['settings'], true) : $settings_default;
        // wbps_logger_array($settings);
        $items = apply_filters('wbps_products_synback', $items, $header, $settings);
        
        
        $items = array_reduce($items, function($result, $item) {
            $row_id = get_post_meta($item['id'], 'wbps_row_id', true);
            $result[$row_id] = array_values($item);
            return $result;
        }, []);
        
        $payload_new['row_id']  = get_post_meta($product['id'],'wbps_row_id', true);
        $payload_new['rows']     = $items;
        $payload_new['product_id']     = $product['id'];
        $payload_new['sheet_props']     = $sheet_props;

        return $payload_new;
    }
    
    
    function handle_product_trashed( $new_status, $old_status, $post ) {
        
        if ( $_SERVER['REQUEST_URI'] === '/wp-json/wbps/v1/product-sync' ) {
            return;
        }
    
        if ( 'product' !== $post->post_type ) {
            return;
        }
    
        if ( 'trash' === $new_status ) {
            // Product is trashed
            $this->trigger_webhook_on_product_trash($post->ID);
        }
    }

    function trigger_webhook_on_new_product( $post_id ) {
        
        $endpoint_url = wbps_get_webapp_url();
        if( !$endpoint_url ) return;
        
        $wc_product = wc_get_product( $post_id );
    
        // Check if the post is a product
        if ( $wc_product ) {
            $endpoint_url = add_query_arg( array(
                'event_type' => 'product_created',
                'sheet_name' => 'products'
            ), $endpoint_url );
    
            
            // because WC API is not ready when a new product is created
            $response = $wc_product->get_data();
            
            /**
            * since we are pulling variation via wc_get_products (not with WC API)
            * Some keys are not matched like image_id is returned instead of image
            **/
            $response = $wc_product->get_data();
            $response['image']      = $response['image_id'];
            $response['images']     = $response['gallery_image_ids'];
            $response['categories'] = $response['category_ids'];
            $response['tags']       = $response['tag_ids'];
            $response['permalink']  = get_permalink( $post_id );
            $response['type']       = $wc_product->get_type();
            $response['price']       = $wc_product->get_price();
            
    
            $payload = $this->build_payload_for_webhook( $response );
            // return wbps_logger_array($payload);
            
            // Send the webhook request
            $response = wp_remote_post( $endpoint_url, array(
              'method' => 'POST',
              'headers' => array( 'Content-Type' => 'application/json' ),
              'body' => json_encode( $payload ),
            ) );
    
            // Log the response
            if ( is_wp_error( $response ) ) {
              wbps_logger_array( 'Webhook Created failed: ' . $response->get_error_message() );
            } else {
            //   wbps_logger_array( 'Webhook Ok - Created: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }
    
    function trigger_webhook_on_product_update( $post_id ) {
        
        $endpoint_url = wbps_get_webapp_url();
        
        if( !$endpoint_url ) return;
        
        $wc_product = wc_get_product( $post_id );
        
        // Check if the post is a product
        if ( $wc_product ) {
            $endpoint_url = add_query_arg( array(
                'event_type' => 'product_updated',
                'sheet_name' => 'products'
            ), $endpoint_url );
            
            // wbps_logger_array($endpoint_url);
            $request = new WP_REST_Request( 'GET', '/wc/v3/products/'.$post_id );
            $request->set_body_params( $data );
            $response = @rest_do_request( $request );
            
            if ( $response->is_error() ) {
                $error = $response->as_error();
                return new WP_Error( 'wcapi_batch_product_error', $error->get_error_message() );
            } else{
                $response = $response->get_data();
            }
            
            $payload = $this->build_payload_for_webhook( $response );
            // wbps_logger_array(json_encode( $payload ));
            
            // Send the webhook request
            $response = wp_remote_post( $endpoint_url, array(
              'method' => 'POST',
              'headers' => array( 'Content-Type' => 'application/json' ),
              'body' => json_encode( $payload ),
            ) );
            
            // Log the response
            if ( is_wp_error( $response ) ) {
              wbps_logger_array( 'Webhook on Update failed: ' . $response->get_error_message() );
            } else {
              wbps_logger_array( 'Webhook Ok - Updated: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }
    
    
    function trigger_webhook_on_product_trash( $post_id ) {
        
        $endpoint_url = wbps_get_webapp_url();
        if( !$endpoint_url ) return;
        $wc_product = wc_get_product( $post_id );
        
        // Check if the post is a product
        if ( $wc_product ) {
            $endpoint_url = add_query_arg( array(
                'event_type' => 'product_deleted',
                'sheet_name' => 'products'
            ), $endpoint_url );
            
            
            $request = new WP_REST_Request( 'GET', '/wc/v3/products/'.$post_id );
            $request->set_body_params( $data );
            $response = @rest_do_request( $request );
            // wbps_logger_array($response);
            
            if ( $response->is_error() ) {
                $error = $response->as_error();
                return new WP_Error( 'wcapi_delete_product_error', $error->get_error_message() );
            } else{
                $response = $response->get_data();
            }
            
            $payload = $this->build_payload_for_webhook( $response );
            
            
            // Send the webhook request
            $response = wp_remote_post( $endpoint_url, array(
              'method' => 'POST',
              'headers' => array( 'Content-Type' => 'application/json' ),
              'body' => json_encode( $payload ),
            ) );
            
            // Log the response
            if ( is_wp_error( $response ) ) {
              wbps_logger_array( 'Webhook Delete failed: ' . $response->get_error_message() );
            } else {
            //   wbps_logger_array( 'Webhook Ok - Deleted: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }


}

function init_wpbs_hooks(){
	return WBPS_Hooks::__instance();
}