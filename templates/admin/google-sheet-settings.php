<?php
/**
 * Google Sheet Widget Template
 * 
 *
 * */
 
?>

<div class="wcgs-sync-wrapper">
    
    <?php if(wcgs_is_service_connect()): ?>
    
    <select id="sheet_name">
        <option value=""><?php _e('Select Sheet', 'wcgs');?></option>
        <option value="products"><?php _e('Products', 'wcgs');?></option>
        <option value="categories"><?php _e('Categories', 'wcgs');?></option>
    </select>
    <button id="wcgs-sync-btn" class="btn btn-primary">Sync</button>
    
    <?php endif; ?>
    
    <div id="wcgs_progressbar"><div id="pb-run"></div></div>
    <div id="wcgs_working"></div>
    
</div>