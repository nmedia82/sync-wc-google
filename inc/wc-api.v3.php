<?php
/**
 * WooCOmmerce API Live Updates
 * */
 
class WCGS_WC_API_V3 {

  function __construct() {
    
    // Adding variations into products lists
    add_filter('wcgs_products_list_before_syncback', array($this, 'add_variations'), 11, 2);
    add_filter('wcgs_products_list_before_syncback', array($this, 'add_meta_columns'), 21, 2);
    
    // Meta query extends to fetch the products for syncback chunking
    $syncback_setting = get_option('wcgs_syncback_settings');
    if( $syncback_setting == 'not_linked' ){
      add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'wcgs_product_meta_query', 10, 3 );
    }
      
  }
  
  function batch_update_products($data) {
    
    $request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
    $request->set_body_params( $data );
    $response = @rest_do_request( $request );
    
    if ( $response->is_error() ) {
        $error = $response->as_error();
        return new WP_Error( 'wcapi_batch_product_error', $error->get_error_message() );
    } else{
        $response = $response->get_data();
        
        $result1 = $result2 = [];
        if( isset($response['update']) ) {
             $result1 = array_map(function($item){
               
                  if( isset($item['error']) )
                    return ['row'=>'ERROR','id'=>$item['id'], 'message'=>$item['error']['message']];
                
                  $row_id_meta = array_filter($item['meta_data'], function($meta){
                    return $meta->key == 'wcgs_row_id';
                  });
                  
                  $row_id_meta = reset($row_id_meta);
                  $row_id = $row_id_meta->value;
                  $images_ids = array_column($item['images'],'id');
                  return ['row'=>$row_id, 'id'=>$item['id'], 'images'=>implode('|',$images_ids)];
                    
             }, $response['update']);
             
            // wcgs_log($result);
        }
        
        if( isset($response['create']) ) {
             $result2 = array_map(function($item){
               
                  if( isset($item['error']) )
                    return ['row'=>'ERROR','id'=>$item['id'], 'message'=>$item['error']['message']];
                
                  $row_id_meta = array_filter($item['meta_data'], function($meta){
                    return $meta->key == 'wcgs_row_id';
                  });
                  
                  $row_id_meta = reset($row_id_meta);
                  $row_id = $row_id_meta->value;
                  $images_ids = array_column($item['images'],'id');
                  return ['row'=>$row_id, 'id'=>$item['id'], 'images'=>implode('|',$images_ids)];
                    
             }, $response['create']);
             
        }
        
        // wcgs_log($result);
        return array_merge($result1, $result2);
    }
  }
  
  // Batch Categories Update
  function batch_update_categories($data, $rowRef) {
    
    $request = new WP_REST_Request( 'POST', '/wc/v3/products/categories/batch' );
    $request->set_body_params( $data );
    $response = @rest_do_request( $request );

    if ( $response->is_error() ) {
        $error = $response->as_error();
        return new WP_Error( 'wcapi_batch_categories_error', $error->get_error_message() );
    } else{
        $response = $response->get_data();
        
        $result1 = $result2 = [];
        if( isset($response['update']) ) {
             $result1 = array_map(function($item) use($rowRef){
               
                  if( isset($item['error']) )
                    return ['row'=>'ERROR','id'=>$item['id'], 'message'=>$item['error']['message']];
                
                  $row_id = $rowRef[$item['id']];
                  $image_id = isset($item['image']['id']) ? $item['image']['id'] : null; 
                  return ['row'=>$row_id, 'id'=>$item['id'], 'image'=>$image_id];
                    
             }, $response['update']);
             
            // wcgs_log($result);
        }
        
        if( isset($response['create']) ) {
             $result2 = array_map(function($item) use($rowRef){
               
                  if( isset($item['error']) )
                    return ['row'=>'ERROR','id'=>$item['id'], 'message'=>$item['error']['message']];
                
                  $item_name = sanitize_key($item['name']);
                  $row_id = isset($rowRef[$item_name]) ? $rowRef[$item_name] : '';
                  $image_id = isset($item['image']['id']) ? $item['image']['id'] : null; 
                  return ['row'=>$row_id, 'id'=>$item['id'], 'image'=>$image_id];
                    
             }, $response['create']);
             
        }
        
        // wcgs_log($result);
        $results = array_merge($result1, $result2);
        //This action is used to update category meta for wcgs_row_id
        do_action('wcgs_after_categories_synced_v3', $results);
        return $results;
    }
  }
  
  
  // Variations Updating/Syncing Batch
  function batch_update_variations($variations) {
         
        $all_result = [];
        foreach($variations as $product_id => $variation) {
            
            $response = new stdClass;
            
            $request = new WP_REST_Request( 'POST', "/wc/v3/products/{$product_id}/variations/batch" );
            $request->set_body_params( $variation );
            $response = rest_do_request( $request );
            if ( $response->is_error() ) {
                $error = $response->as_error();
                return new WP_Error( 'wcapi_batch_variation_error', $error->get_error_message() );
            } else{
              $response = $response->get_data();
                // wcgs_log($response);
              
              $result1 = $result2 = [];
              if( isset($response['update']) ) {
                   $result1 = array_map(function($item){
                     
                        if( isset($item['error']) )
                          return ['row'=>'ERROR','id'=>$item['id'], 'message'=>$item['error']['message']];
                      
                        $row_id_meta = array_filter($item['meta_data'], function($meta){
                          return $meta->key == 'wcgs_row_id';
                        });
                        
                        $row_id_meta = reset($row_id_meta);
                        $row_id = $row_id_meta->value;
                        $image_id = isset($item['image']['id']) ? $item['image']['id'] : null; 
                        return ['row'=>$row_id, 'id'=>$item['id'], 'image'=>$image_id];
                          
                   }, $response['update']);
                   
              }
              
              if( isset($response['create']) ) {
                   $result2 = array_map(function($item){
                     
                        if( isset($item['error']) )
                          return ['row'=>'ERROR','id'=>$item['id'], 'message'=>$item['error']['message']];
                      
                        $row_id_meta = array_filter($item['meta_data'], function($meta){
                          return $meta->key == 'wcgs_row_id';
                        });
                        
                        $row_id_meta = reset($row_id_meta);
                        $row_id = $row_id_meta->value;
                        $image_id = isset($item['image']['id']) ? $item['image']['id'] : null; 
                        return ['row'=>$row_id, 'id'=>$item['id'], 'image'=>$image_id];
                          
                   }, $response['create']);
                   
              }
          }
          
          $all_result = array_merge($result1, $result2);
      }
      
      return $all_result;
  }
  
  
  // Fetch products
  // get products for sync-back
  function get_products_for_syncback($sheet_info){
   
    $header     = $sheet_info['header_data'];
    $chunk_size = $sheet_info['chunk_size'];
    $sheet_name = $sheet_info['sheet_name'];
    
    $chunk = 0;
    $include_products = [];
    if( isset($sheet_info['request_args']['chunk']) ) {
        $chunk      = intval($sheet_info['request_args']['chunk']);
        $saved_chunked = get_option("wcgs_{$sheet_name}_syncback_chunk");
    
        $response = array();
        if( !isset($saved_chunked[$chunk]) ) {
            $response['status'] = 'error';
            $response['message'] = __("No chunk found to sync","wcgs");
            return $response;
        }
        
        $include_products = $saved_chunked[$chunk];
    }else if( isset($sheet_info['request_args']['ids']) ) {
        $include_products = $sheet_info['request_args']['ids'];
    }else if( isset($sheet_info['request_args']['new_only']) ) {
        $include_products = wcgs_get_non_linked_products_ids();
    }
    
    // $include_products = [6577]
     
    
    if( count($include_products) === 0 ) {
        return new WP_Error( 'no_products_in_chunk', __( "Oops, No products found for sync.", "wcgs" ) );
    }
    
    $header  = apply_filters('wcgs_page_header_data', $header);
    if( !$header ) {
        return new WP_Error( 'header_not_found', __( "Oops, you have to sync first.", "wcgs" ) );
    }
    
    $header = array_fill_keys($header, '');
    $items = [];
    
    /**
     * If include_products are greater then limit (400) then chunk it down
     * */
    $max_wc_api_limit = 50;
    $chunked_ids = array_chunk($include_products, $max_wc_api_limit, true);
    // wcgs_log($chunked_ids); exit;
    // if( count($include_products) > $max_wc_api_limit ){
      
    // }
    
    foreach($chunked_ids as $chunk){
    
      $args              = apply_filters('wcgs_export_products_args',
                        ['per_page' => $max_wc_api_limit, 'include' => $chunk]);
      // wcgs_log($args);
          
      $request = new WP_REST_Request( 'GET', '/wc/v3/products' );
      $request->set_query_params( $args );
      $response = rest_do_request( $request );
      if ( ! $response->is_error() ) {
          $items = array_merge($items, $response->get_data());
      }
  
    }
    
    
    $items = apply_filters('wcgs_products_list_before_syncback', $items, $sheet_info);
    
    $sortby_id = array_column($items, 'id');
    array_multisort($sortby_id, SORT_ASC, $items);
    
    $header['sync'] = 'OK';
    $items = array_map(function($data) use($header){
        return array_replace($header, array_intersect_key($data, $header));
    }, $items);
    
    // wcgs_log($items); exit;
    
    // wcgs_log($pid_rows); exit;
    return apply_filters('wcgs_products_synback', $items, $sheet_info);
  }
  
  // Fetch categories
  // get categories for sync-back
  function get_categories_for_syncback($sheet_info){
   
    $header     = $sheet_info['header_data'];
    $sheet_name = $sheet_info['sheet_name'];
    $sync_data  = 'OK';
    
    // $chunk_size = $sheet_info['chunk_size'];
    // $chunk      = intval($sheet_info['request_args']['chunk']);
    $chunk_size = 100;
    $args_product_cat = ['taxonomy'=>'product_cat','hide_empty' => false];
    $total_cats = wp_count_terms($args_product_cat);
    $no_of_pages = floor($total_cats);
    // wcgs_log($no_of_pages);
    
    $items = [];
    
    for($i=1; $i<=$no_of_pages; $i++){
      
      $args              = apply_filters('wcgs_export_categories_args',
                          ['per_page' => $chunk_size,
                          'page'      => $i]);
                          
      // if request_args has ids then only select those ids
      if( isset($sheet_info['request_args']['ids']) ) {
        $args['include'] = $sheet_info['request_args']['ids'];
      }
      
      // if request_args has new_only then include only unlinked data
      if( isset($sheet_info['request_args']['new_only']) ) {
        $args['include'] = wcgs_get_non_linked_categories_ids();
        // if new catesgory are synced then sync should be null to LINK
        $sync_data = '';
      }
      
      // wcgs_log($args);
      $request = new WP_REST_Request( 'GET', '/wc/v3/products/categories' );
      $request->set_query_params( $args );
      $response = rest_do_request( $request );
      if ( $response->is_error() ) {
          $error = $response->as_error();
          return new WP_Error( 'wcapi_categories_fetch_error', $error->get_error_message() );
      }
      
      $items = array_merge($items, $response->get_data());
    }
    
    // wcgs_log($items);
    $items = apply_filters('wcgs_categories_list_before_syncback', $items);
    
    $sortby_id = array_column($items, 'id');
    array_multisort($sortby_id, SORT_ASC, $items);
    
    // wcgs_log($header);
    $header = array_fill_keys($header, '');
    $header['sync'] = $sync_data;
    
     $categories = array();
     foreach($items as $item) {
       // My Hero :)
        $categories[] = array_replace($header, array_intersect_key($item, $header));    // replace only the wanted keys
     }
     
    // wcgs_log($categories); exit;
    return apply_filters('wcgs_categories_synback', $categories, $sheet_info);
  }
  
  // Add variation before syncback via hook
  function add_variations($products, $sheet_info){
        
      
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
  				    
  				    $variation_data['type'] = 'variation';
  				    
  				    /**
  				     * since we are pulling variation via wc_get_products (not with WC API)
  				     * Some keys are not matched like image_id is returned instead of image
  				     **/
  				    $variation_data['image'] = $variation_data['image_id'];
  				    $variations[] = $variation_data;
  				}
      }
      
      // wcgs_log($variations);
      $combined_arr = array_merge($products, $variations);
      return $combined_arr;
  }
  
  // Adding meta columns if found
  function add_meta_columns($products, $sheet_info){
    
    $meta_keys = get_option('wcgs_metadata_keys');
    if( !$meta_keys ) return $products;
    
    $header_data = $sheet_info['header_data'];
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
    
    // wcgs_log($products);
    // exit;
    return $products;
    
  }
  
  function create_product_chunks($sheet_info) {
    
      $sheet_name = $sheet_info['sheet_name'];
      $chunk_size = $sheet_info['chunk_size'];
      
      $include_products = wcgs_get_syncback_product_ids();
          
      $response = [];
      
      if($include_products) {
          if( !$include_products ) return null;
          $chunked_array = array_chunk($include_products, $chunk_size, true);
          update_option("wcgs_{$sheet_name}_syncback_chunk", $chunked_array);
          // wcgs_pa($chunked_array);
          
          $total_chunks = count($chunked_array);
          
          $response['status'] = 'chunked';
          $response['chunks'] =  $total_chunks;
          $response['message'] =  sprintf(__("Total %d Products found, chunked into %d", "wcgs"), count($include_products), $total_chunks);
      } else {
          $response = ['message'=>"No products found to sync-back",'status'=>'success'];
      }
          
      return $response;
  }
  
}