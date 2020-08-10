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
  
   // $gs->getSheetInfo();
   $category = new WCGS_Categories();
   $categories = $category->get_data();
   
   $wcapi = new WCGS_WC_API();
   $googleSheetRows = $wcapi->add_categories($categories, $category->rowRef);
   // wcgs_pa($googleSheetRows); exit;
   
   $gs->update_rows('categories', $googleSheetRows);
   // $gs->add_row($sheet_id);
 }
 ?>
