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
		
	$value = sprintf(__("%s", 'wcgs') , $value );
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