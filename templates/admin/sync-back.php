<?php
/**
 * Google Sync Back Template
 * 
 *
 * */
 
// echo '<div class="wcgs-sync-back-wrapper pt-1 woocommmerce">';
$wcgs_pro_url = 'https://najeebmedia.com/google-sync';
 
if ( ! $google_client->auth_link ) {
  
    echo '<div class="wcgs-sync-back-free">';
    echo '<a href="'.esc_url($wcgs_pro_url).'">';
    echo __("Upgrade to PRO version to export existing products into Google sheet with more advance features",'wcgs');
    echo '</a></div>';
    
    // printf(__('<a class="button button-primary wcgs-sync-back-btn" href="#" id="wcgs-sync-back-btn">%s</a>', 'wcgs'), "Export Selected Data To Google");
   
 }
//  echo '</div>';