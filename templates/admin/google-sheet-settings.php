<?php
/**
 * Google Sheet Widget Template
 * 
 *
 * */
 
 
do_action('wcgs_before_sync_wrapper', $google_client);

// var_dump($google_client->auth_link);
 
if ( $google_client->auth_link ) {
  printf(__('<a class="button button-primary wcgs-sync-btn" href="%s">%s</a>','wcgs'), esc_url($google_client->auth_link), "Authorize Google Account");
} else {
  
  printf(__('<p class="wcgs-connected">%s</p>', 'wcgs'), "Your Store Connected with Google Sheet");
   
}