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