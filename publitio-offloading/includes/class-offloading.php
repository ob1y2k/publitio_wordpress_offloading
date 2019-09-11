<?php
/**
 * @package Publitio
 */
require_once PLUGIN_PATH . '/includes/class-auth-service.php';

/**
 * Class Offload - handle media offloading
 */
class Offload
{
    /**
     * @var Instance of PublitioApiService class
     */
    private $publitioApi;

    /**
     * @var array of image sizes
     */
    private $sizes = array();

    public function __construct()
    {
        $this->publitioApi = new PublitioApiService();
        $this->register();
    }

    /**
     * Register all necessary filters and get all image sizes
     */
    public function register()
    {
        $this->sizes = $this->publitioApi->_get_all_image_sizes();
        add_filter('wp_calculate_image_srcset', array($this, 'wp_calculate_image_offloading_srcset'), 999, 5);
        add_filter('the_content', array($this, 'update_offloading_images_src'), 999);
    }

    /**
     * Calculate image srcset
     */
    public function wp_calculate_image_offloading_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $url = $image_src;
            $attachment = get_post($attachment_id);
            if ($attachment) {
                $publitioMeta = get_post_meta($attachment_id, 'publitioMeta', true);
                if (!$publitioMeta) {
                    $publitioMeta = $this->publitioApi->uploadFile($attachment);
                    if (!is_null($publitioMeta)) {
                        $publitioURL = $publitioMeta['publitioURL'];
                    }
                } else {
                    $publitioURL = $publitioMeta['publitioURL'];
                }
                if (!empty($sources)) {
                    foreach ($sources as $key => $source) {
                        $dimensions = $this->get_srcset_dimensions($image_meta, $source);
                        if (!empty($dimensions)) {
                            $crop = false;
                            if (!empty($dimensions['width']) && !empty($dimensions['height'])) {
                                foreach ($this->sizes as $size => $size_dimensions) {
                                    if ($dimensions['width'] === $size_dimensions['width'] && $dimensions['height'] === $size_dimensions['height']) {
                                        $crop = (bool)$size_dimensions['crop'];
                                        break;
                                    }
                                }
                                $dimensions['crop'] = $crop ? 'c_fill' : 'c_fit';
                                $url = $this->publitioApi->getImageUrl($dimensions, $publitioMeta);
                            } elseif (!empty($dimensions['width'])) {
                                $dimensions['crop'] = 'c_fit';
                                $url = $this->publitioApi->getImageUrl($dimensions, $publitioMeta);
                            } else {
                                $url = $publitioURL;
                            }
                        }
                        $sources[$key]['url'] = $url;
                    }
                }
            }
        }
        return $sources;
    }

    /**
     * Replace image src with publitio url
     * @param string $content
     * @return mixed|string
     */
    public function update_offloading_images_src($content = '')
    {
        $post_images = array();
        if (preg_match_all('/<img [^>]+>/', $content, $matches)) {
            $post_images = $matches[0];
        }
        if (empty($post_images)) {
            return $content;
        }
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            foreach ($post_images as $image) {
                if (preg_match('/wp-image-([0-9]+)/i', $image, $class_id) && ($attac_id = absint($class_id[1]))) { // @codingStandardsIgnoreLine
                    $attachment = get_post($attac_id);
                    if ($attachment) {
                        $src = preg_match('/ src="([^"]*)"/', $image, $match_src) ? $match_src[1] : '';
                        $publitioMeta = get_post_meta($attac_id, 'publitioMeta', true);
                        $width = preg_match('/ width="([0-9]+)"/', $image, $match_width) ? (int)$match_width[1] : 0;
                        $height = preg_match('/ height="([0-9]+)"/', $image, $match_height) ? (int)$match_height[1] : 0;
                        $class = preg_match('/ class="([^"]*)"/', $image, $match_class) ? $match_class[1] : '';

                        if (!$publitioMeta) {
                            $publitioMeta = $this->publitioApi->uploadFile($attachment);
                            if (!is_null($publitioMeta)) {
                                $publitioURL = $publitioMeta['publitioURL'];
                            } else {
                                $publitioURL = $src;
                            }
                        } else {
                            $publitioURL = $publitioMeta['publitioURL'];
                        }

                        if (!empty($class)) {
                            $size = preg_match('/size-([a-zA-Z0-9-_]+)?/', $class, $match_size) ? $match_size[1] : '';
                        } else {
                            $size = '';
                        }

                        if (!empty($src)) {
                            if (!empty($width) && !empty($height)) {
                                $crop = false;
                                if (!empty($size)) {
                                    $dimensions = $this->get_image_size($size);
                                    if (!empty($dimensions) && (bool)$dimensions['crop']) {
                                        $crop = true;
                                    }
                                }
                                $dimensions['width'] = $width;
                                $dimensions['height'] = $height;
                                $dimensions['crop'] = $crop ? 'c_fill' : 'c_fit';

                                $updated_src = $this->publitioApi->getImageUrl($dimensions, $publitioMeta);

                            } else {
                                $updated_src = $publitioURL;
                            }

                            if (!empty($updated_src)) {
                                $updated_image = str_replace($src, $updated_src, $image);
                                $content = str_replace($image, $updated_image, $content);
                            }
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Get size information for a specific image size.
     * @param $size
     * @return bool|mixed
     */
    private function get_image_size($size)
    {
        if (!empty($this->sizes) && isset($this->sizes[$size])) {
            return $this->sizes[$size];
        }
        return false;
    }

    /**
     * Get srcset dimensions
     * @return array
     */
    private function get_srcset_dimensions($image_meta = array(), $source = array())
    {
        $dimension = 'w' === $source['descriptor'] ? 'width' : 'height';
        foreach ($image_meta['sizes'] as $key => $size) {
            if ($size[$dimension] === $source['value']) {
                return array(
                    'width' => $size['width'],
                    'height' => $size['height'],
                );
            }
        }
        return array(
            $dimension => $source['value'],
        );
    }
}