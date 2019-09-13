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
     * Register all necessary filters and actions and get all image sizes
     */
    public function register()
    {
        $this->sizes = $this->publitioApi->_get_all_image_sizes();
        add_action('add_attachment', array($this, 'upload_file_to_publitio'));
        add_filter('wp_calculate_image_srcset', array($this, 'wp_calculate_image_offloading_srcset'), 999, 5);
        add_filter('the_content', array($this, 'update_offloading_images_src'), 999);
    }

    /**
     * When file is uploaded to media upload it to Publitio
     * @param $attcID
     */
    public function upload_file_to_publitio($attcID)
    {
        $attachment = get_post($attcID);
        $publitioMeta = $this->getPublitioMeta($attachment);
    }

    /**
     * Calculate image srcset
     */
    public function wp_calculate_image_offloading_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $attachment = get_post($attachment_id);
            if ($attachment) {
                $publitioMeta = $this->getPublitioMeta($attachment);
                if (!empty($sources) && !is_null($publitioMeta)) {
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
                                $url = $publitioMeta['publitioURL'];
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
        $post_urls = $this->filter_attachemnts($content);
        if (empty($post_urls)) {
            return $content;
        }
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            foreach ($post_urls as $post_url) {
                $attachment_id = $this->get_attachment_id($post_url);
                if ($attachment_id) {
                    $attachment = get_post($attachment_id);
                    if ($attachment) {
                        $publitioMeta = $this->getPublitioMeta($attachment);
                        if (!is_null($publitioMeta)) {
                            $width = preg_match('/ width="([0-9]+)"/', $post_url, $match_width) ? (int)$match_width[1] : 0;
                            $height = preg_match('/ height="([0-9]+)"/', $post_url, $match_height) ? (int)$match_height[1] : 0;
                            $class = preg_match('/ class="([^"]*)"/', $post_url, $match_class) ? $match_class[1] : '';

                            if (!empty($class)) {
                                $size = preg_match('/size-([a-zA-Z0-9-_]+)?/', $class, $match_size) ? $match_size[1] : '';
                            } else {
                                $size = '';
                            }

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

                                $updated_url = $this->publitioApi->getImageUrl($dimensions, $publitioMeta);

                            } else {
                                $updated_url = $publitioMeta['publitioURL'];
                            }
                            if (!empty($updated_url)) {
                                $content = str_replace($post_url, $updated_url, $content);
                            }
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Get Publitio Meta Data for attachement
     * @param $attachment
     * @return array|mixed|null
     */
    private function getPublitioMeta($attachment)
    {
        $publitioMeta = get_post_meta($attachment->ID, 'publitioMeta', true);
        if (!$publitioMeta) {
            $publitioMeta = $this->publitioApi->uploadFile($attachment);
            if (is_null($publitioMeta)) {
                $publitioMeta = null;
            }
        }
        return $publitioMeta;
    }

    /**
     * Return array of image, video and audio urls from content
     * @param $content
     * @return array
     */
    private function filter_attachemnts($content)
    {
        $images = array();
        $videos = array();
        $audios = array();

        if (preg_match_all('/<img[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matchesImages)) {
            $images = $matchesImages['src'];
        }
        if (preg_match_all('/<video[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matchesVideo)) {
            $videos = $matchesVideo['src'];
        }
        if (preg_match_all('/<audio[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matchesAudio)) {
            $audios = $matchesAudio['src'];
        }

        $attachments = array_merge((array)$images, (array)$videos, (array)$audios);
        return $attachments;
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

    /**
     * Return attachment id from url
     * @param $url
     * @return int
     */
    private function get_attachment_id($url)
    {
        $attach_id = attachment_url_to_postid($url);
        if ($attach_id) {
            $attachment_id = $attach_id;
        } else {
            $attachment_id = 0;
            $dir = wp_upload_dir();
            if (false !== strpos($url, $dir['baseurl'] . '/')) {
                $file = basename($url);
                $query_args = array(
                    'post_type' => 'attachment',
                    'post_status' => 'any',
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'value' => $file,
                            'compare' => 'LIKE',
                            'key' => '_wp_attachment_metadata',
                        ),
                    )
                );
                $query = new WP_Query($query_args);
                if ($query->have_posts()) {
                    foreach ($query->posts as $post_id) {
                        $meta = wp_get_attachment_metadata($post_id);
                        $original_file = basename($meta['file']);
                        $cropped_image_files = wp_list_pluck($meta['sizes'], 'file');
                        if ($original_file === $file || in_array($file, $cropped_image_files)) {
                            $attachment_id = $post_id;
                            break;
                        }
                    }
                }
            }
        }
        return $attachment_id;
    }
}