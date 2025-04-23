<?php
class WBPS_Format {

    private static $ins = null;

    public static function __instance() {
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }

    public function __construct() {
        add_filter('wcgs_sync_data_products_before_processing', [$this, 'format_data_products'], 11, 2);
        add_filter('wcgs_products_data_attributes', [$this, 'product_attributes'], 99, 3);
        add_filter('wcgs_products_data_categories', [$this, 'product_extract_id_categories'], 99, 3);
        add_filter('wcgs_products_data_brands', [$this, 'product_extract_id_brands'], 99, 3);
        add_filter('wcgs_products_data_tags', [$this, 'product_extract_id_tags'], 99, 3);
        add_filter('wcgs_products_data_image', [$this, 'variation_image'], 99, 3);
        add_filter('wcgs_products_data_images', [$this, 'product_images'], 99, 3);
        add_filter('wcgs_products_data_dimensions', [$this, 'product_dimensions'], 99, 3);
        add_filter('wcgs_products_data_downloads', [$this, 'product_downloads'], 99, 3);

        if (wbps_pro_is_installed()) {
            add_filter('wbps_products_synback', [$this, 'syncback_data_products'], 11, 3);
            add_filter('wcgs_sync_data_categories_before_processing', [$this, 'format_data_categories'], 11, 2);
            add_filter('wcgs_categories_data_image', [$this, 'categories_image'], 99, 3);
        }
    }

    private function json_decode_safe($string) {
        if (is_string($string)) {
            $decoded = json_decode($string, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $string;
        }
        return $string;
    }

    public function format_data_products($sheet_data, $general_settings) {
        $taxonomy_found = wpbs_get_taxonomy_names();

        return array_map(function($item) use ($general_settings, $taxonomy_found) {
            foreach (wbps_fields_format_required() as $key => $type) {
                if (!isset($item[$key])) continue;
                $safe_key = sanitize_key($key);
                $item[$safe_key] = apply_filters("wcgs_products_data_{$safe_key}", $item[$key], $item, $general_settings);
            }

            foreach (wbps_fields_integer_array() as $key) {
                if (!isset($item[$key])) continue;
                $item[$key] = $this->parsing_integer_sting_to_array($item[$key], $item);
            }

            if (isset($item['meta_data']) && is_array($item['meta_data'])) {
                $item['meta_data'] = array_map(function ($meta) {
                    if (isset($meta['value'])) {
                        $meta['value'] = $this->json_decode_safe($meta['value']);
                    }
                    return $meta;
                }, $item['meta_data']);
            }

            return $item;
        }, $sheet_data);
    }

    public function format_data_categories($sheet_data, $general_settings) {
        return array_map(function($item) use ($general_settings) {
            foreach (wbps_fields_format_required() as $key => $type) {
                if (!isset($item[$key])) continue;
                $safe_key = sanitize_key($key);
                $item[$safe_key] = apply_filters("wcgs_categories_data_{$safe_key}", $item[$key], $item, $general_settings);
            }
            return $item;
        }, $sheet_data);
    }

    public function product_extract_id_categories($value, $row, $general_settings) {
        if (!$value) return $value;
        $return_value = $general_settings['categories_return_value'];

        if ($return_value === 'object') {
            return $this->json_decode_safe($value);
        } elseif ($return_value === 'name') {
            $value = wbps_get_taxonomy_ids_by_names('product_cat', $value);
        } else {
            $value = explode('|', $value);
        }

        return array_map(fn($id) => ['id' => trim($id)], $value);
    }

    public function product_extract_id_brands($value, $row, $general_settings) {
        if (!$value) return $value;
        $return_value = $general_settings['brands_return_value'] ?? 'id';

        if ($return_value === 'object') {
            return $this->json_decode_safe($value);
        } elseif ($return_value === 'name') {
            $value = wbps_get_taxonomy_ids_by_names('product_brand', $value);
        } else {
            $value = explode('|', $value);
        }

        return array_map(fn($id) => ['id' => trim($id)], $value);
    }

    public function product_extract_id_tags($value, $row, $general_settings) {
        if (!$value) return $value;
        $return_value = $general_settings['tags_return_value'];

        if ($return_value === 'object') {
            return $this->json_decode_safe($value);
        } elseif ($return_value === 'name') {
            $value = wbps_get_taxonomy_ids_by_names('product_tag', $value);
        } else {
            $value = explode('|', $value);
        }

        return array_map(fn($id) => ['id' => trim($id)], $value);
    }

    public function parsing_integer_sting_to_array($value, $row) {
        return $value ? explode('|', $value) : $value;
    }

    public function product_attributes($attributes, $row, $general_settings) {
        if (!$attributes) return [];
        return $this->json_decode_safe($attributes);
    }

    public function variation_image($image, $row, $general_settings) {
        if ($image === '') return $image;

        $image = esc_url_raw(trim($image));
        $key = filter_var($image, FILTER_VALIDATE_URL) ? 'src' : 'id';

        return [$key => $image];
    }

    public function product_images($images, $row, $general_settings) {
        if ($images === '') return $images;

        $make_array = explode('|', $images);
        return array_map(function($img) {
            $img = esc_url_raw(trim($img));
            $key = filter_var($img, FILTER_VALIDATE_URL) ? 'src' : 'id';
            return [$key => $img];
        }, $make_array);
    }

    public function categories_image($image, $row, $general_settings) {
        if ($image === '') return $image;

        $image = esc_url_raw(trim($image));
        $key = filter_var($image, FILTER_VALIDATE_URL) ? 'src' : 'id';

        return [$key => $image];
    }

    public function product_dimensions($dimensions, $row, $general_settings) {
        return $dimensions ? $this->json_decode_safe($dimensions) : $dimensions;
    }

    public function product_downloads($downloads, $row, $general_settings) {
        return $downloads ? $this->json_decode_safe($downloads) : $downloads;
    }

    public function syncback_data_products($products, $header, $settings) {
        $integerArrayFields = wbps_fields_integer_array();
        $formatRequiredFields = wbps_fields_format_required();

        foreach ($products as &$product) {
            foreach ($product as $key => &$value) {
                $key = sanitize_key(trim($key));

                $value = apply_filters("wcgs_products_syncback_value", $value, $key);

                if (in_array($key, $integerArrayFields, true)) {
                    $value = is_array($value) ? implode('|', $value) : $value;
                } elseif (isset($formatRequiredFields[$key])) {
                    $value = $value === null ? "" : $value;
                    $value = apply_filters("wcgs_products_syncback_value_{$key}", $value, $key, $settings);
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                }
            }
        }

        return $products;
    }
}

function init_wbps_format() {
    return WBPS_Format::__instance();
}