<?php
/** Admin related functions
 * */
 
function wcgs_admin_dashboard() {
 
    if( ! current_user_can('administrator') ) return;
 
        wp_add_dashboard_widget(
                'wcgs_google_sync',         // Widget slug.
                'Google Sheet Sync',         // Title.
                'wcgs_admin_render_sync_widget' // Display function.
        );
}


function wcgs_admin_render_sync_widget() {
     
     wp_enqueue_script('wcgs-widget', WCGS_URL.'/js/widget.js', array('jquery') );
     $wcgs_js_vars = array(
			'plugin_url'				=> WCGS_URL
			);
    wp_localize_script('wcgs-widget', 'wcgs_widget_vars', $wcgs_js_vars);
    
    wcgs_load_template_file('admin/google-sheet-widget.php');
}

function wcgs_add_settings_tab($settings_tabs){
    $settings_tabs['wcgs_settings'] = __( 'Google Products', 'wcgs' );
    return $settings_tabs;
}

function wcgs_settings_tab(){
    
    $wcgs_google_credential  = wcgs_get_option('wcgs_google_credential');
    $wcgs_googlesheet_id = wcgs_get_option('wcgs_googlesheet_id');
    $wcgs_imports_limit = wcgs_get_option('wcgs_imports_limit');
    $wcgs_redirect_url = get_rest_url(null, 'nkb/v1/auth');
    
    if(!empty($wcgs_google_credential) && !empty($wcgs_googlesheet_id) && !empty($wcgs_imports_limit) && !empty($wcgs_redirect_url)){
        
        wcgs_admin_render_sync_widget();
    }
    
    woocommerce_admin_fields(wcgs_array_settings());
}

function wcgs_save_settings(){
    woocommerce_update_options( wcgs_array_settings() );
}

// WCGS Settings
function wcgs_array_settings() {
	

	$wcgs_settings = array(
       
		array(
			'title' => 'Google Credentials',
			'type'  => 'title',
			'desc'	=> __(''),
			'id'    => 'wcgs_google_creds',
		),
		
		array(
            'title'		=> __( 'Google Credentials:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'Copy/paste google credentials you downloaded from Google Console', 'wcgs' ),
            'default'	=> __('', 'wcgs'),
            'id'		=> 'wcgs_google_credential',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
        array(
            'title'		=> __( 'Google Sheet ID:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'Paste here the Google Sheet ID to import products/categories from', 'wcgs' ),
            'default'	=> __('', 'wcgs'),
            'id'		=> 'wcgs_googlesheet_id',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
        array(
            'title'		=> __( 'Redirect URL:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'Copy this redirect URL and paste into Google credentials as per guide.', 'wcgs' ),
            'default'	=> get_rest_url(null, 'nkb/v1/auth'),
            'id'		=> 'wcgs_redirect_url',
            'css'   	=> 'min-width:300px;',
            'custom_attributes' => array('readonly' => 'readonly'),
			'desc_tip'	=> true,
        ),
        array(
            'title'             => __( 'Imports Limit', 'wcgs' ),
            'type'              => 'select',
            'label'             => __( 'Button', 'wcgs' ),
            'default'           => 'daily',
            'options' => array( '20'=>__('20','wcgs'),
                                '50'=> __('50','wcgs'),
                              
                            ),
            'id'       => 'wcgs_imports_limit',
            'desc'       => __( 'Set product import limit at a single sync.', 'wcgs' ),
            'desc_tip'      => true,
        ),
            
        array(
			'type' => 'sectionend',
			'id'   => 'wcgs_google_creds',
		),
		
		array(
			'title' => 'WooCommerce API Credentials',
			'type'  => 'title',
			'desc'	=> __(''),
			'id'    => 'wcgs_woocommerce_creds',
		),
		
		array(
            'title'		=> __( 'WooCommerce Consumer Key:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'WooCommerce Consumer Key generated from REST API', 'wcgs' ),
            'default'	=> __('', 'wcgs'),
            'id'		=> 'wcgs_wc_ck',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
        
        array(
            'title'		=> __( 'WooCommerce Secret Key:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( 'WooCommerce Secret Key generated from REST API', 'wcgs' ),
            'default'	=> __('', 'wcgs'),
            'id'		=> 'wcgs_wc_sk',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
        
        array(
			'type' => 'sectionend',
			'id'   => 'wcgs_woocommerce_creds',
		),
    	);
        
        
	return apply_filters('wcgs_settings_data', $wcgs_settings);
		
}

// Show notices
function wcgs_admin_show_notices() {
    
    if ( $resp_notices = get_transient( "wcgs_admin_notices" ) ) {
		?>
		<div id="message" class="<?php echo $resp_notices['class']; ?> updated notice is-dismissible">
		    <p><?php echo $resp_notices['message']; ?></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'ppom' ); ?></span>
			</button>
		</div>
	<?php
	
	    delete_transient("wcgs_admin_notices");
	}
}