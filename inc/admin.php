<?php
/** Admin related functions
 * */
 
// function wcgs_admin_dashboard() {
 
//     if( ! current_user_can('administrator') ) return;
 
//         wp_add_dashboard_widget(
//                 'wcgs_google_sync',         // Widget slug.
//                 'Google Sheet Sync',         // Title.
//                 'wcgs_admin_render_sync_widget' // Display function.
//         );
// }


function wcgs_admin_render_sync_widget($gs) {
     
     wp_enqueue_script('wcgs-js', WCGS_URL.'/js/wcgs.js', array('jquery') );
     wp_enqueue_style('wcgs-style', WCGS_URL.'/css/wcgs.css' );
     
     $wcgs_js_vars = array(
			'plugin_url'				=> WCGS_URL
			);
    wp_localize_script('wcgs-js', 'wcgs_widget_vars', $wcgs_js_vars);
    
    wcgs_load_template_file('admin/google-sheet-settings.php', ['google_client'=>$gs]);
}

function wcgs_add_settings_tab($settings_tabs){
    $settings_tabs['wcgs_settings'] = __( 'Google Sync', 'wcgs' );
    return $settings_tabs;
}

function wcgs_settings_tab(){
    
    $wcgs_google_credential  = wcgs_get_option('wcgs_google_credential');
    $wcgs_googlesheet_id = wcgs_get_option('wcgs_googlesheet_id');
    $wcgs_imports_limit = wcgs_get_option('wcgs_imports_limit');
    $wcgs_redirect_url = get_rest_url(null, 'nkb/v1/auth');
    
    $gs = new GoogleSheet_API();
    if(!empty($wcgs_google_credential) && !empty($wcgs_googlesheet_id) && !empty($wcgs_imports_limit) && !empty($wcgs_redirect_url)){
        
        wcgs_admin_render_sync_widget($gs);
    }
    
    // if not authorized, no settings
    woocommerce_admin_fields(wcgs_array_settings());
    // if( ! $gs->auth_link ) {
    // }
}

function wcgs_save_settings(){
    woocommerce_update_options( wcgs_array_settings() );
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