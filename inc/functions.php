<?php
/**
 * Helper functions
 * */
 

function wcgs_pa($arr) {
    echo '<pre>'; print_r($arr); echo '</pre>';
}


function wcgs_load_template_file($file_name, $vars=null) {
         
   if( is_array($vars))
    extract( $vars );
    
   $file_path =  WCGS_PATH . '/templates/'.$file_name;
   if( file_exists($file_path))
   	include ($file_path);
   else
   	die('File not found'.$file_path);
}

// Get sheet ID by title saved in option meta: wcgs_sheets_info
function wcgs_get_sheetid_by_title($title) {
    
    $gs_info = get_option('wcgs_sheets_info');
    $sheetId = '';
    if($gs_info) {
        
        foreach($gs_info as $id => $sheet_title) {
            
            if( $title === $sheet_title ) {
                $sheetId = $id;
                break;
            }
        }
    }
    
    return $sheetId;
}

/**
 * Get the product attribute ID from the name.
 *
 * @since 3.0.0
 * @param string $name | The name (slug).
 */
function wcgs_get_attribute_id_from_name( $name ){
    global $wpdb;
    $attribute_id = $wpdb->get_col("SELECT attribute_id
    FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
    WHERE attribute_name LIKE '$name'");
    return reset($attribute_id);
}

function wcgs_get_option($key, $default_val=false) {
	
    $value = get_option($key);
	if( ! $value ) {
		
		$value = $default_val;
	}
		
	$value = sprintf(__("%s", 'wcgs') , trim($value) );
	return $value;
}

// ignore header keys
// function wcgs_system_header_keys() {
//     return ['sync', 'last_sync','last_sync_source'];
// }
// // system header with keys
// function wcgs_system_header_key_values($key){
    
//     $header = ['sync' => 1, 'last_sync' => current_datetime(),'last_sync_source'=>get_bloginfo('name')];
//     return isset($header[$key]) ? $header[$key] : false;
// }

// WCGS Settings Admin
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