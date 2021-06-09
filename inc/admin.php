<?php
/** Admin related functions
 * */



function wcgs_admin_render_sync_widget($gs) {
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
    
    wp_enqueue_style('wcgs-style', WCGS_URL.'/css/wcgs.css' );

    echo '<div class="wcgs-sync-wrapper woocommmerce">';
    
    // if(!empty($wcgs_google_credential) && !empty($wcgs_googlesheet_id) && !empty($wcgs_imports_limit) && !empty($wcgs_redirect_url)){
    if(!empty($wcgs_google_credential) && !empty($wcgs_googlesheet_id) && !empty($wcgs_imports_limit) && !empty($wcgs_redirect_url)){
        
        wp_enqueue_script('wcgs-js', WCGS_URL.'/js/wcgs.js', array('jquery') );
        $wcgs_js_vars = array(
        	'plugin_url'				=> WCGS_URL
        	);
        wp_localize_script('wcgs-js', 'wcgs_widget_vars', $wcgs_js_vars);
        
        $gs = new WCGS_APIConnect();
        wcgs_admin_render_sync_widget($gs);
    }
    
    $wcgs_demo_sheet  = 'https://docs.google.com/spreadsheets/d/1sA55ZG3uo8JLr8eKyDkim0B2QcC1OtVVr26zufW0Fwo/edit?usp=sharing';
    $wcgs_demo_v3     = 'https://docs.google.com/spreadsheets/d/1JI02CBDVlPffSzgmLvSRx_zmB_4fPbQwpd2_cz_FYzw/edit?usp=sharing';
    $desc = sprintf(__('<a target="_blank" href="%s">Google Demo Sheet</a>', 'wcgs'), $wcgs_demo_sheet);
    $desc .= sprintf(__('| <a target="_blank" href="%s">Google Demo Sheet Version 3</a>', 'wcgs'), $wcgs_demo_v3);
    if( $sheet_id = get_option('wcgs_googlesheet_id') ) {
      $wcgs_demo_sheet = "https://docs.google.com/spreadsheets/d/{$sheet_id}/edit?usp=sharing";    
      $desc .= sprintf(__(' | <a target="_blank" href="%s">Connected Sheet</a>', 'wcgs'), $wcgs_demo_sheet);
    }
    
     printf(__('<p class="wcgs-connected-desc">%s</p>', 'wcgs'), $desc);
     
    echo '</div>';
    
    // if not authorized, no settings
    woocommerce_admin_fields(wcgs_array_settings());
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

function wcgs_admin_columns_css() {
	?>
	<style>
	    /* Meta in column */
		th.column-wcgs_column{ width: 10%!important;}
	</style><?php
}