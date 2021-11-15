<?php
/**
 * Fetch operations: Sending data from WooCommerce to GoogleSheet
 * @craeted date: November 11, 2021
 * @created by: Najeeb Ahmad
 * 
 */
 
 
class WCGS_Fetch{
    
    static $sheet_id;
    static $chunk_size;
    // static $sheet;
    function __construct() {
        
        
        self::$sheet_id = wcgs_get_sheet_id();
        self::$chunk_size = wcgs_get_chunk_size();
        
        // callbacks
        add_action('wp_ajax_wcgs_sync_back_data_products', array($this, 'chunk_data'));
        add_action('wp_ajax_wcgs_syncback_chunk_products', array($this, 'fetch_chunk_data'));
        
        
        // Adding variations into products lists
        add_filter('wcgs_products_list_before_syncback', array($this, 'add_variations'), 11, 2);
        add_filter('wcgs_products_list_before_syncback', array($this, 'add_meta_columns'), 21, 2);
    }
    
    
    function chunk_data() {
        
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        $this->sheet_name = isset($_POST['sheet']) ? sanitize_text_field($_POST['sheet']) : '';
        
        $chunks_response = $this->create_chunk();
        
        // wcgs_log($chunks_response);
        
        $response = [];
        
        if( is_wp_error($chunks_response) ) {
            $response['status'] = 'error';
            $response['message'] =  $chunks_response->get_error_message();
        }else{
            $response = $chunks_response;
        }
        
        if( $send_json ) {
            wp_send_json($response);
        } else {
            return $response;
        }
    }
    
    
    function create_chunk(){
        
        $range = $this->sheet_name;
        $gs_rows = WCGS_APIConnect::get_sheet_rows(self::$sheet_id, $range);
        if( is_wp_error($gs_rows) ) {
            return new WP_Error( 'gs_client_error', $gs_rows->get_error_message() );
        }
        
        $sheet_header = $gs_rows[0];  // setting header
        
        $sync_col_index = array_search('sync', $sheet_header);
        if( ! $sync_col_index ) {
            return new WP_Error( 'gs_heading_error', __('Make sure sheet has correct header format, sync column is missing','wcgs') );
        }
        
        global $wpdb;
        $qry = "SELECT DISTINCT ID FROM {$wpdb->prefix}posts WHERE";
        $qry .= " post_type = 'product'";
        $qry .= " AND post_status = 'publish'";
        $syncback_setting = get_option('wcgs_syncback_settings');
        if( $syncback_setting == 'not_linked' ){
            $qry .= " AND NOT EXISTS (SELECT * from {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID AND {$wpdb->prefix}postmeta.meta_key = 'wcgs_row_id');";
        }
        
        $qry = apply_filters('wcgs_fetch_all_product_before_chunk_query', $qry, $this);
        
        $products_notsync = $wpdb->get_results($qry, ARRAY_N);
        $include_products = array_map(function($item){ return $item[0]; }, $products_notsync);
        // wcgs_pa($include_products); exit;
            
        // $products_notsync = get_posts($args);
        
        $response = [];
        
        if($include_products) {
            
            $chunk_size = self::$chunk_size;
            if( !$include_products ) return null;
            $chunked_array = array_chunk($include_products, $chunk_size, true);
            
            $syncback_transient = [ 'chunk'    => $chunked_array,
                                'header'   => $sheet_header,
                                'sync_col_index'   => $sync_col_index,
                                ];
                            
            wcgs_set_transient("wcgs_{$this->sheet_name}_syncback", $syncback_transient);
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
    
    // Populating the Google Sheet with products chunk
    function fetch_chunk_data() {
        
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        $this->sheet_name = isset($_POST['sheet']) ? $_POST['sheet'] : '';
        
        $fetched_data = $this->fetch_data();
        
        $fetch_response = $this->FETCH($fetched_data);
        
        $response = [];
        
        if( is_wp_error($fetch_response) ) {
            $response['status'] = 'error';
            $response['message'] =  $fetch_response->get_error_message();
        }else{
            $response = $fetch_response;
        }
        
        if( $send_json ) {
            wp_send_json($response);
        } else {
            return $response;
        }
        
    }
    
    function fetch_data() {
        
        $sync_transient = get_transient("wcgs_{$this->sheet_name}_syncback");
        $saved_chunked  = $sync_transient['chunk'];
        $header         = $sync_transient['header'];
    
        $chunk = isset($_POST['chunk']) ? $_POST['chunk'] : '';
        $sheet_name = isset($_POST['sheet']) ? sanitize_text_field($_POST['sheet']) : '';
        
        $response = array();
        if( !isset($saved_chunked[$chunk]) ) {
            $response['status'] = 'error';
            $response['message'] = __("No chunk found to sync","wcgs");
            return $send_json ? wp_send_json($response) : $response;
        }
        
        // wp_send_json($saved_chunked[$chunk]);
        $include_products = $saved_chunked[$chunk];
        
        switch( $this->sheet_name ) {
            
            case 'products':
                $fetched_data = WCGS_Products::fetch($include_products, self::$chunk_size, $header);
                break;
                
            case 'categories':
                $fetched_data = WCGS_Categories::sync($combined_arr);
                break;
        }
        
        wcgs_log($fetched_data); exit;
        
        return $fetched_data;
    }
        
    function FETCH($fetched_data){
        
        if( is_wp_error($fetched_data) ) {
            return $fetched_data;
        }
        
        $response = [];
        
        if($fetched_data) {
            
            $gs = new WCGS_APIConnect();
            $sync_result = $gs->add_new_rows('products', $fetched_data);
            // wcgs_log($sync_result);
            
            if( isset($sync_result->updates->updatedRows) ) {
                
                do_action('wcgs_after_product_syncback', $sync_result, $chunk, $fetched_data);
                
                $log_msg = sprintf(__("Total %d records exported in %s","wcgs"), $sync_result->updates->updatedRows, $sync_result->updates->updatedRange);
                $this->log(" ==== Products Sync-back Result ====");
                $this->log($log_msg);
                $response = ['message'=>$log_msg,'status'=>'success'];
            }
        } else {
            $response = ['message'=>"No products found to sync-back",'status'=>'success'];
        }
            
        return $response;
    }
    
    // Add variation before syncback via hook
  function add_variations($products, $sheet_header){
        
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
    //   wcgs_log($variations); exit;
      return $combined_arr;
  }
  
  // Adding meta columns if found
  function add_meta_columns($products, $sheet_header){
    
    $meta_keys = get_option('wcgs_metadata_keys');
    if( !$meta_keys ) return $products;
    
    // getting the allowed meta keys and converting to array
    $meta_array = explode(',', $meta_keys);
    $meta_array = array_map('trim', $meta_array);
    // extract only meta data columns
    $meta_column_found = array_intersect($meta_array, $sheet_header);
    if( $meta_column_found ) {
      
        $products = array_map(function($p) use ($meta_column_found){
        
        $p_meta = $p['meta_data'];
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
}

if( wcgs_pro_is_installed() ) {
    return new WCGS_Fetch;
}