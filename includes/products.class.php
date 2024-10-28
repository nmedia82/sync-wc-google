<?php
/**
 * Google Sheet Products Controller
 * 
 * */

class WBPS_Products {
    
    private static $ins = null;
	
	public static function __instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
    
    
    /**
     * chunk [ [id], [row] ]
     * */
    public static function sync($chunk, $general_settings) {
        
        try{
            
            /**
             * Defined: class.formats.php
             * 1. formatting each column data with wcgs_{$sheet_name}_data_{$key}
             * 2. Setting meta_data key for the product
             * 3. product meta columns handling
             **/
            $products = apply_filters('wcgs_sync_data_products_before_processing', $chunk, $general_settings);
            
            
            $variations = array_filter($products, function($row){
                $type = isset($row['type']) ? $row['type'] : '';
                return $type == 'variation' && ! empty($row['parent_id']);
            });
            
            // wbps_logger_array($products); return;
            $without_variations = array_filter($products, function($row){
                $type = isset($row['type']) ? $row['type'] : '';
                return $type != 'variation';
            });
                                        
            // Preparing data for WC API
            $wcapi_data = [];
            foreach($without_variations as $row_id => $row){
                
                // wbps_logger_array($row);
                
                $id = $row['id'];
                // adding row id
                // $row['meta_data'] = [...$row['meta_data'], ['key'=>'wbps_row_id', 'value'=>$row_id]];
                
                if( $id != '' ) {
                    $wcapi_data['update'][$row_id] = $row;   
                }else{
                    $wcapi_data['create'][$row_id] = $row;
                }
            }
            
            // Handling Variations
            // Preparing variations data for WC API
            $wcapi_variations = [];
            foreach($variations as $row_id => $variation){
                
                $id = $variation['id'];
                $parent_id = $variation['parent_id'];
                // adding row id
                // $variation['meta_data'] = [['key'=>'wbps_row_id', 'value'=>$row_id]];
                
                if( $id != '' ) {
                    $wcapi_variations[$parent_id]['update'][$row_id] = $variation;   
                }else{
                    unset($variation['id']);
                    $wcapi_variations[$parent_id]['create'][$row_id] = $variation;
                }
            }
            
            // wbps_logger_array($wcapi_data);
            // wbps_logger_array($wcapi_variations);
            // return;
            
            $wcapi = new WBPS_WCAPI();
            
            $result1 = [];
            
            if( count($wcapi_data) > 0 ) {
                $result1 = $wcapi->batch_update_products($wcapi_data);
                if( is_wp_error($result1) ) {
                    return $result1;
                }
            }
            
            
            // attaching taxonomies
            wpbs_attach_or_remove_product_taxonomies($result1, $products);
            
            $result2 = [];
            if( count($wcapi_variations) > 0 ) {
                $result2 = $wcapi->batch_update_variations($wcapi_variations);
                if( is_wp_error($result2) ) {
                    return $result2;
                }
            }
            
            $both_res = array_merge($result1, $result2);
            
            // wbps_logger_array($result1);
            // wbps_logger_array($result2);
            return $both_res;
            
        } catch(Exception $e) {
                
            $response['status'] = "error";
            $response['message'] =  $e->getMessage();
        }
        
    }
    
    /**
     * fetching (from store to sheet)
     * chunk [2,3,4] contains the product ids in array
     **/
    public static function fetch($products, $header, $settings, $last_row) {
        
        $header = array_fill_keys($header, '');
        $items = [];
        
        
        /**
         * If include_products are greater then limit (400) then chunk it down
         * */
        $max_wc_api_limit = 50;
        $chunked_ids = array_chunk($products, $max_wc_api_limit, true);
        
        foreach($chunked_ids as $chunk){
        
            $args              = apply_filters('wbps_export_products_args',
                            ['per_page' => $max_wc_api_limit, 
                            'include' => $chunk
                            ]);
            
              
            $request = new WP_REST_Request( 'GET', '/wc/v3/products' );
            $request->set_query_params( $args );
            $response = rest_do_request( $request );
            if ( ! $response->is_error() ) {
              $items = array_merge($items, $response->get_data());
            }
            

        }
        
        // adding variation and meta column based on this hook
        $items = apply_filters('wbps_products_list_before_syncback', $items, $header);
        
        $sortby_id = array_column($items, 'id');
        array_multisort($sortby_id, SORT_ASC, $items);
        
        $header['sync'] = 'OK';
        $items = array_map(function($data) use($header){
            return array_replace($header, array_intersect_key($data, $header));
        }, $items);
        
        $items = apply_filters('wbps_products_synback', $items, $header, $settings);
        // wbps_logger_array($items);
        
        $products = self::prepare_for_syncback($items, $settings, $last_row);
        
        return $products;
    }
    
    public static function prepare_for_syncback($products, $settings, $last_row){
        
        global $wpdb;
        $qry = "SELECT post_id, meta_value from {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.meta_key = 'wbps_row_id'";
        $db_results = $wpdb->get_results($qry);
        $pid_rows = [];
        foreach($db_results as $row){
          $pid_rows[$row->post_id] = $row->meta_value;
        }
        
        // wbps_logger_array($products);
    
        $products_refined = [];
        $row = $last_row;
        $link_new_data = [];
        foreach($products as $product) {
            // Check if sync column meta exists
            if( isset($pid_rows[$product['id']]) && $wcgs_row_id = $pid_rows[$product['id']] ) {
                 $update_array = array_map( function($item) {
                    $item = $item == "" ? "" : html_entity_decode($item);
                    return $item;
                }, array_values($product));
                $products_refined['update'][$wcgs_row_id] = $update_array;
            }else{
                $create_array = array_map( function($item) {
                    $item = $item == "" ? "" : html_entity_decode($item);
                    return $item;
                }, array_values($product));
                $row = $row + 1;
                $link_new_data[$row] = $product['id'];
                $products_refined['create'][$row] = $create_array;
            }
        }
        
        // wbps_logger_array($products_refined);
        
        // linking products with row ids
        self::link_product_with_sheet($link_new_data);
        
        return $products_refined;
    }
    
    public static function link_product_with_sheet($row_prodid){
        
        if( count($row_prodid) <= 0 ) return;
        
        global $wpdb;
        $postmeta_table = $wpdb->prefix.'postmeta';
        
        $wpsql = "INSERT INTO {$postmeta_table} (post_id,meta_key,meta_value) VALUES ";
        $delqry = "DELETE FROM {$postmeta_table} WHERE post_id IN (";
        $metakey = 'wbps_row_id';
        
        foreach($row_prodid as $row_id => $prod_id){
            
            
            $metaval    = $row_id;
            $postid     = $prod_id;    // term id
            
            // Delete existing terms meta if any
            $delqry .= "{$postid},";
            // Term meta sql
            $wpsql .= "({$postid}, '{$metakey}', '{$metaval}'),";
        
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
        
}

function init_wbps_products(){
	return WBPS_Products::__instance();
}