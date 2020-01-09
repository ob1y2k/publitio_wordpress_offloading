<?php
/**
 * @package Publitio
 */
require_once PUBLITIO_OFFLOADING_PLUGIN_PATH . '/includes/class-publitio-offloading-auth-service.php';

/**
 * Class Offload - handle media offloading
 */
class PWPO_Offload
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
        $this->pwpo_register();
    }

    /**
     * Register all necessary filters and actions and get all image sizes
     */
    public function pwpo_register()
    {
        if (PWPO_AuthService::is_user_authenticated()) {
            add_action('add_attachment', array($this, 'pwpo_upload_file_to_publitio'));
            if (get_option('publitio_offloading_delete_checkbox') === 'yes') {
                add_action('delete_post_meta', array($this, 'pwpo_delete_file_from_publitio'), 10, 4);
            }

            add_filter('the_content', array($this, 'pwpo_update_offloading_images_src'), 10);
            add_filter('wp_calculate_image_srcset', array($this, 'pwpo_calculate_image_offloading_srcset'), 10, 5);
            add_filter('image_downsize', array($this, 'pwpo_filter_image_downsize'), 10, 3);


            add_filter('post_thumbnail_html', array($this, 'pwpo_featured_image_update_url'), 10, 5);
            add_filter('get_header_image_tag', array($this, 'pwpo_update_header_image_src'), 10, 5);
            if (get_option('publitio_offloading_allow_download') && get_option('publitio_offloading_allow_download') === 'no') {
                wp_enqueue_script('offloadingfrontscripts', PUBLITIO_OFFLOADING_PLUGIN_URL . 'includes/js/inc-script.js', array('jquery'));
            }
            add_filter('wp_get_attachment_url', array($this, 'pwpo_get_url'), 10, 2);
            //add_action('the_post', array($this, 'pwpo_edit_post_content'), 10, 1);
        }
    }

    /**
     * Return Publitio URL for attachment if file is deleted from uploads folder
     * @param $url
     * @return string
     */
    public function pwpo_get_url($url)
    {
        $attachment_id = $this->get_attachment_id($url);
        $attachment = get_post($attachment_id);
        $attach = get_attached_file($attachment_id);
        $url_updated = $url;
        if (file_exists($attach)) {
            if (get_option('publitio_offloading_replace_checkbox') === 'yes') {
                $filetype = wp_check_filetype($attachment->guid);
                $publitioMetaFile = $this->getPublitioMeta($attachment);
                $delete = false;
                if(!is_null($publitioMetaFile)) {
                    $delete = true;
                }
                if (get_option('publitio_offloading_image_checkbox') && get_option('publitio_offloading_image_checkbox') === 'no') {
                    if ($this->publitioApi->isImageType($filetype['ext'])) {
                        $delete = false;
                    }
                }

                if (get_option('publitio_offloading_video_checkbox') && get_option('publitio_offloading_video_checkbox') === 'no') {
                    if ($this->publitioApi->isVideoType($filetype['ext'])) {
                        $delete = false;
                    }
                }

                if (get_option('publitio_offloading_audio_checkbox') && get_option('publitio_offloading_audio_checkbox') === 'no') {
                    if ($this->publitioApi->isAudioType($filetype['ext'])) {
                        $delete = false;
                    }
                }

                if (get_option('publitio_offloading_document_checkbox') && get_option('publitio_offloading_document_checkbox') === 'no') {
                    if ($this->publitioApi->isDocumentType($filetype['ext'])) {
                        $delete = false;
                    }
                }
                if ($delete === true) {
                    $this->publitioApi->deleteAtachment($attachment_id, false);
                } else {
                    $url_updated = $url;
                }
            } else {
                $url_updated = $url;
            }
        } else {
            $publitioMeta = get_post_meta($attachment->ID, 'publitioMeta', true);
            if (!is_null($publitioMeta) && $publitioMeta !== '') {
                $url_updated = $this->publitioApi->getTransformedUrl(null, $publitioMeta);
            }
        }
        return $url_updated;
    }

    /**
     * Edit post content and replace media url with Publitio URL
     * @param $post
     * @return mixed
     */
    public function pwpo_edit_post_content($post)
    {
        $content = $post->post_content;
        $updated = $this->pwpo_update_post_content($content);
        $post->post_content = $updated;
        return $post;
    }

    /**
     * Return Publitio URL for different media sizes
     * @param $downsize
     * @param $attach_id
     * @param $size
     * @return array|bool
     */
    public function pwpo_filter_image_downsize($downsize, $attach_id, $size)
    {
        $attach = get_attached_file($attach_id);
        $attachment = get_post($attach_id);
        $publitioMeta = get_post_meta($attachment->ID, 'publitioMeta', true);
        if ($publitioMeta && !is_null($publitioMeta)) {
            $dimensions = array();
            if (is_array($size)) {
                $crop = false;
                if(isset($size['crop'])) {
                    $crop = $size['crop'];
                }
                $dimensions = array(
                        'width' => $size[0],
                        'height' => $size[1],
                        'crop' => $crop ? 'c_fill' : 'c_fit'
                    );
            } elseif ('full' === $size) {
                $meta = wp_get_attachment_metadata($attach_id);
                if (isset($meta['width']) && isset($meta['height'])) {
                    $dimensions = array(
                        'width' => $meta['width'],
                        'height' => $meta['height'],
                        'crop' => 'c_fit'
                    );
                }
            } else {
                if (!file_exists($attach) && $size && $size !== "") {
                    $dimensions = $this->get_image_size($size);
                    $crop = false;
                    if ($dimensions && !empty($dimensions) && (bool)$dimensions['crop']) {
                        $crop = true;
                        $dimensions['crop'] = $crop ? 'c_fill' : 'c_fit';
                    } else {
                        $dimensions = null;
                    }
                } else {
                    return null;
                }
            }

            return array(
                $this->publitioApi->getTransformedUrl($dimensions, $publitioMeta),
                $dimensions['width'],
                $dimensions['height'],
            );
        }
    }


    /**
     * When file is uploaded to Media folder automatically upload it on Publitio
     * @param $attcID
     */
    public function pwpo_upload_file_to_publitio($attcID)
    {
        $attachment = get_post($attcID);
        $this->getPublitioMeta($attachment);
    }

    /**
     * Call delete file function if attachment has Publitio meta
     * @param $deleted_meta_ids
     * @param $post_id
     * @param $meta_key
     * @param $only_delete_these_meta_values
     */
    public function pwpo_delete_file_from_publitio($deleted_meta_ids, $post_id, $meta_key, $only_delete_these_meta_values)
    {
        if($meta_key === 'publitioMeta') {
            $publitioMeta = $only_delete_these_meta_values;
            if ($publitioMeta) {
                $responseShow = $this->publitioApi->showFile( $publitioMeta['id']);
                if ($responseShow->success === true) {
                    $this->publitioApi->deleteFileFromPublitio($publitioMeta['id']);
                }
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
    public function pwpo_update_header_image_src($html, $header, $attr)
    {
        $html = $this->pwpo_update_offloading_images_src($html);
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
    public function pwpo_featured_image_update_url($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        $html = $this->pwpo_update_offloading_images_src($html, $post_thumbnail_id);
        return $html;
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
    public function pwpo_calculate_image_offloading_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
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
                            $url = $this->publitioApi->getTransformedUrl($dimensions, $publitioMeta);
                        } elseif (!empty($dimensions['width'])) {
                            $dimensions['crop'] = 'c_fit';
                            $url = $this->publitioApi->getTransformedUrl($dimensions, $publitioMeta);
                        } else {
                            $url = $this->publitioApi->getTransformedUrl(null, $publitioMeta);
                        }
                    }
                    $sources[$key]['url'] = $url;
                }
            }
        }
        return $sources;
    }

    /**
     * Replace image src with Publitio URL
     * @param string $content
     * @return mixed|string
     */
    public function pwpo_update_offloading_images_src($content = '', $attach_id = '')
    {
        $post_images = $this->filter_attachments($content);
        if (empty($post_images)) {
            return $content;
        }
        foreach ($post_images as $image) {
            $src = preg_match('/ src="([^"]*)"/', $image, $match_src) ? $match_src[1] : '';
            $src_set = preg_match('/ srcset="([^"]*)"/', $image, $match_src) ? $match_src[1] : '';
            $class_id = preg_match('/wp-image-([0-9]+)/i', $image, $match_class) ? $match_class[1] : 0;

            if (empty($src) || $src === "") {
                $src = $image;
            }

            if (!empty($attach_id)) {
                $attachment_id = $attach_id;
            } elseif ($class_id && $class_id !== 0) {
                $attachment_id = $class_id;
            } else {
                $attachment_id = $this->get_attachment_id($src);
            }

            if (strpos($src, 'wp-content') === false) {
                $content = $this->updatePublitioUrl($content,$image,$src);
            } else {
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

                            if ((!empty($width) && $width !== 0 && $width !== 'auto') && (!empty($height) && $height !== 0 && $height !== 'auto')) {
                                $crop = false;
                                if (!empty($size)) {
                                    $dimensions = $this->get_image_size($size);
                                    if (!empty($dimensions) && (bool)$dimensions['crop']) {
                                        $crop = true;
                                    }
                                }
                                if($width === $height) {
                                    $crop = true;
                                }
                                $dimensions['width'] = $width;
                                $dimensions['height'] = $height;
                                $dimensions['crop'] = $crop ? 'c_fill' : 'c_fit';

                                $updated_src = $this->publitioApi->getTransformedUrl($dimensions, $publitioMeta);
                            } else if ($attach_id !== 0) {
                                $metadata = wp_get_attachment_metadata($attachment_id);
                                if(isset($metadata['sizes'])) {
                                    $sizesMeta = $metadata['sizes'];
                                    if ($sizesMeta) {
                                        foreach ($sizesMeta as $sizeMeta) {
                                            $file = $sizeMeta['file'];
                                            $dimensions = null;
                                            if (strpos($src, $file) !== false) {
                                                $dimensions['width'] = $sizeMeta['width'];
                                                $dimensions['height'] = $sizeMeta['height'];
                                                $dimensions['crop'] = 'c_fill';
                                                break;
                                            }
                                        }
                                        if(!is_null($dimensions) && $dimensions) {
                                            $updated_src = $this->publitioApi->getTransformedUrl($dimensions, $publitioMeta);
                                        } else {
                                            $updated_src = $this->publitioApi->getTransformedUrl(null, $publitioMeta);
                                        }
                                    }
                                } else {
                                    $updated_src = $this->publitioApi->getTransformedUrl(null, $publitioMeta);
                                }
                            } else {
                                $updated_src = $this->publitioApi->getTransformedUrl(null, $publitioMeta);
                            }
                            if (!empty($updated_src) && !is_null($updated_src)) {
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
     * Update post content with Publitio URL if image do not exist
     * @param string $content
     * @return mixed|string
     */
    public function pwpo_update_post_content($content = '')
    {
        $post_images = $this->filter_attachments($content);
        if (empty($post_images)) {
            return $content;
        }
        foreach ($post_images as $image) {
            $src = preg_match('/ src="([^"]*)"/', $image, $match_src) ? $match_src[1] : '';
            $class_id = preg_match('/wp-image-([0-9]+)/i', $image, $match_class) ? $match_class[1] : 0;
            if (empty($src) || $src === "") {
                $src = $image;
            }

            if (!empty($attach_id)) {
                $attachment_id = $attach_id;
            } elseif ($class_id && $class_id !== 0) {
                $attachment_id = $class_id;
            } else {
                $attachment_id = $this->get_attachment_id($src);
            }

            $quit = false;
            $attach = get_attached_file($attachment_id);
            if (file_exists($attach)) {
                $quit = true;
            }

            if ($quit === true) {
                break;
            } else {
                if (strpos($src, 'wp-content') === false) {
                    $content = $this->updatePublitioUrl($content,$image,$src);
                } else {
                    if ($attachment_id && $attachment_id !== 0) {
                        $attachment = get_post($attachment_id);
                        if ($attachment) {
                            $publitioMeta = $this->getPublitioMeta($attachment);
                            if (!is_null($publitioMeta)) {
                                $updated_src = $this->publitioApi->getTransformedUrl(null, $publitioMeta);
                            }
                        }
                    }
                    if (!empty($updated_src) && !is_null($updated_src)) {
                        $updated_image = str_replace($src, $updated_src, $image);
                        $content = str_replace($image, $updated_image, $content);
                    }
                }
            }

        }
        return $content;
    }

    /**
     * Transform Publitio URL with choosen parameters from plugin settings
     * @param $content
     * @param $src
     * @return mixed
     */
    private function updatePublitioUrl($content,$image,$src) {
        $valueQ = get_option('publitio_offloading_image_quality') ? get_option('publitio_offloading_image_quality') : '80';
        $valueV = get_option('publitio_offloading_video_quality') ? get_option('publitio_offloading_video_quality') : '480';

        if (preg_match("/(.file\/.*?)[^\/]*/", $src, $matches)) {
            $file_path = $matches[0];
            if (preg_match("/q_\d{1,3}/", $file_path, $matchesQ)) {
                $q_value = $matchesQ[0];
                $new_file_path = str_replace($q_value, "q_" . $valueQ, $file_path);
                $new_src = str_replace($file_path, $new_file_path, $src);
                $updated_image = str_replace($src, $new_src, $image);
                $content = str_replace($image, $updated_image, $content);
            }
            if (strpos($image, 'video') !== false) {
                if (preg_match("/h_\d{3,4}/", $file_path, $matchesV)) {
                    $v_value = $matchesV[0];
                    $new_file_path = str_replace($v_value, "h_" . $valueV, $file_path);
                    $new_src = str_replace($file_path, $new_file_path, $src);
                    $updated_image = str_replace($src, $new_src, $image);
                    $content = str_replace($image, $updated_image, $content);
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
        $filetype = wp_check_filetype($attachment->guid);

        if (get_option('publitio_offloading_image_checkbox') && get_option('publitio_offloading_image_checkbox') === 'no') {
            if ($this->publitioApi->isImageType($filetype['ext'])) {
                return null;
            }
        }

        if (get_option('publitio_offloading_video_checkbox') && get_option('publitio_offloading_video_checkbox') === 'no') {
            if ($this->publitioApi->isVideoType($filetype['ext'])) {
                return null;
            }
        }

        if (get_option('publitio_offloading_audio_checkbox') && get_option('publitio_offloading_audio_checkbox') === 'no') {
            if ($this->publitioApi->isAudioType($filetype['ext'])) {
                return null;
            }
        }

        if (get_option('publitio_offloading_document_checkbox') && get_option('publitio_offloading_document_checkbox') === 'no') {
            if ($this->publitioApi->isDocumentType($filetype['ext'])) {
                return null;
            }
        }

        $publitioMeta = get_post_meta($attachment->ID, 'publitioMeta', true);
        if (!$publitioMeta) {
            $publitioMeta = $this->publitioApi->uploadFile($attachment);
            if (is_null($publitioMeta)) {
                return null;
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
        $posters = array();
        $audios = array();
        $backgrounds = array();
        $pdfFiles = array();

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

        if (preg_match_all('/<a\shref=\"([^\"]*)\">(.*)<\/a>/siU', $content, $matchesPdf)) {
            $pdfFiles = $matchesPdf[1];
        }

        if (preg_match_all('/<video[^>]+poster=([\'"])(?<poster>.+?)\1[^>]*>/i', $content, $matchesPoster)) {
            $posters = $matchesPoster['poster'];
        }

        $attachments = array_merge((array)$images, (array)$videos, (array)$audios, (array)$backgrounds, (array)$pdfFiles, (array)$posters);
        return $attachments;
    }

    /**
     * Get size information for a specific image size.
     * @param $size
     * @return bool|mixed
     */
    private function get_image_size($size)
    {
        $this->sizes = $this->publitioApi->_get_all_image_sizes();
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