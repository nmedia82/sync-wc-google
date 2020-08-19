<?php
/**
 * WP Callbacks
 * */
 
add_action('wp_ajax_wcgs_sync_categories', 'wcgs_sync_categories');
function wcgs_sync_categories() {
    
    // if (defined('DOING_AJAX') && DOING_AJAX)
        // wp_send_json($_POST);
    
    $sheet_name = isset($_POST['sheet']) ? $_POST['sheet'] : '';
    
    $sync_result = null;
    
    switch( $sheet_name ) {
        case 'categories';
            $category = new WCGS_Categories();
            $sync_result = $category->sync();
        break;
        
        case 'products';
            $product = new WCGS_Products();
            $sync_result = $product->sync();
        break;
        
    }
    
    wp_send_json($sync_result);
}