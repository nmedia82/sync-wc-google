<?php
/**
 * Google Sheet Widget Template
 * 
 *
 * */
 
echo '<div class="wcgs-sync-wrapper woocommmerce">';
 
if ( $google_client->auth_link ) {
  printf(__('<a class="button button-primary wcgs-sync-btn" href="%s">%s</a>','wcgs'), esc_url($google_client->auth_link), "Authorize Google Account");
} else {
  
  echo '<select id="sheet_name" class="wcgs-inputs">';
  echo '<option value="categories">'.__('Categories', 'wcgs').'</option>';
  echo '<option value="products">'.__('Products', 'wcgs').'</option>';
  echo '</select>';
  
  printf(__('<a class="button button-primary wcgs-sync-btn" href="#" id="wcgs-sync-categories">%s</a>', 'wcgs'), "Sync Data");
  
  echo '<pre id="wcgs_working"></pre>';
   
 }
 echo '</div>';
 ?>
