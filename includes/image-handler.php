<?php
/**
 * Enhanced Image Handler for WooCommerce Google Sync
 */

class WBPS_Image_Handler {
    
    public static function download_remote_image($url, $product_id = null) {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', 'Invalid image URL provided');
        }

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'image/*,*/*;q=0.8',
            ),
            'sslverify' => false,
        );

        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            wbps_logger_array("Error downloading image {$url}: " . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            wbps_logger_array("HTTP {$response_code} error for image: {$url}");
            return new WP_Error('http_error', "HTTP {$response_code} error");
        }

        $image_data = wp_remote_retrieve_body($response);
        if (empty($image_data)) {
            return new WP_Error('empty_response', 'Empty response');
        }

        $filename = basename(parse_url($url, PHP_URL_PATH));
        if (empty($filename) || strpos($filename, '.') === false) {
            $filename = 'image_' . time() . '.jpg';
        }

        $upload = wp_upload_bits($filename, null, $image_data);
        
        if ($upload['error']) {
            return new WP_Error('upload_error', $upload['error']);
        }

        $attachment = array(
            'post_mime_type' => wp_check_filetype($upload['file'])['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }

    public static function process_images_array($images_data, $product_id = null) {
        if (empty($images_data) || !is_array($images_data)) {
            return array();
        }

        $processed_images = array();
        
        foreach ($images_data as $image) {
            if (isset($image['src']) && !empty($image['src'])) {
                $attachment_id = self::download_remote_image($image['src'], $product_id);
                
                if (!is_wp_error($attachment_id)) {
                    $processed_images[] = array('id' => $attachment_id);
                } else {
                    wbps_logger_array("Failed to process image {$image['src']}: " . $attachment_id->get_error_message());
                }
            } elseif (isset($image['id']) && !empty($image['id'])) {
                $processed_images[] = array('id' => intval($image['id']));
            }
        }

        return $processed_images;
    }
}
