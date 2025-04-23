<?php
class WBPS_Admin {

    private static $ins = null;

    public static function __instance() {
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }

    function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);

        
        // category columns
        add_filter('manage_edit-product_cat_columns', [$this, 'add_categories_columns']);
        add_filter('manage_product_cat_custom_column', [$this, 'categories_column_content'], 10, 3);

        // product columns
        add_filter('manage_product_posts_columns', [$this, 'product_column'], 20);
        add_filter('manage_product_posts_custom_column', [$this, 'product_column_data'], 20, 2);
    }

    function admin_menu() {
        $parent = 'woocommerce';
        $hook_setup = add_submenu_page(
            $parent,
            __('BulkProductSync - Bulk Product Manager with Google Sheets', 'wbps'),
            __('BulkProductSync', 'wbps'),
            'manage_woocommerce',
            'wbps-settings',
            [$this, 'settings_page'],
            35
        );
        add_action('load-' . $hook_setup, [$this, 'load_scripts']);
    }

    function load_scripts() {
        wp_enqueue_style('wbps-css', WBPS_URL . '/assets/wbps.css');
        wp_enqueue_script('wbps-js', WBPS_URL . '/assets/wbps.js', ['jquery'], WBPS_VERSION, true);
        wp_enqueue_script('wbps-goauth', '//apis.google.com/js/platform.js', null, WBPS_VERSION, false);
    }

    function settings_page() {
        $connection_status = get_option('wbps_connection_status');
        $template = $connection_status ? 'main' : 'setup';
        wbps_load_file("{$template}.php");
    }

    
    function add_categories_columns($columns) {
        $columns['wbps_row_id'] = 'Sync';
        return $columns;
    }

    function categories_column_content($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'wbps_row_id':
                $meta_value = get_term_meta($term_id, 'wbps_row_id', true);
                $content = is_null($meta_value) ? esc_html__('Not Synced', 'wcgs') : esc_html($meta_value);
                break;
        }
        return $content;
    }

    function product_column($columns) {
        $columns['wbps_column'] = __('Sync', 'wbps');
        return $columns;
    }

    function product_column_data($column, $post_id) {
        switch ($column) {
            case 'wbps_column':
                $rowno = get_post_meta($post_id, 'wbps_row_id', true);
                echo $rowno ? esc_html($rowno) : esc_html__('Not synced', 'wbps');
                break;
        }
    }
}

function init_wpbs_admin() {
    return WBPS_Admin::__instance();
}