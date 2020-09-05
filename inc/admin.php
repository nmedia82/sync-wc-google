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
    $wcgs_google_id = wcgs_get_option('wcgs_google_id');
    $wcgs_imports_limit = wcgs_get_option('wcgs_imports_limit');
    $wcgs_redirect_url = wcgs_get_option('wcgs_redirect_url');
    
    if(!empty($wcgs_google_credential) && !empty($wcgs_google_id) && !empty($wcgs_imports_limit) && !empty($wcgs_redirect_url)){
        
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
			'title' => '',
			'type'  => 'title',
			'desc'	=> __(''),
			'id'    => 'wcgs_labels_settings',
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
            'id'		=> 'wcgs_google_id',
            'css'   	=> 'min-width:300px;',
			'desc_tip'	=> true,
        ),
        array(
            'title'		=> __( 'Redirect URL:', 'wcgs' ),
            'type'		=> 'text',
            'desc'		=> __( '', 'wcgs' ),
            'default'	=> __('', 'wcgs'),
            'id'		=> 'wcgs_redirect_url',
            'css'   	=> 'min-width:300px;',
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
			'id'   => 'wcgs_pro',
		),
    	);
        
        
	return apply_filters('wcgs_settings_data', $wcgs_settings);
		
}