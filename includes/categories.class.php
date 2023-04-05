<?php
/**
 * Google Sheet Categories Controller
 * 
 * */

class WBPS_Categories {
    
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
    public static function sync($chunk) {
        
        try{
            
            /**
             * Defined: class.formats.php
             * 1. formatting each column data with wcgs_{$sheet_name}_data_{$key}
             * 2. product meta columns handling
             **/
            $categories = apply_filters('wcgs_sync_data_categories_before_processing', $chunk);
            
            // Preparing data for WC API
            $wcapi_data = [];
            // Saving category name/id and row
            $rowRef = array();
            foreach($categories as $row_id => $row){
                
                $id   = isset($row['id']) ? $row['id'] : '';
                $name = isset($row['name']) ? sanitize_key($row['name']) : '';
                
                if( $id != '' ) {
                    $wcapi_data['update'][] = $row;   
                    $rowRef[$id] = $row_id;
                }else{
                    $wcapi_data['create'][] = $row;
                    $rowRef[$name] = $row_id;
                }
            }
            
            // wbps_logger_array($wcapi_data);
            
            $wcapi = new WBPS_WCAPI();
            
            $result = $wcapi->batch_update_categories($wcapi_data, $rowRef);
            if( is_wp_error($result) ) {
                wp_send_json_error($result->get_error_message());
            }
            
            // wbps_logger_array($result);
            return $result;
            
        } catch(Exception $e) {
                
            $response['status'] = "error";
            $response['message'] =  $e->getMessage();
        }
        
    }
    
    public static function fetch($header, $settings, $last_row){
    
        
        $chunk_size = 100;
        $args_product_cat = ['taxonomy'=>'product_cat','hide_empty' => false];
        $total_cats = wp_count_terms($args_product_cat);
        $no_of_pages = floor($total_cats);
        // wbps_logger_array($no_of_pages);
        
        $items = [];
        
        for($i=1; $i<=$no_of_pages; $i++){
          
            $args  = apply_filters('wbps_export_categories_args',
                          ['per_page' => $chunk_size,
                          'page'      => $i]);
                          
          // if request_args has ids then only select those ids
        //   if( isset($sheet_info['request_args']['ids']) ) {
        //     $args['include'] = $sheet_info['request_args']['ids'];
        //   }
          
          // if request_args has new_only then include only unlinked data
        //   if( isset($sheet_info['request_args']['new_only']) ) {
        //     $args['include'] = wbps_get_non_linked_categories_ids();
        //     // if new catesgory are synced then sync should be null to LINK
        //     $sync_data = '';
        //   }
        
            $args['include'] = self::get_syncable_category_ids();
          
            // wbps_logger_array($args);
            
            $request = new WP_REST_Request( 'GET', '/wc/v3/products/categories' );
            $request->set_query_params( $args );
            $response = rest_do_request( $request );
            if ( $response->is_error() ) {
              $error = $response->as_error();
              return new WP_Error( 'wcapi_categories_fetch_error', $error->get_error_message() );
            }
            
            $items = array_merge($items, $response->get_data());
        }
        
        // wbps_logger_array($items);
        $items = apply_filters('wbps_categories_list_before_syncback', $items);
        
        $sortby_id = array_column($items, 'id');
        array_multisort($sortby_id, SORT_ASC, $items);
        
        $header = array_fill_keys($header, '');
        $header['sync'] = 'OK';
        
         $categories = array();
         foreach($items as $item) {
           // My Hero :)
            $categories[] = array_replace($header, array_intersect_key($item, $header));    // replace only the wanted keys
         }
         
        $categories = self::prepare_for_syncback($categories, $settings, $last_row);
         
        // wbps_logger_array($categories);
        
        // this hooks not being used in pluin now.
        return apply_filters('wbps_categories_synback', $categories, $header, $settings, $last_row);
    }
    
    
    public static function prepare_for_syncback($categories, $settings, $last_row){
        
        $categories_refined = [];
        $row = $last_row;
        $link_new_data = [];
        foreach($categories as $cat) {
            
            if( isset($cat['image']) ) {
                $cat['image'] = apply_filters("wbps_categories_syncback_value_image", $cat['image'], 'image', $settings);
            }
            
            // Check if sync column meta exists
            $wcgs_row_id = get_term_meta($cat['id'], 'wbps_row_id', true);
            $wcgs_row_id = intval($wcgs_row_id);
            if( $wcgs_row_id ) {
                $categories_refined['update'][$wcgs_row_id] = array_values($cat);
            }else{
                $row = $row + 1;
                $link_new_data[$row] = $cat['id'];
                $categories_refined['create'][$row] = array_values($cat);
            }
        }
        
        // linking categories with row ids
        self::link_category_with_sheet($link_new_data);
        
        return $categories_refined;
    }
    
    public static function link_category_with_sheet($row_catid){
        
        if( count($row_catid) <= 0 ) return;
        
        global $wpdb;
        $termmeta_table = $wpdb->prefix.'termmeta';
        
        $wpsql = "INSERT INTO {$termmeta_table} (term_id,meta_key,meta_value) VALUES ";
        $delqry = "DELETE FROM {$termmeta_table} WHERE term_id IN (";
        $metakey = 'wbps_row_id';
        
        foreach($row_catid as $row_id => $cat_id){
            
            
            $metaval    = $row_id;
            $termid     = $cat_id;    // term id
            
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
    
    // get categories not linked
    public static function get_syncable_category_ids() {
    
        global $wpdb;
        $qry = "SELECT DISTINCT term_id FROM {$wpdb->prefix}term_taxonomy WHERE";
        $qry .= " taxonomy = 'product_cat'";
        // $qry .= " AND NOT EXISTS (SELECT * from {$wpdb->prefix}termmeta where {$wpdb->prefix}termmeta.term_id = {$wpdb->prefix}term_taxonomy.term_id AND {$wpdb->prefix}termmeta.meta_key = 'wbps_row_id');";
        
        $result = $wpdb->get_results($qry, ARRAY_N);
        $result = array_map(function($c){
            return $c[0];
        }, $result);
        
        return apply_filters('get_syncable_category_ids', $result);
    }
        
}

function init_wbps_categories(){
	return WBPS_Categories::__instance();
}