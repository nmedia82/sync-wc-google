<?php
/**
 * Helper functions
 **/

function wbps_logger_array($msg) {
    wc_get_logger()->debug(wc_print_r($msg, true), ['source' => 'WCBulkProductSync']);
}

function wbps_load_file($file_name, $vars = null) {
    if (is_array($vars)) extract($vars, EXTR_SKIP);

    $file_path = WBPS_PATH . '/templates/' . basename($file_name);
    if (file_exists($file_path)) {
        include $file_path;
    } else {
        wp_die(esc_html__('Template file not found: ', 'wbps') . esc_html($file_path));
    }
}

function wbps_pro_is_installed() {
    return defined('WCGS_PRO_VERSION') && intval(WCGS_PRO_VERSION) >= 7;
}

function wbps_fields_format_required() {
    return apply_filters('wbps_fields_format_required', [
        'categories' => 'array',
        'brands' => 'array',
        'upsell_ids' => 'array',
        'tags' => 'array',
        'downloads' => 'array',
        'images' => 'array',
        'attributes' => 'array',
        'image' => 'array',
        'meta_data' => 'array',
        'dimensions' => 'array'
    ]);
}

function wbps_fields_integer_array() {
    return apply_filters('wcgs_fields_integer_array', [
        'variations',
        'grouped_products',
        'cross_sell_ids',
        'upsell_ids',
        'related_ids'
    ]);
}

function wbps_get_syncback_product_ids($product_status = ['publish']) {
    global $wpdb;

    if (apply_filters('wbps_use_wp_query', true)) {
        $status_escaped = array_map(function($status) use ($wpdb) {
            return $wpdb->prepare('%s', $status);
        }, $product_status);

        $status_sql = implode(',', $status_escaped);
        $query = "SELECT DISTINCT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_status IN ({$status_sql})";
        $query = apply_filters('wbps_chunk_query', $query);

        $products = $wpdb->get_col($query);
    } else {
        $args = [
            'return'  => 'ids',
            'orderby' => 'id',
            'order'   => 'ASC',
            'limit'   => -1,
            'status'  => $product_status,
        ];
        $products = wc_get_products($args);
    }

    return apply_filters('wbps_get_syncback_product_ids', $products);
}

function wbps_get_webapp_url() {
    return esc_url_raw(get_option('wbps_webhook_url'));
}

function wbps_generate_wc_api_keys() {
    global $wpdb;

    $user_id = get_current_user_id();
    $consumerKey = 'ck_' . wp_generate_password(24, false);
    $consumerSecret = 'cs_' . wp_generate_password(37, false);

    $args = [
        'user_id' => $user_id,
        'description' => 'BPS Rest ' . current_time('mysql'),
        'permissions' => 'read_write',
        'consumer_key' => $consumerKey,
        'consumer_secret' => $consumerSecret,
        'truncated_key' => substr($consumerSecret, -7),
    ];

    $inserted = $wpdb->insert($wpdb->prefix . 'woocommerce_api_keys', $args);

    if ($inserted) {
        return [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
            'key_id' => $wpdb->insert_id,
        ];
    }

    return new WP_Error('api_key_generation_error', __('Error generating API keys.', 'wbps'), ['status' => 500]);
}

function wpbs_disconnect() {
    global $wpdb;

    $meta_key = 'wbps_row_id';
    $wpdb->delete($wpdb->prefix . 'postmeta', ['meta_key' => $meta_key]);
    $wpdb->delete($wpdb->prefix . 'termmeta', ['meta_key' => $meta_key]);

    delete_option('wbps_webhook_url');

    $wc_keys = get_option('wbps_woocommerce_keys');
    if (!empty($wc_keys['key_id'])) {
        $wpdb->delete($wpdb->prefix . 'woocommerce_api_keys', ['key_id' => intval($wc_keys['key_id'])], ['%d']);
    }

    delete_option('wbps_woocommerce_keys');
    delete_option('wbps_sheet_props');
    delete_option('wbps_connection_status');
}

function wbps_get_product_meta_col_value($product, $col_key) {
    $value = get_post_meta($product['id'], $col_key, true);
    if ($value) return $value;

    return array_reduce($product['meta_data'], function($acc, $meta) use ($col_key) {
        return ($meta->key === $col_key) ? $meta->value : $acc;
    });
}

function wbps_return_bytes($size) {
    $unit = strtoupper(substr($size, -1));
    $value = (int)substr($size, 0, -1);
    switch ($unit) {
        case 'K': return $value * 1024;
        case 'M': return $value * 1024 * 1024;
        case 'G': return $value * 1024 * 1024 * 1024;
        default:  return $value;
    }
}

function wbps_settings_link($links) {
    $url = esc_url(admin_url('admin.php?page=wbps-settings'));
    $links[] = sprintf('<a href="%s">%s</a>', $url, __('Connection Manager', 'wbps'));
    return $links;
}

function wbps_get_taxonomy_ids_by_names($taxonomy_type, $taxonomy_names) {
    global $wpdb;

    $names = array_map('sanitize_text_field', explode('|', $taxonomy_names));
    $placeholders = implode(',', array_fill(0, count($names), '%s'));
    $query = $wpdb->prepare(
        "SELECT t.term_id FROM {$wpdb->terms} t
         JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         WHERE tt.taxonomy = %s AND t.name IN ($placeholders)",
        array_merge([$taxonomy_type], $names)
    );

    return $wpdb->get_col($query);
}

function wbps_sync_processed_data($items, $action) {
    return array_map(function($item) use ($action) {
        if (!empty($item['error'])) {
            $message = sanitize_text_field($item['error']['message']) . ' product:' . intval($item['id']);
            return ['row' => 'ERROR', 'id' => intval($item['id']), 'message' => $message, 'action' => $action];
        }

        $row_id_meta = array_filter($item['meta_data'], fn($meta) => $meta->key === 'wbps_row_id');
        $row_id = reset($row_id_meta)->value ?? '';
        $images_ids = array_column($item['images'], 'id');
        $images_ids = apply_filters('wbps_images_ids', implode('|', $images_ids), $item);

        return ['row' => $row_id, 'id' => intval($item['id']), 'images' => $images_ids, 'action' => $action];
    }, $items);
}

function wbps_get_authcode() {
    $authcode = get_option('wbps_authcode');
    $wc_keys = get_option('wbps_woocommerce_keys');

    if (!$wc_keys) {
        $wc_keys = wbps_generate_wc_api_keys();
        update_option('wbps_woocommerce_keys', $wc_keys);
    }

    if ($authcode) return sanitize_text_field($authcode);

    $authcode = 'authcode_' . wp_generate_password(24, false);
    update_option('wbps_authcode', $authcode);
    return $authcode;
}

function wbps_get_sheet_props() {
    return get_option('wbps_sheet_props');
}

function wpbs_get_taxonomy_names() {
    $props = wbps_get_sheet_props();
    if (!$props || !isset($props['product_mapping'])) return [];

    $mapping = json_decode($props['product_mapping'], true);
    if (!is_array($mapping)) return [];

    return array_map(fn($item) => $item['key'], array_filter($mapping, fn($item) => $item['source'] === 'taxonomy'));
}

function wbps_decode_if_json($data) {
    $decoded = json_decode($data, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : sanitize_text_field($data);
}