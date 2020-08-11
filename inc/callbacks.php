<?php
/**
 * WP Callbacks
 * */
 
add_action('wp_ajax_wcgs_sync_categories', 'wcgs_sync_categories');
function wcgs_sync_categories() {
    
    // if (defined('DOING_AJAX') && DOING_AJAX)
        // wp_send_json($_POST);
    
    $category = new WCGS_Categories();
    $categories = $category->get_data();
   
    $wcapi = new WCGS_WC_API();
    $googleSheetRows = $wcapi->add_categories($categories, $category->rowRef);
    
    $gs = new GoogleSheet_API();
    
    // If Client is authrized
    if ( ! $gs->auth_link ) {
        
        $gs->update_rows('categories', $googleSheetRows);
        
    }
    
    exit;
}