<?php
/**
 * Google Sheet Widget Template
 * 
 *
 * */
 
 
do_action('wcgs_before_sync_wrapper', $google_client);

echo '<div class="wcgs-sync-wrapper woocommmerce">';

// var_dump($google_client->auth_link);
 
if ( $google_client->auth_link ) {
  printf(__('<a class="button button-primary wcgs-sync-btn" href="%s">%s</a>','wcgs'), esc_url($google_client->auth_link), "Authorize Google Account");
} else {
  
  echo '<select id="sheet_name" class="wcgs-inputs">';
  foreach(wcgs_sync_array() as $key => $value ){
    echo '<option value="'.$key.'">'.$value.'</option>';
  }
  echo '</select>';
  
  printf(__('<a class="button button-primary wcgs-sync-btn" href="#" id="wcgs-sync-btn">%s</a>', 'wcgs'), "Sync Data");
  
  do_action('wcgs_after_sync_button', $google_client);
  
  echo '<div id="wcgs_progressbar">';
  printf(__('<div class="pb-run">%s</div>','wcgs'), 'Please wait...');
  echo '</div>';
  
  echo '<pre id="wcgs_working"></pre>';
   
 }
 echo '</div>';