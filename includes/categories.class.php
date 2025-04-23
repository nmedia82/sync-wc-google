<?php

class WBPS_Categories {

    private static $ins = null;

    public static function __instance() {
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }

    public static function sync($chunk, $general_settings) {
        try {
            $categories = apply_filters('wcgs_sync_data_categories_before_processing', $chunk, $general_settings);

            $wcapi_data = [];
            $rowRef = [];

            foreach ($categories as $row_id => $row) {
                $id = isset($row['id']) ? intval($row['id']) : '';
                $name = isset($row['name']) ? sanitize_key($row['name']) : '';

                if ($id) {
                    $wcapi_data['update'][] = $row;
                    $rowRef[$id] = $row_id;
                } else {
                    $wcapi_data['create'][] = $row;
                    $rowRef[$name] = $row_id;
                }
            }

            $wcapi = new WBPS_WCAPI();
            $result = $wcapi->batch_update_categories($wcapi_data, $rowRef);

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }

            return $result;
        } catch (Exception $e) {
            return [
                'status' => "error",
                'message' => $e->getMessage()
            ];
        }
    }

    public static function fetch($header, $settings, $last_row) {
        $chunk_size = 100;
        $args_product_cat = ['taxonomy' => 'product_cat', 'hide_empty' => false];
        $total_cats = wp_count_terms($args_product_cat);
        $no_of_pages = floor($total_cats);

        $items = [];

        for ($i = 1; $i <= $no_of_pages; $i++) {
            $args = apply_filters('wbps_export_categories_args', [
                'per_page' => $chunk_size,
                'page' => $i,
                'include' => self::get_syncable_category_ids(),
            ]);

            $request = new WP_REST_Request('GET', '/wc/v3/products/categories');
            $request->set_query_params($args);
            $response = rest_do_request($request);

            if ($response->is_error()) {
                return new WP_Error('wcapi_categories_fetch_error', $response->as_error()->get_error_message());
            }

            $items = array_merge($items, $response->get_data());
        }

        $items = apply_filters('wbps_categories_list_before_syncback', $items);
        $sortby_id = array_column($items, 'id');
        array_multisort($sortby_id, SORT_ASC, $items);

        $header = array_fill_keys($header, '');
        $header['sync'] = 'OK';

        $categories = array_map(function ($item) use ($header) {
            return array_replace($header, array_intersect_key($item, $header));
        }, $items);

        return apply_filters('wbps_categories_synback', self::prepare_for_syncback($categories, $settings, $last_row), $header, $settings, $last_row);
    }

    public static function prepare_for_syncback($categories, $settings, $last_row) {
        $categories_refined = [];
        $row = $last_row;
        $link_new_data = [];

        foreach ($categories as $cat) {
            if (isset($cat['image'])) {
                $cat['image'] = apply_filters("wbps_categories_syncback_value_image", $cat['image'], 'image', $settings);
            }

            $wcgs_row_id = intval(get_term_meta($cat['id'], 'wbps_row_id', true));

            if ($wcgs_row_id) {
                $categories_refined['update'][$wcgs_row_id] = array_values($cat);
            } else {
                $row += 1;
                $link_new_data[$row] = intval($cat['id']);
                $categories_refined['create'][$row] = array_values($cat);
            }
        }

        self::link_category_with_sheet($link_new_data);
        return $categories_refined;
    }

    public static function link_category_with_sheet($row_catid) {
        if (empty($row_catid)) return;
    
        global $wpdb;
        $termmeta_table = $wpdb->prefix . 'termmeta';
        $metakey = 'wbps_row_id';
    
        $term_ids = array_map('intval', array_values($row_catid));
        $delete_placeholders = implode(',', array_fill(0, count($term_ids), '%d'));
    
        // Combine term_ids and metakey for prepare
        $delete_args = array_merge($term_ids, [$metakey]);
    
        // Delete existing meta
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$termmeta_table} WHERE term_id IN ($delete_placeholders) AND meta_key = %s",
                ...$delete_args
            )
        );
    
        // Prepare insert query
        $values = [];
        $placeholders = [];
        foreach ($row_catid as $row_id => $cat_id) {
            $placeholders[] = "(%d, %s, %s)";
            $values[] = intval($cat_id);
            $values[] = $metakey;
            $values[] = strval($row_id);
        }
    
        $sql = "INSERT INTO {$termmeta_table} (term_id, meta_key, meta_value) VALUES " . implode(', ', $placeholders);
        $wpdb->query($wpdb->prepare($sql, ...$values));
    }


    public static function get_syncable_category_ids() {
        global $wpdb;

        $qry = "SELECT DISTINCT tt.term_id 
                FROM {$wpdb->prefix}term_taxonomy AS tt 
                WHERE tt.taxonomy = %s";

        $results = $wpdb->get_results($wpdb->prepare($qry, 'product_cat'), ARRAY_N);

        return apply_filters('get_syncable_category_ids', array_map(fn($r) => intval($r[0]), $results));
    }
}

function init_wbps_categories() {
    return WBPS_Categories::__instance();
}