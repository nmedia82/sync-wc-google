<?php
/**
 * WP Callbacks
 * */
 
add_action('wp_ajax_wcgs_sync_categories', 'wcgs_sync_categories');
function wcgs_sync_categories() {
    
    // if (defined('DOING_AJAX') && DOING_AJAX)
        // wp_send_json($_POST);
    
    $category = new WCGS_Categories();
    $category->sync();
    
    // $gs = new GoogleSheet_API();
    // $gs->add_row();
    
    // $product = new WCGS_Products();
    // $product->sync();
    exit;
}