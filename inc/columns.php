<?php
/**
 * Adding extra columns
 * 
 **/
 
 class WCGS_Columns_Manager {
     
    private static $ins = null;
    
    public static function get_instance()
    {
        // create a new object if it doesn't exist.
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }
     
     function __construct() {
         
         add_filter( 'manage_edit-product_cat_columns', array($this, 'add_categories_columns') );
         add_filter('manage_product_cat_custom_column', array($this, 'categories_column_content'),10,3);
     }
     
     function add_categories_columns($columns){
        $columns['wcgs_row_id'] = 'Sync';
        return $columns;
     }
     
     function categories_column_content($content,$column_name,$term_id){
        switch ($column_name) {
            case 'wcgs_row_id':
                //do your stuff here with $term or $term_id
                $content = get_term_meta($term_id,'wcgs_row_id', true);
                $content = is_null($content) ? __('Not Synced','wcgs') : $content;
                break;
            default:
                break;
        }
        
        return $content;
    }
 }
 
 
 function WCGS_COLUMNS_INIT() {
    return WCGS_Columns_Manager::get_instance();
}