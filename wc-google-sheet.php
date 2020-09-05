<?php 
/**
 * Plugin Name: WooCommerce Google Sheet Sync
 * Plugin URI: https://najeebmedia.com 
 * Description: Sync products by Google Sheets
 * Version: 1.0
 * Author: najeebmedia.com
 * Author URI: http://najeebmedia.com
 * /
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wcgs
 */
 
// CONSTANTS
define('WCGS_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
define('WCGS_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );


include_once WCGS_PATH . "/inc/const.php";
include_once WCGS_PATH . "/inc/functions.php";
// include_once wcgc_PATH . "/inc/arrays.php";
include_once WCGS_PATH . "/inc/admin.php";
include_once WCGS_PATH . "/inc/wc-api.php";
include_once WCGS_PATH . "/inc/gs-api.php";
include_once WCGS_PATH . "/inc/gs-categories.php";
include_once WCGS_PATH . "/inc/gs-products.php";
include_once WCGS_PATH . "/inc/rest.php";
include_once WCGS_PATH . "/inc/hooks.php";
include_once WCGS_PATH . "/inc/callbacks.php";


class WC_GOOGLESHEET {
    
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
	    
	    
	    /* == rest api == */
		add_action( 'rest_api_init', 'wcgs_rest_api_register'); // endpoint url
	}
	
	public static function get_instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
	
	public function activate_plugin()
    {
        // Nothing TODO  
       
    }
    
}


// ==================== INITIALIZE PLUGIN CLASS =======================
//
add_action('plugins_loaded', 'WCSH');
//
// ==================== INITIALIZE PLUGIN CLASS =======================

function WCSH() {
    
    return WC_GOOGLESHEET::get_instance();
}

register_activation_hook( __FILE__, array('WC_GOOGLESHEET', 'activate_plugin'));

