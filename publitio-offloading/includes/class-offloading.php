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
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $this->sizes = $this->publitioApi->_get_all_image_sizes();
            add_action('add_attachment', array($this, 'upload_file_to_publitio'));
            add_filter('wp_calculate_image_srcset', array($this, 'wp_calculate_image_offloading_srcset'), 999, 5);
            add_filter('the_content', array($this, 'update_offloading_images_src'), 999);
            add_filter('post_thumbnail_html', array($this, 'featured_image_update_url'), 999, 5);
            add_filter('get_header_image_tag', array($this, 'update_header_image_src'), 999, 5);
            if(get_option('publitio_offloading_allow_download') && get_option('publitio_offloading_allow_download') === 'no') {
                wp_enqueue_script('offloadingfrontscripts', PLUGIN_URL . 'includes/js/inc-script.js', array('jquery'));
            }
        }
    }

    /**
     * Replace custom header image  url if exist
     * @param $html
     * @param $header
     * @param $attr
     * @return mixed|string
     */
    public function update_header_image_src($html, $header, $attr)
    {
        $html = $this->update_offloading_images_src($html);
        return $html;
    }

    /**
     * Replace featured image url with Publitio URL
     * @param $html
     * @param $post_id
     * @param $post_thumbnail_id
     * @param $size
     * @param $attr
     * @return mixed
     */
    public function featured_image_update_url($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        $html = $this->update_offloading_images_src($html, $post_thumbnail_id);
        return $html;
    }

    /**
     * When file is uploaded to Media folder automatically upload it on Publitio
     * @param $attcID
     */
    public function upload_file_to_publitio($attcID)
    {
        $attachment = get_post($attcID);
        $this->getPublitioMeta($attachment);
    }

    /**
     * Calculate image srcset
     * @param $sources
     * @param $size_array
     * @param $image_src
     * @param $image_meta
     * @param $attachment_id
     * @return mixed
     */
    public function wp_calculate_image_offloading_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
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
                            $url = $publitioMeta['publitio_url'];
                        }
                    }
                    $sources[$key]['url'] = $url;
                }
            }
        }
        return $sources;
    }

    /**
     * Replace image src with Publitio url
     * @param string $content
     * @return mixed|string
     */
    public function update_offloading_images_src($content = '', $attach_id = '')
    {
        $post_images = $this->filter_attachments($content);
        if (empty($post_images)) {
            return $content;
        }
        foreach ($post_images as $image) {
            $src = preg_match('/ src="([^"]*)"/', $image, $match_src) ? $match_src[1] : '';
            $class_id = preg_match('/wp-image-([0-9]+)/i', $image, $match_class) ? $match_class[1] : 0;
            if (empty($src)) {
                $src = $image;
            }
            $poster_src = preg_match('/ poster="([^"]*)"/', $image, $match_poster) ? $match_poster[1] : '';
            if (!empty($poster_src)) {
                $poster_id = $this->get_attachment_id($poster_src);
                if ($poster_id && $poster_id !== 0) {
                    $poster = get_post($poster_id);
                    $publitioMetaPoster = $this->getPublitioMeta($poster);
                    $updated_poster = $publitioMetaPoster['publitio_url'];
                }
            }
            if (!empty($attach_id)) {
                $attachment_id = $attach_id;
            } elseif ($class_id && $class_id !== 0) {
                $attachment_id = $class_id;
            } else {
                $attachment_id = $this->get_attachment_id($src);
            }
            if ($attachment_id && $attachment_id !== 0) {
                $attachment = get_post($attachment_id);
                if ($attachment) {
                    $publitioMeta = $this->getPublitioMeta($attachment);
                    if (!is_null($publitioMeta)) {
                        $width = preg_match('/ width="([0-9]+)"/', $image, $match_width) ? (int)$match_width[1] : 0;
                        $height = preg_match('/ height="([0-9]+)"/', $image, $match_height) ? (int)$match_height[1] : 0;
                        $class = preg_match('/ class="([^"]*)"/', $image, $match_class) ? $match_class[1] : '';

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

                            $updated_src = $this->publitioApi->getImageUrl($dimensions, $publitioMeta);

                        } else {
                            $updated_src = $publitioMeta['publitio_url'];
                        }
                        if (!empty($updated_src)) {
                            if (!empty($updated_poster)) {
                                $updated_image = str_replace(array($src, $poster_src), array($updated_src, $updated_poster), $image);
                            } else {
                                $updated_image = str_replace($src, $updated_src, $image);
                            }
                            $content = str_replace($image, $updated_image, $content);
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Get Publitio Meta Data for attachment
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
     * Return array of images, background images, videos , audio files from content
     * @param $content
     * @return array
     */
    private function filter_attachments($content)
    {
        $images = array();
        $videos = array();
        $audios = array();
        $backgrounds = array();

        if (preg_match_all('/<img[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matchesImages)) {
            $images = $matchesImages[0];
        }
        if (preg_match_all('/<video[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matchesVideo)) {
            $videos = $matchesVideo[0];
        }
        if (preg_match_all('/<audio[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matchesAudio)) {
            $audios = $matchesAudio[0];
        }

        if (preg_match_all('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', $content, $matchesBackground)) {
            $backgrounds = $matchesBackground['image'];
        }

        $attachments = array_merge((array)$images, (array)$videos, (array)$audios, (array)$backgrounds);
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
     * Return attachment id from attachment url
     * @param $url
     * @return int
     */
    private function get_attachment_id($url)
    {
        $attach_id = attachment_url_to_postid($url);
        if ($attach_id && $attach_id !== 0) {
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