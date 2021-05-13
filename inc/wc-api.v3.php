<?php
/**
 * WooCOmmerce API Live Updates
 * */
 
class WCGS_WC_API_V3 {

  function __construct() {
    
    // Adding variations into products lists
    add_filter('wcgs_products_list_before_syncback', array($this, 'add_variations'));
      
  }
  
  function batch_update_products($data) {
    
    $request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
    $request->set_body_params( $data );
    $response = rest_do_request( $request );
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
                  return ['row'=>$row_id, 'id'=>$item['id']];
                    
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
                  return ['row'=>$row_id, 'id'=>$item['id']];
                    
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
    $response = rest_do_request( $request );
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
                  return ['row'=>$row_id, 'id'=>$item['id']];
                    
             }, $response['update']);
             
            // wcgs_log($result);
        }
        
        if( isset($response['create']) ) {
             $result2 = array_map(function($item) use($rowRef){
               
                  if( isset($item['error']) )
                    return ['row'=>'ERROR','id'=>$item['id'], 'message'=>$item['error']['message']];
                
                  $item_name = sanitize_key($item['name']);
                  $row_id = isset($rowRef[$item_name]) ? $rowRef[$item_name] : '';
                  return ['row'=>$row_id, 'id'=>$item['id']];
                    
             }, $response['create']);
             
        }
        
        // wcgs_log($result);
        $results = array_merge($result1, $result2);
        //This action is used to update category meta for wcgs_row_id
        do_action('wcgs_after_categories_updated_v3', $results);
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
                        return ['row'=>$row_id, 'id'=>$item['id']];
                          
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
                        return ['row'=>$row_id, 'id'=>$item['id']];
                          
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
    $chunk      = intval($sheet_info['chunk']);
    
    $saved_chunked = get_transient("wcgs_{$sheet_name}_syncback_chunk");
    
    $response = array();
    if( !isset($saved_chunked[$chunk]) ) {
        $response['status'] = 'error';
        $response['message'] = __("No chunk found to sync","wcgs");
        return $response;
    }
    
    $include_products = $saved_chunked[$chunk]; 
    // wcgs_log($include_products); exit;
    
    $items = [];
    
    $args              = apply_filters('wcgs_export_products_args',
                        ['per_page' => $chunk_size, 'include' => $include_products]);
        
    $request = new WP_REST_Request( 'GET', '/wc/v3/products' );
    $request->set_query_params( $args );
    $response = rest_do_request( $request );
    if ( ! $response->is_error() ) {
        $items = $response->get_data();
    }
    
    $header  = apply_filters('wcgs_page_header_data', $header);
    if( !$header ) {
        return new WP_Error( 'header_not_found', __( "Oops, you have to sync first.", "wcgs" ) );
    }
    
    $items = apply_filters('wcgs_products_list_before_syncback', $items);
    
    $sortby_id = array_column($items, 'id');
    array_multisort($sortby_id, SORT_ASC, $items);
    
    $header = array_fill_keys($header, '');
    
     $products = array();
     foreach($items as $item) {
         
         $product_row = array();
         if( $header ) {
           
          // My Hero :)
          $header['sync'] = 'OK';
          $products[] = array_replace($header, array_intersect_key($item, $header));    // replace only the wanted keys
         }
         
     }
     
    // wcgs_log($products); exit;
    return apply_filters('wcgs_products_synback', $products, $sheet_info);
  }
  
  // Fetch categories
  // get categories for sync-back
  function get_categories_for_syncback($sheet_info){
   
    $header     = $sheet_info['header_data'];
    $sheet_name = $sheet_info['sheet_name'];
    // $chunk_size = $sheet_info['chunk_size'];
    // $chunk      = intval($sheet_info['chunk']);
    $chunk_size = 100;
    
    $items = [];
    $args              = apply_filters('wcgs_export_categories_args',
                        ['per_page' => $chunk_size]);
                        
    // if request_args has ids then only select those ids
    if( isset($sheet_info['request_args']['ids']) ) {
      $args['include'] = $sheet_info['request_args']['ids'];
    }
        
    $request = new WP_REST_Request( 'GET', '/wc/v3/products/categories' );
    $request->set_query_params( $args );
    $response = rest_do_request( $request );
    if ( $response->is_error() ) {
        $error = $response->as_error();
        return new WP_Error( 'wcapi_categories_fetch_error', $error->get_error_message() );
    }
    
    $items = $response->get_data();
    
    // $items = apply_filters('wcgs_categories_list_before_syncback', $items);
    
    $sortby_id = array_column($items, 'id');
    array_multisort($sortby_id, SORT_ASC, $items);
    
    $header = array_fill_keys($header, '');
    
     $categories = array();
     foreach($items as $item) {
         
         $product_row = array();
         if( $header ) {
           
          // My Hero :)
          $header['sync'] = 'OK';
          $categories[] = array_replace($header, array_intersect_key($item, $header));    // replace only the wanted keys
         }
         
     }
     
    // wcgs_log($categories); exit;
    return apply_filters('wcgs_categories_synback', $categories, $sheet_info);
  }
  
  // Add variation before syncback via hook
  function add_variations($products){
        
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
  				    $variations[] = $variation_data;
  				}
      }
      
      $combined_arr = array_merge($products, $variations);
      // wcgs_log($variations); exit;
      return $combined_arr;
  }
  
  function create_product_chunks($sheet_info) {
    
      $sheet_name = $sheet_info['sheet_name'];
      $chunk_size = $sheet_info['chunk_size'];
      
      global $wpdb;
      $qry = "SELECT DISTINCT ID FROM {$wpdb->prefix}posts WHERE";
      $qry .= " post_type = 'product'";
      $qry .= " AND post_status = 'publish'";
      $syncback_setting = get_option('wcgs_syncback_settings');
      if( $syncback_setting == 'not_imported' ){
          
          $qry .= " AND EXISTS (SELECT * from {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID AND {$wpdb->prefix}postmeta.meta_key = 'wcgs_row_id');";
      }
      
      $products_notsync = $wpdb->get_results($qry, ARRAY_N);
      $include_products = array_map(function($item){ return $item[0]; }, $products_notsync);
      // wcgs_log($include_products); exit;
          
      // $products_notsync = get_posts($args);
      
      $response = [];
      
      if($include_products) {
          if( !$include_products ) return null;
          $chunked_array = array_chunk($include_products, $chunk_size, true);
          set_transient("wcgs_{$sheet_name}_syncback_chunk", $chunked_array);
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