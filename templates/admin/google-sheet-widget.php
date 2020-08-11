<?php
/**
 * Google Sheet Widget Template
 * 
 *
 * */
 
 $gs = new GoogleSheet_API();
 
 // $client = $gs->getClient();
 if ( $gs->auth_link ) {
  echo '<a class="button" href="'.esc_url($gs->auth_link).'">Authorize Account</a>';
 } else {
  
  echo '<a class="button button-primary" href="#" id="wcgs-sync-categories">Sync Categories</a>';
   // $gs->getSheetInfo();
   
   // wcgs_pa($googleSheetRows); exit;
   
   // $gs->update_rows('categories', $googleSheetRows);
   // $gs->delete_row(3);
   // $gs->add_row($sheet_id);
 }
 ?>
