<?php
/**
 * Google Sheet Products Controller
 * 
 * */

class WCGS_Products {
    
    function __construct() {
        
        add_action('wp_ajax_wcgs_get_total_products', [$this, 'get_total_products']);
        add_action('wp_ajax_nopriv_wcgs_get_total_products', [$this, 'get_total_products']);
        // now getting single chunk
        add_action('wp_ajax_wcgs_sync_chunk_products', [$this, 'sync_a_chunk'], 99, 1);
       
    }
    
    function get_total_products() {
        
        $action = 'syncable_total_products';
        $args = [];
        
        try{
            
            $response = wcgs_send_google_rest_request($action, $args);
            // if no row found
            if( $response['total_rows'] === 0){
                wp_send_json(['status'=>'message_response','message'=>__('No row(s) found','wcgs')]);
            }
            // wcgs_log($response);
            // remove headeing row count
            $total_products = $response['total_rows'];
            $total_chunks = ceil($total_products/WCGS_CHUNK_SIZE);
            $msg = sprintf(__("Total %d chunks created for %d products", 'wcgs'), $total_chunks, $total_products );
            wp_send_json(['status'=>'chunked','chunks'=>$total_chunks,'message'=>$msg]);
            
        } catch(Exception $e) {
                
            wp_send_json(['status'=>'error','message' => $e->getMessage()]);
        }
        
    }
    
    function sync_a_chunk($send_json=true) {
        
        if ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            $send_json = true;
        }
        
        
        $chunk = intval($_POST['chunk']);
        $sync_col = '';
        $images_col = '';
        $image_col = '';
        
        $action = 'get_product_chunk';
        $args = ['chunk' => $chunk, 'chunk_size' => WCGS_CHUNK_SIZE];
        
        $response = [];
        
        try{
            
            $response = wcgs_send_google_rest_request($action, $args);
            // wcgs_log($response);
            $header_data = $response['header_data'];
            $products = $response['products'];
            $sync_col = sanitize_text_field($response['sync_col']);
            $images_col = isset($response['images_col']) ? $response['images_col'] : null;
            $image_col = isset($response['image_col']) ? $response['image_col'] : null;
            
            // Adding extra header for the row_id
            // The row already have extra item in last as row id
            $header_data[] = 'row_id_meta';
            $combined_arr = array_map(function($row) use ($header_data) {
                                            return array_combine($header_data, $row);
                                        }, 
                                        $products);
            // wcgs_log($products);
            
            /**
             * Defined: class.formats.php
             * 1. formatting each column data with wcgs_{$sheet_name}_data_{$key}
             * 2. Setting meta_data key for the product
             * 3. product meta columns handling
             **/
            $combined_arr = apply_filters('wcgs_sync_data_products_before_processing', $combined_arr);
            
            $variations = array_filter($combined_arr, function($row){
                return $row['type'] == 'variation' && ! empty($row['parent_id']);
            });
            
            $without_variations = array_filter($combined_arr, function($row){
                return $row['type'] != 'variation';
            });
            
            // wcgs_log($without_variations); exit;
                                        
            // Preparing data for WC API
            $wcapi_data = [];
            // Existing data
            $wcapi_data['update'] = array_filter($without_variations, function($row){
                            return $row['id'] != '';
                });
            // New data
            $wcapi_data['create'] = array_filter($without_variations, function($row){
                            return $row['id'] == '';
                });
                
                
            // wcgs_log($wcapi_data);
            // Handling Variations
            // Preparing variations data for WC API
            $wcapi_variations = [];
            foreach($variations as $variation){
                
                $id = $variation['id'];
                $parent_id = $variation['parent_id'];
                
                if( $id != '' ) {
                    $wcapi_variations[$parent_id]['update'][] = $variation;   
                }else{
                    unset($variation['id']);
                    $wcapi_variations[$parent_id]['create'][] = $variation;
                }
            }
            
            // wcgs_log($wcapi_variations);
            
            $result1 = $result2 = [];        
        
            $wcapi_v3 = new WCGS_WC_API_V3();
            
            if($wcapi_data) {
                $result1 = $wcapi_v3->batch_update_products($wcapi_data);
                if( is_wp_error($result1) ) {
                    wp_send_json_error($result1->get_error_message());
                }
            }
            
            if($wcapi_variations) {
                $result2 = $wcapi_v3->batch_update_variations($wcapi_variations);
                if( is_wp_error($result2) ) {
                    wp_send_json_error($result2->get_error_message());
                }
            }
            
            $both_res = array_merge($result1, $result2);
            // wcgs_log($both_res);
            
            // FILTER ERRORS
            $errors = array_filter($both_res, function($a){
                return $a['row'] == 'ERROR';
            });
            
            // FILTER NON-ERRORS
            $rows_ok = array_filter($both_res, function($a){
                return $a['row'] != 'ERROR';
            });
            
            // building error msg string
            $err_msg = '';
            foreach($errors as $err){
                $err_msg .= '<p style="color:red">FAILED: '.$err['message'].' (Resource ID: '.$err['id'].')</p><hr>';
            }
            
            
            // Since version 3.2, updating google sheet back via PHP API
            $sheet_name = 'products';
            $id_col = 'A';
            
            
            $id_ranges = array_map(function($r){
                return 'A'.$r['row'];
            }, $rows_ok);
            
            $ids = array_map(function($r){
                return $r['id'];
            }, $rows_ok);
            
            $sync_ranges = array_map(function($r) use($sync_col){
                return $sync_col.$r['row'];
            }, $rows_ok);
            
            $images_ranges = [];
            $images_ids = [];
            if($images_col && isset($r['images'])){
                $images_ranges = array_map(function($r) use($images_col){
                    return $images_col.$r['row'];
                }, $rows_ok);
                
                // images IDs
                $images_ids = array_map(function($r){
                    return $r['images'];
                }, $rows_ok);
            }
            
            
            
            $image_ranges = [];
            $image_ids = [];
            if($image_col && isset($r['image'])){
                $image_ranges = array_map(function($r) use($image_col){
                    return $image_col.$r['row'];
                }, $rows_ok);
                
                // image IDs
                $image_ids = array_map(function($r){
                    return $r['image'];
                }, $rows_ok);
            }
            
            
            
            
            // $updatable_range = [];
            // foreach($rows_ok as $row){
            //     $updatable_range["{$sheet_name}!{$id_col}{$row['row']}"] = [$row['id']];
            //     $updatable_range["{$sheet_name}!{$sync_col}{$row['row']}"] = ['OK'];
            //     if( $images_col && isset($row['images']) ){
            //         $updatable_range["{$sheet_name}!{$images_col}{$row['row']}"] = [$row['images']];
            //     }
            //     if( $image_col && isset($row['image']) ){
            //         $updatable_range["{$sheet_name}!{$image_col}{$row['row']}"] = [$row['image']];
            //     }
            // }
            
            // wcgs_log($updatable_range); exit;
            // if( count($updatable_range) > 0 ) {
            //     $gs = new WCGS_APIConnect();
            //     $resp = $gs->update_rows_with_ranges($updatable_range);
            //     if( is_wp_error($resp) ) {
            //         return $resp;
            //     }
            // }
            
            // $resp = ['success_rows' => count($rows_ok),
            //             'error_rows'    => count($errors),
            //             'error_msg' => $err_msg
            //             ];
            
            // return $resp;
            // remove headeing row count
            
            $updatable_range = ['id_ranges'     => $id_ranges,
                                'ids'           => $ids,
                                'sync_ranges'   => $sync_ranges,
                                'images_ranges' => $images_ranges,
                                'images_ids'    => $images_ids,
                                'image_ranges'  => $image_ranges,
                                'image_ids'     => $image_ids];
            
            $response['status'] = "success";
            $response['message'] =  sprintf(__("Total %d products synced, found %d errors",'wcgs'), count($rows_ok), count($errors));
            $response['updatble_ranges'] = $updatable_range;
            
        } catch(Exception $e) {
                
            $response['status'] = "error";
            $response['message'] =  $e->getMessage();
        }
        
        
        if( $send_json ) {
            wp_send_json($response);
        } else {
            return $response;
        }
        
    }
}

return new WCGS_Products;