<?php
/**
 * WooCOmmerce API
 * */
 
 require WCGS_PATH . '/lib/woocommerce/vendor/autoload.php';
 use Automattic\WooCommerce\Client;
 
 class WCGS_WC_API {
     
     function __Construct() {
         
        $wcgs_ck = wcgs_get_option('wcgs_wc_ck');
        $wcgs_sk = wcgs_get_option('wcgs_wc_sk');
        
        //  PPOM DEV
         $this->woocommerce = new Client(
            get_site_url(), // Your store URL
            $wcgs_ck,
            $wcgs_sk,
            [
                'wp_api' => true, // Enable the WP REST API integration
                'version' => 'wc/v3', // WooCommerce WP REST API version
                'timeout' => 0,
                'verify_ssl'=> false
            ]
        );
     }
     
     
     function get_info(){
         
         print_r($this->woocommerce->get(''));
     }
     
     // Updating categories via WC API
     // return Rows for Google Sheet
     function update_categories_batch($data, $rowRef, $gs_rows) {
         
        $response = $this->woocommerce->post('products/categories/batch', $data);
        // wcgs_pa($response);
        
         // Getting Rows to update Google Sheet
         $googleSheetRow = array();
         if( isset($response->create) ) {
             foreach($response->create as $item){
              
                   if( isset($item->error) ) {
                      $errors_found[] = $item;
                   } else {
                      $item_name = sanitize_key($item->name);
                      if( !isset($rowRef[$item_name]) ) continue;
                      $rowNo = $rowRef[$item_name];
                      // if( !isset($rowRef[$item->name]) ) continue;
                      $googleSheetRow[$rowNo] = [$item->id, 1];
                   }
            
                 do_action('wcgs_after_category_created', $item, $data);
             }
         }
         
         if( isset($response->update) ) {
             foreach($response->update as $item){
                 
                if( isset($item->error) ) {
                     $errors_found[] = $item;
                  } else {
                     $rowNo = $rowRef[$item->id];
                     // $googleSheetRow[$rowNo] = [$item->name, $item->description, $item->id, $item->parent, 1];
                     $googleSheetRow[$rowNo] = [$item->id, 1];
                  }
                 
                 do_action('wcgs_after_category_updated', $item, $data);
             }
         }
         
          if( count($errors_found) > 0 ) {
            set_transient('wcgs_batch_error', $errors_found);
         }
         
         ksort($googleSheetRow);
         do_action('wcgs_after_categories_updated', $googleSheetRow, $data);
         // wcgs_pa($googleSheetRow);
         return $googleSheetRow;
     }
     
     // Updating products via WC API
     // return Rows for Google Sheet
     function update_products_batch($data, $gs_rows) {
         
         // ini_set('default_socket_timeout', 500);
         
        // product ids being created/udpated
        $product_ids = [];
         
         $errors_found = array();
        //   wcgs_pa($data); exit;
        try {
            $response = $this->woocommerce->post('products/batch', $data);
        } catch(HttpClientException $e) {
            $errors_found[] = $e->getMessage();
        }
        
         // Getting Rows to update Google Sheet
         $googleSheetRow = array();
         if( isset($response->create) ) {
             foreach($response->create as $item){
                 
                 if( isset($item->error) ) {
                    $errors_found[] = $item;
                 } else {
                    $key_found = array_search('wcgs_row_id', array_column($item->meta_data, 'key'));
                    if( $key_found === false ) continue;
                    $rowNo = $item->meta_data[$key_found]->value;
                    $googleSheetRow[$rowNo] = [$item->id, 1];
                    $product_ids['create'][$rowNo] = $item->id;
                 }
                 
                 do_action('wcgs_after_product_created', $item, $data, $rowNo);
             }
         }
         
         if( isset($response->update) ) {
             foreach($response->update as $item){
                 
                 if( isset($item->error) ) {
                    $errors_found[] = $item;
                 } else {
                    $key_found = array_search('wcgs_row_id', array_column($item->meta_data, 'key'));
                    if( $key_found === false ) continue;
                    $rowNo = $item->meta_data[$key_found]->value;
                    $googleSheetRow[$rowNo] = [$item->id, 1];
                    $product_ids['update'][$rowNo] = $item->id;
                 }
                 
                 do_action('wcgs_after_product_updated', $item, $data, $rowNo);
             }
         }
         
         
         if( count($errors_found) > 0 ) {
            set_transient('wcgs_batch_error', $errors_found);
         }
         
         ksort($googleSheetRow);
         do_action('wcgs_after_products_updated', $googleSheetRow, $data, $product_ids);
         // wcgs_pa($googleSheetRow);
         return $googleSheetRow;
     }
     
     
     // Attaching Variations to product via WC API
     // return Rows for Google Sheet
     function update_variations_batch($variations, $gs_rows) {
         
        // ini_set('default_socket_timeout', 500);
         
        // variation ids being created/udpated
        $variation_ids = [];
        $rowNo = 0;
         
        $errors_found = array();
        //   wcgs_pa($variations); exit;
        try {
            
            foreach($variations as $product_id => $variation) {
                // var_dump("products/{$product_id}/variations/batch", $variation);
                $response = $this->woocommerce->post("products/{$product_id}/variations/batch", $variation);
                
            }
        } catch(HttpClientException $e) {
            $errors_found[] = $e->getMessage();
        }
        
         // Getting Rows to update Google Sheet
         $googleSheetRow = array();
         if( isset($response->create) ) {
             foreach($response->create as $item){
                 
                 if( isset($item->error) ) {
                    $errors_found[] = $item;
                 } else {
                    $key_found = array_search('wcgs_row_id', array_column($item->meta_data, 'key'));
                    if( $key_found === false ) continue;
                    $rowNo = $item->meta_data[$key_found]->value;
                    $googleSheetRow[$rowNo] = [$item->id, 1];
                    $product_ids['create'][$rowNo] = $item->id;
                 }
                 
                 do_action('wcgs_after_variation_created', $item, $variations, $rowNo);
             }
         }
         
         if( isset($response->update) ) {
             foreach($response->update as $item){
                 
                 if( isset($item->error) ) {
                    $errors_found[] = $item;
                 } else {
                    $key_found = array_search('wcgs_row_id', array_column($item->meta_data, 'key'));
                    if( $key_found === false ) continue;
                    $rowNo = $item->meta_data[$key_found]->value;
                    $googleSheetRow[$rowNo] = [$item->id, 1];
                    $variation_ids['update'][$rowNo] = $item->id;
                 }
                 
                 do_action('wcgs_after_variation_updated', $item, $variations, $rowNo);
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
     
     // get category for googlesheet row
     function get_category_for_gsheet($id){
         
         $item = $this->woocommerce->get('products/categories/'.$id);
         $category = new WCGS_Categories();
         $header = $category->get_header();
         
         $category_row = array();
         if( $header ) {
             foreach($header as $key => $index) {
                 
                $value = $key == 'sync' ? 1 : $item->{ trim($key) };
                $value = $value === NULL ? '' : $value;
                $category_row[] = $value;
             }
         }
        //  $category_row = [$item->id, 1, $item->name, $item->slug, $item->parent, $item->description, $item->display, '', $item->menu_order];
        //  wcgs_pa($category_row); exit;
        return apply_filters('wcgs_category_update_row', $category_row, $id);
     }
     
     
     // get product for googlesheet row
     function get_product_for_gsheet($id){
         
         $item = $this->woocommerce->get('products/'.$id);
         $product = new WCGS_Products();
         $header = $product->get_header();
         // wcgs_pa($item);
         
         $product_row = array();
         if( $header ) {
             foreach($header as $key => $index) {
                 
                // ignore last_sync for now
                if( $key == 'last_sync' ) continue;
                
                $value = $key == 'sync' ? 1 : $item->{ trim($key) };
                $value = $value === NULL ? '' : $value;
                $product_row[] = $value;
             }
         }
        //  $product_row = [$item->id, 1, $item->name, $item->slug, $item->parent, $item->description, $item->display, '', $item->menu_order];
        //  wcgs_pa($item);
        return apply_filters('wcgs_product_update_row', $product_row, $id);
     }
 }