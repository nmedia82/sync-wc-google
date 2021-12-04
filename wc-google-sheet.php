<?php 
/**
 * Plugin Name: Sync WooCommerce with Google Sheets
 * Plugin URI: https://najeebmedia.com/googlesync
 * Description: Sync your products with Google Sheet into your WooCommerce Store
 * Version: 6.2
 * Author: N-Media
 * Author URI: http://najeebmedia.com
 * /
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wcgs
 */
 
// CONSTANTS
define('WCGS_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
define('WCGS_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define('WCGS_SETTING_URL', admin_url( 'admin.php?page=wc-settings&tab=wcgs_settings' ) );
define('WCGS_QCONN_URL', 'https://clients.najeebmedia.com/google-sync-connect/' );
define('WCGS_VERSION', '6.2' );
define('WCGS_LOG', false );
define('WCGS_SYNC_OK', 'OK' );


include_once WCGS_PATH . "/inc/const.php";
include_once WCGS_PATH . "/inc/functions.php";
include_once WCGS_PATH . "/inc/admin.php";
include_once WCGS_PATH . "/inc/wc-api.php";
include_once WCGS_PATH . "/inc/wc-api.v3.php";
include_once WCGS_PATH . "/inc/gs-api.php";
include_once WCGS_PATH . "/inc/gs-categories.php";
include_once WCGS_PATH . "/inc/gs-products.php";
include_once WCGS_PATH . "/inc/gs-variations.php";
include_once WCGS_PATH . "/inc/rest.php";
include_once WCGS_PATH . "/inc/hooks.php";
include_once WCGS_PATH . "/inc/callbacks.php";
include_once WCGS_PATH . "/inc/columns.php";
include_once WCGS_PATH . "/inc/class.sheet.php";
include_once WCGS_PATH . "/inc/class.formats.php";


class WCGS_INIT {
    
    /**
	 * the static object instace
	 */
	private static $ins = null;
	
	function __construct() {
	    
	   // add_action ( 'wp_enqueue_scripts', array ($this, 'load_scripts'));
	   //admin hooks
	    // add_action( 'wp_dashboard_setup', 'wcgs_admin_dashboard' );
	    
	   // Adding setting tab in WooCommerce
    	add_filter( 'woocommerce_settings_tabs_array', 'wcgs_add_settings_tab', 50 );
    	
    	// Display settings
    	add_action( 'woocommerce_settings_tabs_wcgs_settings', 'wcgs_settings_tab' );
    	
    	// Save settings
    	add_action( 'woocommerce_update_options_wcgs_settings', 'wcgs_save_settings' );
    	
    	// Adding css for sync column
    	add_action('admin_head', 'wcgs_admin_columns_css');
	    
	    
	    $plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", array($this, 'settings_plugin') );
		
		// Admin notices
		add_action( 'admin_notices', 'wcgs_admin_show_notices' );
		
		// Column Manager
		if( is_admin() ) WCGS_COLUMNS_INIT();
		
		add_filter('woocommerce_rest_check_permissions', '__return_true');
		
		add_action( 'admin_post_wcgs_remove_token', array($this, 'remove_token') );
		
	}
	
	
	function settings_plugin( $links ) {
		$setting_title  = __('Plugin Settings', 'twoco');
	    $video_title    = __('Video Guide', 'twoco');
	    $settings_link  = sprintf(__('<a href="%s">%s</a>','twoco'), WCGS_SETTING_URL, $setting_title);
	    $video_url      = 'https://youtu.be/pNdxG_otQ5c';
	    $video_guide  = sprintf(__('<a target="_blank" href="%s">%s</a>','twoco'), $video_url, $video_title);
	  	array_push( $links, $settings_link, $video_guide );
	  	return $links;
	}
	
	public static function get_instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
	
	public static function activate_plugin()
    {
        // Nothing TODO  
       
    }
    
    function remove_token(){
    	
    	delete_option('wcgs_token');
    	wp_redirect(WCGS_SETTING_URL);
    }
    
}


// ==================== INITIALIZE PLUGIN CLASS =======================
//
add_action('plugins_loaded', 'WCSH');
//
// ==================== INITIALIZE PLUGIN CLASS =======================

function WCSH() {
    
    return WCGS_INIT::get_instance();
}

register_activation_hook( __FILE__, array('WCGS_INIT', 'activate_plugin'));