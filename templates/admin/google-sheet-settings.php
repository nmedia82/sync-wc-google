<?php
/**
 * Google Sheet Widget Template
 * 
 *
 * */
 
 
do_action('wcgs_before_sync_wrapper', $google_client);

// var_dump($google_client->auth_link);
 
if ( $google_client->auth_link ) {
  
  if( wcgs_is_quick_connect() ) {
    printf(__('<a class="button button-primary wcgs-sync-btn" href="%s">%s</a>','wcgs'), esc_url(wcgs_quick_connect_url()), "Authorize Google Account");
  } else {
    printf(__('<a class="button button-primary wcgs-sync-btn" href="%s">%s</a>','wcgs'), esc_url($google_client->auth_link), "Authorize Google Account");
  }
} else {
  
  printf(__('<p class="wcgs-connected">%s</p>', 'wcgs'), "Your Store Connected with Google Sheet");
  
  // echo '<select id="sheet_name" class="wcgs-inputs">';
  // foreach(wcgs_sync_array() as $key => $value ){
  //   echo '<option value="'.$key.'">'.$value.'</option>';
  // }
  // echo '</select>';
  
  // printf(__('<a class="button button-primary wcgs-sync-btn" href="#" id="wcgs-sync-btn">%s</a>', 'wcgs'), "Sync Data");
  
  // // do_action('wcgs_after_sync_button', $google_client);
  
  // echo '<div id="wcgs_progressbar">';
  // printf(__('<div class="pb-run">%s</div>','wcgs'), 'Please wait...');
  // echo '</div>';
  
  // echo '<pre id="wcgs_working"></pre>';
   
}