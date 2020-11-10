<?php
// Google Sync PRO Class

class WCGS_PRO {
    
    function __construct() {
        
        // Add variations into Sync Array
        add_filter('wcgs_sync_array', array($this, 'add_varition_option') );
        add_action('wcgs_after_products_updated', array($this, 'add_variations'), 11, 3);
        
        add_action('wp_ajax_wcgs_sync_data', array($this, 'wcgs_sync_variations') );
    }
    
    
    function add_varition_option($sync_array){
        
        $sync_array['variations'] = __('Variations','wcgs');
        return $sync_array;
    }
    
    function wcgs_sync_variations(){
        
        $sheet_name = isset($_POST['sheet']) ? sanitize_text_field($_POST['sheet']) : '';
        
        $variation = new WCGS_Variations();
        $sync_result = $variation->sync();
    }
    
    function add_variations($item, $data, $product_ids){
        
        $batch_variation = [];
        // wcgs_pa($data);
        if( count($product_ids) > 0 ) {
            
            if( isset($product_ids['create']) ) {
                foreach($product_ids['create'] as $row_no => $product_id){
                        if( isset($data['create'][$row_no]['variations']) ) 
                            $batch_variation['create'][] = $data['create'][$row_no]['variations'];   
                }
            }
            
            if( isset($product_ids['update']) ) {
                foreach($product_ids['update'] as $row_no => $product_id){
                        if( isset($data['update'][$row_no]['variations']) )
                            $batch_variation['update'][] = $data['update'][$row_no]['variations'];
                }
            }
        }
        
        wcgs_pa($batch_variation);
    }
}

new WCGS_PRO;