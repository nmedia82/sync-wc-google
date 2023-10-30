<?php
/**
 * Bulk Product Sync - Setup
 **/
 
$master_sheet = 'https://docs.google.com/spreadsheets/d/1P5ARmpqrEQh--o37c3McYWBhN-8Yr7zW8utJkCbA-WE/edit?usp=sharing';
$url_addon = 'https://workspace.google.com/marketplace/app/bulk_product_sync_with_google_sheets/267586530797';
$video_guide_url = 'https://www.youtube.com/watch?v=yKNokaTP2iU';
$authcode = wbps_get_authcode();
$siteurl = get_bloginfo('url');
?>

<div id="wbps-main">
    <header>
        <h1 class="head-item">BulkProductSync Setup Wizard</h1>
        <img class="head-item" width="125" src="<?php echo WBPS_URL.'/images/gsync.png'?>" alt="Bulk Product Sync Logo" />
    </header>
    
    <section class="authcode-section">
        
            <?php if( isset($_GET['authcode']) && $_GET['authcode'] === 'yes' ){?>
            
                <p>
                    <label for="auth_code">AuthCode</label>
                    <input type="text" id="auth_code" value="<?php echo esc_html($authcode);?>" readonly>
                </p>
                
            <?php } else { ?>
        
                <ol>
                    <li>Start by making your own copy of the Google Sheet by clicking this link: <a href="<?php echo esc_url($master_sheet);?>" target="_blank">Get Google Sheet</a>. Don't forget to click "File" and then "Make a copy" in the new sheet.</li>
                    <li>Next, it's time to install the Bulk Product Sync Addon. Just click on this link: <a href="<?php echo esc_url($url_addon);?>" target="_blank">Install the Addon</a>, and hit the "Install" button.</li>
                    <li>Once you've installed the addon, refresh your Google sheet. You'll notice a new menu under <strong>Extensions > Bulk Product Sync with Google Sheets™ > Setup</strong>. Enter your site URL <strong><code><?php echo esc_html($siteurl);?></code></strong> in the provided field.</li>
                    <li>Finally, enter your AuthCode: <strong><code><?php echo esc_html($authcode);?></code></strong> in the corresponding field, and click "Connect & Verify" to complete the setup.</li>
                </ol>
                
                <p id="video-guide">
                    <a href="<?php echo esc_url($video_guide_url);?>" target="_blank">Getting Started Video Tutorial</a>
                </p>
            <?php } ?>
        
    </section>
</div>
