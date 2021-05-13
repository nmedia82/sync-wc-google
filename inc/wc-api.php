<?php
/**
 * WooCOmmerce API
 * */
 
  class WCGS_WC_API {
     
     function __construct() {
        //  Chilled 
     }
     
     
     function get_info(){
         
         print_r($this->woocommerce->get(''));
     }
     
     // Updating categories via WC API
     // return Rows for Google Sheet
     function update_categories_batch($data, $rowRef) {
         
        $errors_found = array();
        $googleSheetRow = array();
        //   wcgs_pa($data); exit;
        
        $request = new WP_REST_Request( 'POST', '/wc/v3/products/categories/batch' );
        $request->set_body_params( $data );
        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            $error = $response->as_error();
            set_transient('wcgs_rest_api_error', $error->get_error_message());
        } else{
            $response = $response->get_data();
            // wcgs_pa($response); exit;
             // Getting Rows to update Google Sheet
             if( isset($response['create']) ) {
                 foreach($response['create'] as $item){
                     
                     if( isset($item['error']) ) {
                        $errors_found[] = $item;
                     } else {
                        $item_name = sanitize_key($item['name']);
                        if( !isset($rowRef[$item_name]) ) continue;
                        $rowNo = $rowRef[$item_name];
                        $googleSheetRow[$rowNo] = [$item['id'], WCGS_SYNC_OK];
                     }
                     
                     do_action('wcgs_after_category_created', $item, $data, $rowNo);
                 }
             }
             
             if( isset($response['update']) ) {
                 foreach($response['update'] as $item){
                     
                     if( isset($item['error']) ) {
                        $errors_found[] = $item;
                     } else {
                        $rowNo = $rowRef[$item['id']];
                        $googleSheetRow[$rowNo] = [$item['id'], WCGS_SYNC_OK];
                     }
                     
                     do_action('wcgs_after_category_updated', $item, $data, $rowNo);
                 }
             }
        }
         
        if( count($errors_found) > 0 ) {
            set_transient('wcgs_batch_error', $errors_found);
        }
         
         ksort($googleSheetRow);
         do_action('wcgs_after_categories_updated', $googleSheetRow, $data);
        //   wcgs_pa($googleSheetRow);
         return $googleSheetRow;
     }
     
    // Updating products via WC API
    // return Rows for Google Sheet
    function update_products_batch($data, $gs_rows) {
     
    //  wcgs_pa($data);
     
    // product ids being created/udpated
    $product_ids = [];
    $googleSheetRow = array();
    $errors_found = array();
    
    $request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
    $request->set_body_params( $data );
    $response = rest_do_request( $request );
    if ( $response->is_error() ) {
        $error = $response->as_error();
        set_transient('wcgs_rest_api_error', $error->get_error_message());
    } else{
        $response = $response->get_data();
    
     // Getting Rows to update Google Sheet
         if( isset($response['create']) ) {
             foreach($response['create'] as $item){
                 
                 if( isset($item['error']) ) {
                    $errors_found[] = $item;
                 } else {
                    $rowNo = '';
                    foreach($item['meta_data'] as $metadata){
                        $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                        if($rowNo) break;
                    }
                    $googleSheetRow[$rowNo] = [$item['id'], 1];
                    $product_ids['update'][$rowNo] = $item['id'];
                 }
                 
                 do_action('wcgs_after_product_created', $item, $data, $rowNo);
             }
         }
         
         if( isset($response['update']) ) {
             foreach($response['update'] as $item){
                 
                 if( isset($item['error']) ) {
                    $errors_found[] = $item;
                 } else {
                    $rowNo = '';
                    foreach($item['meta_data'] as $metadata){
                        $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                        if($rowNo) break;
                    }
                    $googleSheetRow[$rowNo] = [$item['id'], 1];
                    $product_ids['update'][$rowNo] = $item['id'];
                 }
                 
                 do_action('wcgs_after_product_updated', $item, $data, $rowNo);
             }
         }
    }
     
     
    if( count($errors_found) > 0 ) {
        set_transient('wcgs_batch_error', $errors_found);
    }
     
     ksort($googleSheetRow);
     do_action('wcgs_after_products_updated', $googleSheetRow, $data, $product_ids);
    //   wcgs_pa($googleSheetRow); exit;
     return $googleSheetRow;
    }
     
     
     // Attaching Variations to product via WC API
     // return Rows for Google Sheet
     function update_variations_batch($variations, $gs_rows) {
         
        // ini_set('default_socket_timeout', 500);
         
        // variation ids being created/udpated
        $variation_ids = [];
        $rowNo = 0;
        $googleSheetRow = [];
         
        $errors_found = array();
        //   wcgs_pa($variations); exit;
        
        foreach($variations as $product_id => $variation) {
            
            $response = new stdClass;
            
            $request = new WP_REST_Request( 'POST', "/wc/v3/products/{$product_id}/variations/batch" );
            $request->set_body_params( $variation );
            $response = rest_do_request( $request );
            if ( $response->is_error() ) {
                $error = $response->as_error();
                set_transient('wcgs_rest_api_error', $product_id.':'.$error->get_error_message());
            } else{
                $response = $response->get_data();
                
                 // Getting Rows to update Google Sheet
                 if( isset($response['create']) ) {
                     foreach($response['create'] as $item){
                         
                         if( isset($item['error']) ) {
                            $errors_found[] = $item;
                         } else {
                            $rowNo = '';
                            foreach($item['meta_data'] as $metadata){
                                $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                                if($rowNo) break;
                            }
                            $googleSheetRow[$rowNo] = [$item['id'], 1];
                            $product_ids['update'][$rowNo] = $item['id'];
                         }
                         
                         do_action('wcgs_after_variation_created', $item, $data, $rowNo);
                     }
                 }
                 
                 if( isset($response['update']) ) {
                     foreach($response['update'] as $item){
                         
                         if( isset($item['error']) ) {
                            $errors_found[] = $item;
                         } else {
                            $rowNo = '';
                            foreach($item['meta_data'] as $metadata){
                                $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                                if($rowNo) break;
                            }
                            $googleSheetRow[$rowNo] = [$item['id'], 1];
                            $product_ids['update'][$rowNo] = $item['id'];
                         }
                         
                         do_action('wcgs_after_variation_updated', $item, $data, $rowNo);
                     }
                 }
            }
        }
         
        if( count($errors_found) > 0 ) {
            set_transient('wcgs_batch_error', $errors_found);
        }
         
         ksort($googleSheetRow);
         do_action('wcgs_after_variations_updated', $googleSheetRow, $variations, $variation_ids);
         // wcgs_pa($googleSheetRow);
         return $googleSheetRow;
     }
     
     
    // Updating products via WC API
    // return Rows for Google Sheet
    function update_orders_batch($data, $gs_rows) {
     
    //  wcgs_pa($data);
     
    // product ids being created/udpated
    $product_ids = [];
    $googleSheetRow = array();
    $errors_found = array();
    
    $request = new WP_REST_Request( 'POST', '/wc/v3/orders/batch' );
    $request->set_body_params( $data );
    $response = rest_do_request( $request );
    if ( $response->is_error() ) {
        $error = $response->as_error();
        set_transient('wcgs_rest_api_error', $error->get_error_message());
    } else{
        $response = $response->get_data();
    
     // Getting Rows to update Google Sheet
         if( isset($response['create']) ) {
             foreach($response['create'] as $item){
                 
                 if( isset($item['error']) ) {
                    $errors_found[] = $item;
                 } else {
                    $rowNo = '';
                    foreach($item['meta_data'] as $metadata){
                        $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                        if($rowNo) break;
                    }
                    $googleSheetRow[$rowNo] = [$item['id'], 1];
                    $product_ids['update'][$rowNo] = $item['id'];
                 }
                 
                 do_action('wcgs_after_order_created', $item, $data, $rowNo);
             }
         }
         
         if( isset($response['update']) ) {
             foreach($response['update'] as $item){
                 
                 if( isset($item['error']) ) {
                    $errors_found[] = $item;
                 } else {
                    $rowNo = '';
                    foreach($item['meta_data'] as $metadata){
                        $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                        if($rowNo) break;
                    }
                    $googleSheetRow[$rowNo] = [$item['id'], 1];
                    $order_ids['update'][$rowNo] = $item['id'];
                 }
                 
                 do_action('wcgs_after_order_updated', $item, $data, $rowNo);
             }
         }
    }
     
     
    if( count($errors_found) > 0 ) {
        set_transient('wcgs_batch_error', $errors_found);
    }
     
     ksort($googleSheetRow);
     do_action('wcgs_after_orders_updated', $googleSheetRow, $data, $order_ids);
    //   wcgs_pa($googleSheetRow); exit;
     return $googleSheetRow;
    }
    
    
    // Updating customers via WC API
    // return Rows for Google Sheet
    function update_customers_batch($data, $gs_rows) {
     
    //  wcgs_pa($data);
     
    // product ids being created/udpated
    $product_ids = [];
    $googleSheetRow = array();
    $errors_found = array();
    
    $request = new WP_REST_Request( 'POST', '/wc/v3/customers/batch' );
    $request->set_body_params( $data );
    $response = rest_do_request( $request );
    if ( $response->is_error() ) {
        $error = $response->as_error();
        set_transient('wcgs_rest_api_error', $error->get_error_message());
    } else{
        $response = $response->get_data();
    
     // Getting Rows to update Google Sheet
         if( isset($response['create']) ) {
             foreach($response['create'] as $item){
                 
                 if( isset($item['error']) ) {
                    $errors_found[] = $item;
                 } else {
                    $rowNo = '';
                    foreach($item['meta_data'] as $metadata){
                        $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                        if($rowNo) break;
                    }
                    $googleSheetRow[$rowNo] = [$item['id'], 1];
                    $product_ids['update'][$rowNo] = $item['id'];
                 }
                 
                 do_action('wcgs_after_customer_created', $item, $data, $rowNo);
             }
         }
         
         if( isset($response['update']) ) {
             foreach($response['update'] as $item){
                 
                 if( isset($item['error']) ) {
                    $errors_found[] = $item;
                 } else {
                    $rowNo = '';
                    foreach($item['meta_data'] as $metadata){
                        $rowNo = $metadata->key == 'wcgs_row_id' ? $metadata->value : '';
                        if($rowNo) break;
                    }
                    $googleSheetRow[$rowNo] = [$item['id'], 1];
                    $customer_ids['update'][$rowNo] = $item['id'];
                 }
                 
                 do_action('wcgs_after_customer_updated', $item, $data, $rowNo);
             }
         }
    }
     
     
    if( count($errors_found) > 0 ) {
        set_transient('wcgs_batch_error', $errors_found);
    }
     
     ksort($googleSheetRow);
     do_action('wcgs_after_customers_updated', $googleSheetRow, $data, $customer_ids);
    //   wcgs_pa($googleSheetRow); exit;
     return $googleSheetRow;
    }
         
     // get category for googlesheet row
     function get_category_for_gsheet($id, $sync='OK'){
        
        $request = new WP_REST_Request( 'GET', "/wc/v3/products/categories/{$id}" );
        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            // Log here
            return '';
        }
        
        $item = $response->get_data();
        $category = new WCGS_Categories();
        
        // Legacy
        $header = $category->get_header();
        
        $header = wcgs_get_sheet_info('categories', 'header_data');
        if($header){
            $header = array_fill_keys($header, '');
        }
        
        $combined_cat = array_replace($header, array_intersect_key($item, $header));
        $combined_cat['sync'] = 'OK';
        
        // wcgs_pa($combined_cat); exit;
        return apply_filters('wcgs_category_update_row', array_values($combined_cat), $id);
     }
     
     // get product for googlesheet row
     function get_product_for_gsheet($id){
         
        $request = new WP_REST_Request( 'GET', "/wc/v3/products/{$id}" );
        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            // Log here
            return '';
        }
         
        $item = $response->get_data();
        $product = new WCGS_Products();
        $header = $product->get_header();
        
        $header = array_fill_keys($header, '');
        $combined_product = array_replace($header, array_intersect_key($item, $header));
        $combined_product['sync'] = 'OK';
        
        foreach(wcgs_fields_format_required() as $key => $type){
            
            if( !isset($combined_product[$key]) ) continue;
            
            $combined_product[$key] = apply_filters("wcgs_products_syncback_value_{$key}", $combined_product[$key], $key);
        }
        
        // wcgs_pa($combined_product); exit;
        return apply_filters('wcgs_product_update_row', $combined_product, $id);
     }
     
     // get categories for sync-back
     function get_categories_for_syncback(){
         
         // Getting categories IDs not syncs
         $args = array(
            'hide_empty' => false,
            'meta_query' => array(
                array(
                     'key' => 'wcgs_row_id',
                     'compare' => 'NOT EXISTS'
                  ),
            ),
            'taxonomy'  => 'product_cat',
            );
            
        $categories_notsync = get_terms( $args );
         
        $include_categories = [];
        foreach($categories_notsync as $c){
             $include_categories[] = $c->term_id;
        }
        
        $chunk_size = wcgs_get_chunk_size();
        
        $chunks          = array_chunk($include_categories, $chunk_size);
        $chunkedResponse = [];
        
        foreach ($chunks as $chunk) {
            
            $args              = apply_filters('wcgs_export_categories_args',
                                ['per_page' => $chunk_size, 'include' => $chunk]);
                
            $request = new WP_REST_Request( 'GET', '/wc/v3/products/categories' );
            $request->set_query_params( $args );
            $response = rest_do_request( $request );
            if ( ! $response->is_error() ) {
                $chunkedResponse[] = $response->get_data();
            }
        }
        
        $items = array_merge(...$chunkedResponse);
        unset($chunkedResponse, $chunks, $chunk);
        
        $categories = new WCGS_Categories();
        $header = $categories->get_header();
        //  wcgs_pa($items); exit;
         
         $categories = array();
         foreach($items as $item) {
             
             $product_row = array();
             if( $header ) {
                 foreach($header as $key => $index) {
                     
                    if( $key == 'auto_sync' ) continue;
                    switch($key){
                        case 'sync':
                            $value = 'OK';
                            break;
                        case 'last_sync':
                            $value = date('Y-m-d h:i:sa', time());
                            break;
                        default:
                            $value = is_array($item[trim($key)]) ? json_encode($item[trim($key)]) : $item[trim($key)];
                            break;
                    }
                    
                    $value = $value === NULL ? '' : $value;
                    $value = apply_filters("wcgs_categories_syncback_value", $value, $key, $index);
                    $product_row[] = apply_filters("wcgs_categories_syncback_value_{$key}", $value, $key, $index);
                 }
             }
             
             $categories[] = $product_row;
         }
        
        // wcgs_pa($categories); exit;
        return apply_filters('wcgs_categories_synback', $categories);
     }
     
     
     // get categories for sync-back (already synced)
     function get_synced_categories_for_syncback(){
         
         // Getting categories IDs not syncs
         $args = array(
            'hide_empty' => false,
            'meta_query' => array(
                array(
                     'key' => 'wcgs_row_id',
                     'compare' => 'EXISTS'
                  ),
            ),
            'taxonomy'  => 'product_cat',
            );
            
        $categories_synced = get_terms( $args );
         
        // $include_categories = [];
        // foreach($categories_notsync as $c){
        //      $include_categories[] = $c->term_id;
        // }
        
        // $chunk_size = wcgs_get_chunk_size();
        
        // $chunks          = array_chunk($include_categories, $chunk_size);
        // $chunkedResponse = [];
        
        // foreach ($chunks as $chunk) {
            
        //     $args              = apply_filters('wcgs_export_categories_args',
        //                         ['per_page' => $chunk_size, 'include' => $chunk]);
                
        //     $request = new WP_REST_Request( 'GET', '/wc/v3/products/categories' );
        //     $request->set_query_params( $args );
        //     $response = rest_do_request( $request );
        //     if ( ! $response->is_error() ) {
        //         $chunkedResponse[] = $response->get_data();
        //     }
        // }
        
        // $items = array_merge(...$chunkedResponse);
        // unset($chunkedResponse, $chunks, $chunk);
        
        return $categories_synced;
     }
     
     // get products for sync-back
     function get_products_for_syncback($included_products){
         
        //  $included_products = [1604];
         
        $chunk_size = wcgs_syncback_get_chunk_size();
        $items = [];
        
        $args              = apply_filters('wcgs_export_products_args',
                            ['per_page' => $chunk_size, 'include' => $included_products]);
            
        $request = new WP_REST_Request( 'GET', '/wc/v3/products' );
        $request->set_query_params( $args );
        $response = rest_do_request( $request );
        if ( ! $response->is_error() ) {
            $items = $response->get_data();
        }
        
        $product = new WCGS_Products();
        
        $header  = apply_filters('wcgs_page_header_data', $product->get_header());
        if( !$header ) {
            return new WP_Error( 'header_not_found', __( "Oops, you have to sync first.", "wcgs" ) );
        }
        
        $items = apply_filters('wcgs_products_list_before_syncback', $items);
        
        //  wcgs_log($header);
         
         $products = array();
         foreach($items as $item) {
             
             $product_row = array();
             if( $header ) {
                 foreach($header as $key => $index) {
                     
                    $value = $item[trim($key)];
                    
                    // if type is not set then it is a variation
                    $is_variation = !isset($item['type']) ? true : false;
             
                    switch($key){
                        case 'sync':
                            $value = '';
                            break;
                        case 'last_sync':
                            $value = date('Y-m-d h:i:sa', time());
                            break;
                        case 'featured':
                            $value = $value ? 'TRUE' : 'FALSE';
                            break;
                        case 'description':
                            $value = apply_filters('the_conent', $value);
                            $char_count = strlen($value);
                            if( $char_count >= 45000){
                                $value = '';
                            }
                            break;
                        
                        default:
                            $value = is_array($value) ? json_encode($value) : $value;
                            break;
                    }
                    
                    $value = $value === NULL ? '' : $value;
                    $value = apply_filters("wcgs_products_syncback_value", $value, $key, $index);
                    $product_row[] = apply_filters("wcgs_products_syncback_value_{$key}", $value, $key, $index);
                 }
             }
             
             $products[] = $product_row;
         }
        //  $product_row = [$item->id, 1, $item->name, $item->slug, $item->parent, $item->description, $item->display, '', $item->menu_order];
        // wcgs_pa($products); exit;
        return apply_filters('wcgs_products_synback', $products, $included_products);
     }
     
     // get variations for sync-back
     function get_variations_for_syncback($variable_products){
        
        // wcgs_pa($variable_products); exit;
        $variations_found = [];
        foreach($variable_products as $product_id){
            $request = new WP_REST_Request( 'GET', "/wc/v3/products/$product_id/variations" );
            $request->set_query_params( $args );
            $response = rest_do_request( $request );
            if ( ! $response->is_error() ) {
                $items = $response->get_data();
                $variations_found[$product_id] = $items;
            }
         }
         
         $product = new WCGS_variations();
         $header = $product->get_header();
        //  wcgs_pa($header); exit;
         
         $variations = array();
         foreach($variations_found as $parent_id => $items) {
             
             foreach($items as $item) {
                $product_row = array();
                 if( $header ) {
                     foreach($header as $key => $index) {
                         
                        // ignore last_sync for now
                        // if( $key == 'last_sync' ) continue;
                        
                        switch($key){
                            case 'sync':
                                $value = 1;
                                break;
                            case 'last_sync':
                                $value = wcgs_get_last_sync_date();
                                break;
                            case 'dimensions':
                                $value = json_encode($item[trim($key)]);
                                break;
                            case 'image':
                                $value = $item[trim($key)];
                                if($value){
                                    $image_from = get_option('wcgs_image_import');
                                    $value = $value[$image_from];
                                }
                                break;
                            case 'product_id':
                                $value = $parent_id;
                                break;
                            default:
                                $value = is_array($item[trim($key)]) ? json_encode($item[trim($key)]) : $item[trim($key)];
                                break;
                        }
                        
                        $value = $value === NULL ? '' : $value;
                        $value = apply_filters("wcgs_variations_syncback_value", $value, $key, $index);
                        $product_row[] = apply_filters("wcgs_variations_syncback_value_{$key}", $value, $key, $index);
                     }
                 }
                 
                 $variations[] = $product_row;
             }
             
             
         }
        //  $product_row = [$item->id, 1, $item->name, $item->slug, $item->parent, $item->description, $item->display, '', $item->menu_order];
        //  wcgs_pa($variations);
        return apply_filters('wcgs_variations_synback', $variations, $variable_products);
     }
     
 }