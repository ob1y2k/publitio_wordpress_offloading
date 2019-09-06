<?php
/**
 * @package AnicaPlugin
 */


namespace Inc\Offload;

use Inc\Base\Api;

class Offload
{

    public function register()
    {
        add_filter('the_content', array($this, 'update_images_src'), 999);
        add_filter('wp_calculate_image_srcset', array($this, 'filter_wp_calculate_image_srcset'), 999, 5);
    }

    public function filter_wp_calculate_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        $api = new Api();
        $attachment = get_post($attachment_id);
        if ($attachment) {
            $publitioMeta = get_post_meta($attachment_id, '$publitioURL', true);
            if (!$publitioMeta) {
                $responseUpload = $api->uploadFile($attachment);
                $publitioURL = $responseUpload->url_preview;
                update_post_meta($attachment_id, '$publitioURL', $publitioURL);
            }
        }

        return $sources;
    }

    function update_images_src($content = '')
    {
        $post_images = array();
        if (preg_match_all('/<img [^>]+>/', $content, $matches)) {
            $post_images = $matches[0];
        }

        if (empty($post_images)) {
            return $content;
        }

        // replace image src with publitio url
        foreach ($post_images as $image) {
            if (preg_match('/wp-image-([0-9]+)/i', $image, $class_id) && ($attac_id = absint($class_id[1]))) { // @codingStandardsIgnoreLine
                $attachment = get_post($attac_id);
                if ($attachment) {
                    $publitioURL = get_post_meta($attac_id, '$publitioURL', true);
                    $src = preg_match('/ src="([^"]*)"/', $image, $match_src) ? $match_src[1] : '';
                    if($src) {
                        $newImage = str_replace($src, $publitioURL, $image);
                        $content = str_replace($image, $newImage, $content);
                    }
                }
            }
        }
        return $content;
    }

}