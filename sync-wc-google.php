<?php 
/**
 * Plugin Name: Bulk Product Sync for WooCommerce
 * Plugin URI: http://www.najeebmedia.com/googlesync
 * Description: A plugin that allows bulk syncing of products between WooCommerce stores. 
 * Version: 7.5.2
 * Author: N-Media
 * Author URI: http://najeebmedia.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wcgs
 */
 
define('WBPS_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
define('WBPS_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define('WBPS_VERSION', '7.5.2' );
define('WBPS_SHORTNAME', 'wbps' );
// Data display
define('WBPS_CATEGORIES_TAG_DATA', get_option('wcgs_category_tags_data'));

include_once WBPS_PATH.'/includes/functions.php';
include_once WBPS_PATH.'/includes/meta.json.php';
include_once WBPS_PATH.'/includes/admin.class.php';
include_once WBPS_PATH.'/includes/formats.class.php';
include_once WBPS_PATH.'/includes/wc-api.class.php';
include_once WBPS_PATH.'/includes/products.class.php';
include_once WBPS_PATH.'/includes/categories.class.php';
include_once WBPS_PATH.'/includes/wprest.class.php';
include_once WBPS_PATH.'/includes/hooks.class.php';

     
function wbps_init(){
    
    init_wpbs_admin();
    
    init_wbps_format();
    
    init_wbps_wp_rest();
    
    init_wpbs_hooks();
    
}


add_action('woocommerce_init', 'wbps_init');