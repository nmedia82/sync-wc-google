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

