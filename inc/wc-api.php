<?php
/**
 * WooCOmmerce API
 * */
 
 require WCGS_PATH . '/lib/woocommerce/vendor/autoload.php';
 use Automattic\WooCommerce\Client;
 
 class WCGS_WC_API {
     
     function __Construct() {
         
         $this->woocommerce = new Client(
            'https://nmdevteam.com/ppom', // Your store URL
            'ck_8dc8e9e966ae812bb96d180e885e038d7c7d9848', // Your consumer key
            'cs_3d7d2f36bcea3ddd043fa643c5550e3a3f416672', // Your consumer secret
            [
                'wp_api' => true, // Enable the WP REST API integration
                'version' => 'wc/v3' // WooCommerce WP REST API version
            ]
        );
     }
     
     
     function get_info(){
         
         print_r($this->woocommerce->get(''));
     }
     
     // Updating categories via WC API
     // return Rows for Google Sheet
     function update_categories_batch($data, $rowRef) {
         
        $response = $this->woocommerce->post('products/categories/batch', $data);
        
         // Getting Rows to update Google Sheet
         $googleSheetRow = array();
         if( isset($response->create) ) {
             foreach($response->create as $item){
                 
                 if( isset($item->error) ) continue;
                 if( !isset($rowRef[$item->name]) ) continue;
                 
                 $rowNo = $rowRef[$item->name];
                 // $googleSheetRow[$rowNo] = [$item->name, $item->description, $item->id, $item->parent, 1];
                 $googleSheetRow[$rowNo] = [$item->id, 1];
                 do_action('wcgs_after_category_created', $item, $data);
             }
         }
         
         if( isset($response->update) ) {
             foreach($response->update as $item){
                 
                 if( isset($item->error) ) continue;
                 
                 if( !isset($rowRef[$item->id]) ) continue;
                 
                 $rowNo = $rowRef[$item->id];
                 // $googleSheetRow[$rowNo] = [$item->name, $item->description, $item->id, $item->parent, 1];
                 $googleSheetRow[$rowNo] = [$item->id, 1];
                 do_action('wcgs_after_category_updated', $item, $data);
             }
         }
         
         ksort($googleSheetRow);
         do_action('wcgs_after_categories_updated', $googleSheetRow, $data);
         wcgs_pa($googleSheetRow);
         return $googleSheetRow;
     }
     
     // Updating products via WC API
     // return Rows for Google Sheet
     function update_products_batch($data, $rowRef, $gs_rows) {
         
         $response = $this->woocommerce->post('products/batch', $data);
         // wcgs_pa($response);
        
         // Getting Rows to update Google Sheet
         $googleSheetRow = array();
         if( isset($response->create) ) {
             foreach($response->create as $item){
                 
                 if( isset($item->error) ) continue;
                 if( !isset($rowRef[$item->name]) ) continue;
                 
                 $rowNo = $rowRef[$item->name];
                 // Setting id
                 $gs_rows[ $rowNo-1 ][0] = $item->id;
                 // Setting sync
                 $gs_rows[ $rowNo-1 ][1] = 1;
                 // $googleSheetRow[$rowNo] = $gs_rows[ $rowNo-1 ];
                 $googleSheetRow[$rowNo] = [$item->id, 1];
                 do_action('wcgs_after_product_created', $item, $data);
             }
         }
         
         if( isset($response->update) ) {
             foreach($response->update as $item){
                 
                 if( isset($item->error) ) continue;
                 
                 if( !isset($rowRef[$item->id]) ) continue;
                 
                 $rowNo = $rowRef[$item->id];
                 // Setting sync
                 $gs_rows[ $rowNo-1 ][1] = 1;
                 // $googleSheetRow[$rowNo] = $gs_rows[ $rowNo-1 ];
                 $googleSheetRow[$rowNo] = [$item->id, 1];
                 do_action('wcgs_after_product_updated', $item, $data);
             }
         }
         
         ksort($googleSheetRow);
         do_action('wcgs_after_products_updated', $googleSheetRow, $data);
         wcgs_pa($googleSheetRow);
         return $googleSheetRow;
     }
     
     // get category for googlesheet row
     function get_category_for_gsheet($id){
         
         $item = $this->woocommerce->get('products/categories/'.$id);
         return [$item->id, 1, $item->name, $item->slug, $item->parent, $item->description, $item->display, '', $item->menu_order];
     }
 }