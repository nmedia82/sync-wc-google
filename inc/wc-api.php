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
     
     function add_categories($data, $rowRef) {
         
         $response = $this->woocommerce->post('products/categories/batch', $data);
         
         // Getting Rows to update Google Sheet
         $googleSheetRow = array();
         if( isset($response->create) ) {
             foreach($response->create as $cat){
                 
                 if( isset($cat->error) ) continue;
                 if( !isset($rowRef[$cat->name]) ) continue;
                 
                 $rowNo = $rowRef[$cat->name];
                 $googleSheetRow[$rowNo] = [$cat->name, $cat->description, $cat->id, $cat->parent, 1];
             }
         }
         
         if( isset($response->update) ) {
             foreach($response->update as $cat){
                 
                 if( isset($cat->error) ) continue;
                 
                 if( !isset($rowRef[$cat->id]) ) continue;
                 
                 $rowNo = $rowRef[$cat->id];
                 $googleSheetRow[$rowNo] = [$cat->name, $cat->description, $cat->id, $cat->parent, 1];
             }
         }
         
         ksort($googleSheetRow);
        //  wcgs_pa($googleSheetRow);
         return $googleSheetRow;
     }
     
     // get category for googlesheet row
     function get_category_for_gsheet($id){
         
         $cat = $this->woocommerce->get('products/categories/'.$id);
         return [$cat->name, $cat->description, $cat->id, $cat->parent, 1];
     }
 }