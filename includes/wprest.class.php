<?php
/**
 * Rest API Handling
 */

if (!defined('ABSPATH')) die('Not Allowed.');

class WBPS_WP_REST {
    private static $ins = null;

    public static function __instance() {
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }

    public function __construct() {
        add_filter('woocommerce_rest_check_permissions', '__return_true');

        add_action('rest_api_init', [$this, 'init_api']);
    }

    function init_api() {
        foreach (wbps_get_rest_endpoints() as $endpoint) {
            register_rest_route('wbps/v1', $endpoint['slug'], array(
                'methods' => $endpoint['method'],
                'callback' => [$this, $endpoint['callback']],
                'permission_callback' => [$this, 'permission_check'],
            ));
        }
    }

    function permission_check($request) {
        return true;
    }

    function check_pro($request) {
        if (wbps_pro_is_installed()) {
            wp_send_json_success(get_option('wbps_woocommerce_keys'));
        } else {
            wp_send_json_error('Not installed');
        }
    }

    function connection_check($request) {
        $params = $request->get_params();
        if (empty($params)) wp_send_json_error(['message' => 'Invalid or empty request parameters.']);
        wp_send_json_success('connection_ok');
    }

    function verify_authcode($request) {
        $authcode = sanitize_text_field($request->get_param('authcode'));
        $saved = get_option('wbps_authcode');

        if ($authcode !== $saved) {
            wp_send_json_error(__('AuthCode is not valid', 'wbps'));
        }

        update_option('wbps_connection_status', 'verified');
        $wc_keys = get_option('wbps_woocommerce_keys');
        wp_send_json_success(['wc_keys' => $wc_keys, 'is_pro' => wbps_pro_is_installed()]);
    }

    function disconnect_store($request) {
        wpbs_disconnect();
        wp_send_json_success(__("Store is unlinked", "wbps"));
    }

    function product_sync($request) {
        $postMaxSizeBytes = wbps_return_bytes(ini_get('post_max_size'));
        if (strlen(file_get_contents('php://input')) > $postMaxSizeBytes) {
            wp_send_json_error(['message' => 'POST data exceeds server limit.']);
        }

        $chunk = json_decode(wp_unslash($request->get_param('chunk')), true);
        $general_settings = json_decode(wp_unslash($request->get_param('general_settings')), true);
        $chunk = array_replace(...$chunk);

        $products_ins = init_wbps_products();
        $response = $products_ins::sync($chunk, $general_settings);

        if (is_wp_error($response)) wp_send_json_error($response->get_error_message());
        wp_send_json_success($response);
    }

    function category_sync($request) {
        $chunk = json_decode(wp_unslash($request->get_param('chunk')), true);
        $general_settings = json_decode(wp_unslash($request->get_param('general_settings')), true);
        $chunk = array_replace(...$chunk);

        $categories_ins = init_wbps_categories();
        $response = $categories_ins::sync($chunk, $general_settings);

        if (is_wp_error($response)) wp_send_json_error($response->get_error_message());
        wp_send_json_success($response);
    }

    function prepare_fetch($request) {
        if (!wbps_pro_is_installed()) {
            $url = 'https://najeebmedia.com/wordpress-plugin/woocommerce-google-sync/';
            $msg = 'Pro Version not installed. <a href="' . esc_url($url) . '" target="_blank">Learn more</a>';
            wp_send_json_error(['message' => $msg]);
        }

        $sheet_name = sanitize_text_field($request->get_param('sheet_name'));
        $product_status = $request->get_param('product_status') ?? ['publish'];

        if ($request->get_param('refresh_fetch') === 'yes') {
            global $wpdb;
            $wpdb->delete("{$wpdb->prefix}postmeta", ['meta_key' => 'wbps_row_id']);
        }

        if ($sheet_name === 'products') {
            $response = wbps_get_syncback_product_ids($product_status);
            wp_send_json_success($response);
        }

        wp_send_json_error(['message' => 'Unsupported sheet name']);
    }

    function product_fetch($request) {
        $chunk = json_decode(wp_unslash($request->get_param('chunk')), true);
        $sheet_header = $request->get_param('sheet_header');
        $general_settings = $request->get_param('general_settings');
        $last_row = intval($request->get_param('last_row'));

        $products_ins = init_wbps_products();
        $response = $products_ins::fetch($chunk, $sheet_header, $general_settings, $last_row);
        wp_send_json_success(['products' => json_encode($response)]);
    }

    function category_fetch($request) {
        if (!wbps_pro_is_installed()) {
            $url = 'https://najeebmedia.com/wordpress-plugin/woocommerce-google-sync/';
            $msg = 'Pro Version not installed. <a href="' . esc_url($url) . '" target="_blank">Learn more</a>';
            wp_send_json_error(['message' => $msg]);
        }

        $sheet_header = $request->get_param('sheet_header');
        $general_settings = $request->get_param('general_settings');
        $last_row = intval($request->get_param('last_row'));

        if ($request->get_param('refresh_fetch') === 'yes') {
            global $wpdb;
            $wpdb->delete("{$wpdb->prefix}termmeta", ['meta_key' => 'wbps_row_id']);
        }

        $categories_ins = init_wbps_categories();
        $response = $categories_ins::fetch($sheet_header, $general_settings, $last_row);
        wp_send_json_success(['categories' => json_encode($response)]);
    }

    function attributes_fetch($request) {
        if (!wbps_pro_is_installed()) {
            $url = 'https://najeebmedia.com/wordpress-plugin/woocommerce-google-sync/';
            wp_send_json_error(['message' => 'Pro Version not installed. <a href="' . esc_url($url) . '" target="_blank">Learn more</a>']);
        }

        $attributes_data = [];
        foreach (wc_get_attribute_taxonomies() as $values) {
            $terms = get_terms(['taxonomy' => 'pa_' . $values->attribute_name, 'hide_empty' => false]);
            $attributes_data[] = [
                'id' => $values->attribute_id,
                'name' => $values->attribute_label,
                'terms' => wp_list_pluck($terms, 'name')
            ];
        }

        wp_send_json_success(['attributes' => json_encode($attributes_data)]);
    }

    function link_new_product($request) {
        $product_id = intval($request->get_param('product_id'));
        $row_id = intval($request->get_param('row_id'));
        $response = update_post_meta($product_id, 'wbps_row_id', $row_id);
        wp_send_json($response);
    }

    function webhook_callback($request) {
        $data = $request->get_params();
        delete_option('wbps_woocommerce_keys');
        update_option('wbps_woocommerce_keys', $data);
        return '';
    }

    function enable_webhook($request) {
        if (!wbps_pro_is_installed()) {
            wp_send_json_error(__('Pro version not active. <a target="_blank" href="https://najeebmedia.com/googlesync">Get Pro</a>', 'wbps'));
        }

        $url = esc_url_raw($request->get_param('webapp_url'));
        update_option('wbps_webhook_url', $url);
        wp_send_json_success('AutoFetch is enabled');
    }

    function disable_webhook($request) {
        delete_option('wbps_webhook_url');
        return '';
    }

    function save_sheet_props($request) {
        $data = $request->get_params();
        update_option('wbps_sheet_props', $data);
        wp_send_json_success(__("Properties updated successfully.", 'wbps'));
    }

    function relink_products($request) {
        global $wpdb;
        $data = json_decode(wp_unslash($request->get_param('product_links')), true);
        $postmeta_table = $wpdb->prefix . 'postmeta';
        $metakey = 'wbps_row_id';

        // Delete old
        $wpdb->query($wpdb->prepare("DELETE FROM {$postmeta_table} WHERE meta_key = %s", $metakey));

        // Insert new
        $placeholders = [];
        $values = [];
        foreach ($data as $link) {
            $placeholders[] = "(%d, %s, %s)";
            $values[] = intval($link['product_id']);
            $values[] = $metakey;
            $values[] = strval($link['row_id']);
        }

        if (!empty($placeholders)) {
            $sql = "INSERT INTO {$postmeta_table} (post_id, meta_key, meta_value) VALUES " . implode(',', $placeholders);
            $wpdb->query($wpdb->prepare($sql, ...$values));
        }

        wp_send_json_success(__("Properties updated successfully.", 'wbps'));
    }
}

function init_wbps_wp_rest() {
    return WBPS_WP_REST::__instance();
}
