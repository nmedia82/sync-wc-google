<?php
/**
 * Bulk Product Sync - Setup
 **/

$master_sheet = 'https://docs.google.com/spreadsheets/d/1P5ARmpqrEQh--o37c3McYWBhN-8Yr7zW8utJkCbA-WE/edit?usp=sharing';
$url_addon = 'https://workspace.google.com/marketplace/app/bulk_product_sync_with_google_sheets/267586530797';
$video_guide_url = 'https://www.youtube.com/watch?v=aCjnnOXXiP8';
$authcode = wbps_get_authcode();
$siteurl = get_bloginfo('url');
?>

<div id="wbps-main">
    <header>
        <h1 class="head-item">BulkProductSync Setup Wizard</h1>
        <img class="head-item" width="125" src="<?php echo esc_url(WBPS_URL . '/images/bps-logo.png'); ?>" alt="Bulk Product Sync Logo" />
    </header>

    <section class="authcode-section">
        <?php if (isset($_GET['authcode']) && $_GET['authcode'] === 'yes'): ?>
            <p>
                <label for="auth_code">AuthCode</label><br>
                <input type="text" id="auth_code" value="<?php echo esc_html($authcode); ?>" readonly style="width: 100%; max-width: 500px;">
            </p>
        <?php else: ?>
            <ol>
                <li>
                    Start by making your own copy of the Google Sheet by clicking:
                    <a href="<?php echo esc_url($master_sheet); ?>" target="_blank">Get Google Sheet</a>.
                    In the new sheet, click <strong>File → Make a copy</strong>.
                </li>
                <li>
                    Install the Bulk Product Sync Addon from:
                    <a href="<?php echo esc_url($url_addon); ?>" target="_blank">Install the Addon</a>
                    and click <strong>Install</strong>.
                </li>
                <li>
                    After installation, refresh the sheet. A new menu will appear under:
                    <strong>Extensions → Bulk Product Sync with Google Sheets™ → Setup</strong>.
                    Enter your site URL:
                    <code><?php echo esc_html($siteurl); ?></code>
                </li>
                <li>
                    Enter your AuthCode:
                    <strong><code><?php echo esc_html($authcode); ?></code></strong>
                    in the addon field and click <strong>Connect & Verify</strong>.
                </li>
            </ol>

            <p id="video-guide">
                <a href="<?php echo esc_url($video_guide_url); ?>" target="_blank">📺 Getting Started Video Tutorial</a>
            </p>
        <?php endif; ?>
    </section>
</div>