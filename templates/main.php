<?php
/**
 * Bulk Product Sync - Setup
 **/

$webhook_url = wbps_get_webapp_url() ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>';
$sheet_props = get_option('wbps_sheet_props');

$connection = isset($sheet_props['connection_status']) && $sheet_props['connection_status'] === 'verified' ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>';
$pro_activated = isset($sheet_props['pro_version']) && $sheet_props['pro_version'] === 'true' ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>';
$sheet_id = isset($sheet_props['sheet_id']) ? $sheet_props['sheet_id'] : '';
$chunk_size = isset($sheet_props['chunk_size']) ? $sheet_props['chunk_size'] : '';
$sheet_url = 'https://docs.google.com/spreadsheets/d/' . $sheet_id . '/edit';
$autofetch_url = 'https://najeebmedia.com/blog/how-to-enable-autofetch-in-bulk-product-sync-for-real-time-store-updates-in-your-google-sheet/';
$autosync_url = 'https://najeebmedia.com/blog/how-to-enable-auto-sync-in-bulk-product-sync-for-real-time-store-updates-in-your-google-sheet/';
$pro_version_link = 'https://najeebmedia.com/googlesync';
$video_guide_url = 'https://www.youtube.com/watch?v=aCjnnOXXiP8';
$support_url = 'https://clients.najeebmedia.com/forums/forum/googlesync/';
$google_bps_addon = 'https://workspace.google.com/marketplace/app/bulk_product_sync_with_google_sheets/267586530797';
// var_dump($sheet_props);
?>
<div id="wbps-main">
    <header>
        <h1 class="head-item">Bulk Product Edit with BPS</h1>
        <img class="head-item" width="75" src="<?php echo WBPS_URL.'/images/bps-logo.png'?>" alt="Bulk Product Sync Logo" />
    </header>
    
    <section class="connection-tasks">
        <article class="task-item">
            <p class="task-unit name">Video Guide <br>
            <small>Please must watch this video to setup plugin</small></p>
            <p class="task-unit status"><a href="<?php echo esc_url($video_guide_url);?>" target="_blank"><span class="dashicons dashicons-youtube"></span></a></p>
        </article>
        
        <article class="task-item">
            <p class="task-unit name">Google Addon for BulkProductSync</p>
            <p class="task-unit status"><a href="<?php echo esc_url($google_bps_addon);?>" target="_blank"><span class="dashicons dashicons-google"></span></a></p>
        </article>
        
        
        <article class="task-item">
            <p class="task-unit name">Connection Status</p>
            <p class="task-unit status"><?php echo $connection;?></p>
        </article>
        
        <article class="task-item">
            <p class="task-unit name">Chunk Size</p>
            <p class="task-unit status"><?php echo $chunk_size;?></p>
        </article>
        
        <article class="task-item">
            <p class="task-unit name">Connected Google Sheet</p>
            <?php if($sheet_id):?>
            <p class="task-unit status"><a href="<?php echo esc_url($sheet_url);?>" target="_blank"><span class="dashicons dashicons-external"></span></a></p>
            <?php else: ?>
            <p class="task-unit status"><span class="dashicons dashicons-no"></span></p>
            <?php endif;?>
        </article>
        
        
        <article class="task-item">
            <p class="task-unit name">AutoFetch Enabled <br>
            <a href="<?php echo esc_url($autofetch_url);?>" target="_blank">How to Enable AutoFetch</a>
            <a href="<?php echo esc_url($autosync_url);?>" target="_blank">How to Enable AutoSync</a>
            </p>
            <p class="task-unit status"><?php echo $webhook_url; ?></p>
        </article>
        
        <article class="task-item">
            <p class="task-unit name">Pro Version Enabled</p>
            <p class="task-unit status"><?php echo $pro_activated; ?></p>
        </article>
        
        <article class="task-item">
            <p class="task-unit name">Free Version</p>
            <p class="task-unit status"><?php echo WBPS_VERSION; ?></p>
        </article>
        
        <article class="task-item">
            <p class="task-unit name">Pro Version</p>
            <?php if(defined('WCGS_PRO_VERSION')): ?>
            <p class="task-unit status"><?php echo WCGS_PRO_VERSION; ?></p>
            <?php else: ?>
            <p class="task-unit status">
                <span class="dashicons dashicons-no"></span>
            </p>
            <?php endif;?>
        </article>
        
        <article class="task-item">
            <p class="task-unit name">Help/Support?</p>
            <p class="task-unit status"><a href="<?php echo esc_url($support_url);?>" target="_blank"><span class="dashicons dashicons-external"></span></a></p>
        </article>
        
        <?php if(!defined('WCGS_PRO_VERSION')): ?>
        <section>
            <h3>Update PRO & Unlock:</h3>
            <ul>
                <li>Fetch Categories</li>
                <li>Fetch Products</li>
                <li>Unlock all Product Columns</li>
                <li>Update Stock & Prices Only for Quick Inventory</li>
                <li>Variable & Variation Products Management</li>
                <li>Auto Sync (Schedualed as per your need)</li>
                <li>Auto Fetch (Whenever changes on site, Sheet is updated)</li>
                <li>Access Support Forum</li>
                <li>Fetch Orders</li>
            </ul>
        </section>
        <?php endif; ?>
        
        <article id="wpbs-pro-btn">
            <a class="button-primary" href="<?php echo esc_url($pro_version_link);?>" target="_blank">Get Pro</a>
        </article>
    </section>
    
</div>
