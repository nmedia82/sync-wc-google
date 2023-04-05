<?php
/**
 * WP Admin Related Stuff
 * Created Date: December 26, 2022
 * Created By: Ben Rider
 * */
 
class WBPS_Admin {
    
    private static $ins = null;
	
	public static function __instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
    
    function __construct(){
        
        add_action( 'admin_menu', [$this, 'admin_menu'] );
        
        // ajax callback
        add_action('wp_ajax_wbps_save_authcode', [$this, 'save_authcode']);
        
        // category columns
        add_filter( 'manage_edit-product_cat_columns', [$this, 'add_categories_columns'] );
        add_filter('manage_product_cat_custom_column', [$this, 'categories_column_content'],10,3);
        
        // product columns
        add_filter( 'manage_product_posts_columns', [$this, 'product_column'], 20);
        add_filter( 'manage_product_posts_custom_column', [$this, 'product_column_data'], 20, 2 );
    }
    
 
   function admin_menu() {
        
        $parent = 'options-general.php';
        $hook = add_submenu_page(
            $parent,
             __('BulkProductSync - Bulk Product Manager with Google Sheets', 'wbps'),
             __('BulkProductSync', 'wbps') ,
            'manage_options',
            'wbps-settings',
            array(
                $this,
                'settings_page'
            ),
            35
        );
        // script will be only loaded for this current settings page, not all the pages.
        add_action( 'load-'. $hook, [$this, 'load_scripts'] );
        
        // this hide page from menu, but can be accessible via link
        remove_submenu_page( 'options-general.php', 'wbps-settings' );
    }
    
    
    
    function load_scripts() {
        
        wp_enqueue_style('wbps-css', WBPS_URL.'/assets/wbps.css');
        wp_enqueue_script('wbps-js', WBPS_URL.'/assets/wbps.js', ['jquery'], WBPS_VERSION, true );
        
        wp_enqueue_script('wbps-goauth', '//apis.google.com/js/platform.js', null, WBPS_VERSION, false );
    }
    
    
    function settings_page() {
        
        $tpl = isset($_GET['tpl']) ? $_GET['tpl'] : 'setup';
        $tpl = "{$tpl}.php";
        
        wbps_load_file($tpl);
    }
    
    // save authcode
    function save_authcode(){
        
        if( ! current_user_can('administrator') ) {
            wp_send_json_error('Not allowed');
        }
        
        $authcode = sanitize_text_field($_POST['authcode']);
        update_option('wbps_authcode', $authcode);
        
        wp_send_json_success( __('Authcode is saved, continue with setup', 'wbps') );
        
    }
    
    function add_categories_columns($columns){
        $columns['wbps_row_id'] = 'Sync';
        return $columns;
    }
     
    function categories_column_content($content,$column_name,$term_id){
        switch ($column_name) {
            case 'wbps_row_id':
                //do your stuff here with $term or $term_id
                $content = get_term_meta($term_id,'wbps_row_id', true);
                $content = is_null($content) ? __('Not Synced','wcgs') : $content;
            break;
        default:
            break;
        }
    
        return $content;
    }
    
    
    function product_column($columns){
        $columns['wbps_column'] = __('Sync' , 'wbps');
        return  $columns;
    }
    
    //manage cpt custom column data callback
    function product_column_data( $column, $post_id){
    
        switch ($column) {
        case 'wbps_column':
            $rowno = get_post_meta($post_id,'wbps_row_id', true);
            if($rowno){
                echo esc_attr($rowno);
            }else{
                _e("Not synced", 'wbps');
            }
        break;
    }
}
    
}

function init_wpbs_admin(){
	return WBPS_Admin::__instance();
}