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
    
    $wcgs_googlesheet_id = wcgs_get_option('wcgs_googlesheet_id');
    $wcgs_imports_limit = wcgs_get_option('wcgs_imports_limit');
    $wcgs_demo_sheet     = 'https://docs.google.com/spreadsheets/d/1TFmZunnVr__BAV9bX6f_D5kWdshyFPSBxhb5DyYbU0g/edit?usp=sharing';
    
    wp_enqueue_script('wcgs-js', WCGS_URL.'/js/wcgs.js', ['jquery'], WCGS_VERSION, true );
    wp_enqueue_style('wcgs-style', WCGS_URL.'/css/wcgs.css' );

    echo '<div class="wcgs-sync-wrapper woocommmerce">';
    
    $gs = new WCGS_APIConnect();
    
    do_action('wcgs_before_sync_wrapper', $gs);
    
    if( ! $wcgs_googlesheet_id ) {
        printf(__('<a target="_blank" class="button button-primary wcgs-sheet-missing-btn" href="%s">%s</a>','wcgs'), $wcgs_demo_sheet, "Clone Sheet");
    }
    elseif( ! wcgs_is_service_connect() ) {
        printf(__('<a class="button button-primary wcgs-sync-btn" href="#">%s</a>','wcgs'), "Verify Connection");
    } else {
        printf(__('<p class="wcgs-connected">%s</p>', 'wcgs'), "Your Store Connected with Google Sheet");
    }

    echo '<div id="wcgs_working"></div>';
    
    $video_connect_url  = 'https://youtu.be/7J2H92wfOus';
    $video_guide_url  = 'https://youtu.be/pNdxG_otQ5c';
    $wcgs_demo_v5     = 'https://docs.google.com/spreadsheets/d/1TFmZunnVr__BAV9bX6f_D5kWdshyFPSBxhb5DyYbU0g/edit?usp=sharing';
    
    $desc = '';
    if( ! wcgs_is_service_connect() ) {
        $desc .= sprintf(__('<a target="_blank" href="%s">Connection Guide</a> | ', 'wcgs'), $video_connect_url);
    }
    
    $desc .= sprintf(__('<a target="_blank" href="%s">Video Tutorial</a>', 'wcgs'), $video_guide_url);
    $desc .= sprintf(__(' | <a target="_blank" href="%s">GoogleSheet Template</a>', 'wcgs'), $wcgs_demo_v5);
    if( $sheet_id = get_option('wcgs_googlesheet_id') ) {
      $wcgs_connected_sheet = "https://docs.google.com/spreadsheets/d/{$sheet_id}/edit?usp=sharing";    
      $desc .= sprintf(__(' | <a target="_blank" href="%s">Connected Sheet</a>', 'wcgs'), $wcgs_connected_sheet);
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