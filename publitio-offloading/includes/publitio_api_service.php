<?php
/**
 * @package Publitio
 */
require_once PLUGIN_PATH . '/includes/class-auth-service.php';


/**
 * Class PublitioApiService for handling Publitio Api calls and responses
 */
class PublitioApiService
{
    /**
     * Instance of PublitioApiService class
     */
    private $publitio_api;

    public function __construct()
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $key = PublitioOffloadingAuthService::get_key();
            $secret = PublitioOffloadingAuthService::get_secret();
            $this->publitio_api = new \Publitio\API($key, $secret);
        } else {
            $this->publitio_api = NULL;
        }
    }

    /**
     * Init Publitio Api
     * @param $api_key
     * @param $api_secret
     */
    public function init($api_key, $api_secret)
    {
        $this->publitio_api = new \Publitio\API($api_key, $api_secret);
        PublitioOffloadingAuthService::add_credentials($api_key, $api_secret);
        $this->check_credentials();
    }

    /**
     * Get list of account settings for Publitio account
     * @return object
     */
    public function get_account_settins()
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $resp = $this->publitio_api->call('/folders/tree', 'GET');
            $cnames = $this->publitio_api->call('/cnames/list', 'GET');
            $resp->cnames = $cnames->cnames;
            return $resp;
        } else {
            return false;
        }
    }

    /**
     * Set default folder in settings page
     * @param $folder_id
     */
    public function set_default_offloading_folder($folder_id)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            update_option('publitio_offloading_default_folder', $folder_id);
            wp_send_json([
                'status' => 200,
                'folder_id' => $folder_id
            ]);
        }
    }

    /**
     * Set default cname in settings page
     * @param $cname_url
     */
    public function set_default_offloading_cname($cname_url)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            update_option('publitio_offloading_default_cname', $cname_url);
            wp_send_json([
                'status' => 200,
                'default_cname_url' => $cname_url
            ]);
        }
    }

    /**
     * Set value for allow download option on setting page
     * @param $allow
     */
    public function set_allow_download_offloading($allow)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            if ($allow === "true") {
                $option = 'yes';
            } else {
                $option = 'no';
            }
            update_option('publitio_offloading_allow_download', $option);
            wp_send_json([
                'status' => 200,
                'allow_download' => 'yes'
            ]);
        }
    }

    /**
     * Set value image quality (50,60,70,80,90,original)
     * @param $image_quality
     */
    public function set_offloading_image_quality($image_quality)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            update_option('publitio_offloading_image_quality', $image_quality);
            wp_send_json([
                'status' => 200,
                'image_quality' => $image_quality
            ]);
        }
    }

    /**
     * Set value video quality (360p,480p,720p,1080p)
     * @param $video_quality
     */
    public function set_offloading_video_quality($video_quality)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            update_option('publitio_offloading_video_quality', $video_quality);
            wp_send_json([
                'status' => 200,
                'video_quality' => $video_quality
            ]);
        }
    }

    /**
     * Set checkbox option for each type of media (image,video,audio,document)
     * @param $id
     * @param $value
     */
    public function set_files_checkbox($id,$value) {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            if ($value === "true") {
                $option = 'yes';
            } else {
                $option = 'no';
            }
            update_option('publitio_offloading_'.$id, $option);
            wp_send_json([
                'status' => 200,
                id => $option
            ]);
        }
    }

    /**
     * Function for upload image to Publitio Dashboard
     * @param $attachment
     * @return array|null
     */
    public function uploadFile($attachment)
    {
        $args = array(
            'public_id' => $attachment->post_name
        );
        $folder = get_option('publitio_offloading_default_folder');
        if ($folder && !empty($folder)) {
            $args['folder'] = $folder;
        }
        $responseUpload = $this->publitio_api->uploadFile(fopen(wp_get_attachment_url($attachment->ID), 'r'), 'file', $args);
        if ($responseUpload->success === true) {
            $publitioMeta = array(
                'publitio_url' => $responseUpload->url_preview,
                'public_id' => $responseUpload->public_id,
                'extension' => $responseUpload->extension
            );
            if ($folder) {
                $publitioMeta['folder_name'] = $responseUpload->folder;
            }
            update_post_meta($attachment->ID, 'publitioMeta', $publitioMeta);
            return $publitioMeta;
        }
        return null;
    }

    /**
     * Get publitio url with url transformations
     * @param $dimensions - size of image
     * @param $publitioMeta
     * @return string
     */
    public function getTransformedUrl($dimensions, $publitioMeta)
    {
        if(is_null($publitioMeta)) {
            return null;
        }
        $media_url = PUBLITIO_MEDIA;
        if (get_option('publitio_offloading_default_cname')) {
            $media_url = get_option('publitio_offloading_default_cname') . '/file/';
        }
        if (is_null($dimensions)) {
            if($this->isImageType($publitioMeta['extension'])) {
                $qualityImage = get_option('publitio_offloading_image_quality') ? get_option('publitio_offloading_image_quality') : '80';
                return $media_url . ($qualityImage  ? 'q_' . $qualityImage . '/' : '') . ($publitioMeta['folder_name'] ? $publitioMeta['folder_name'] : '') . $publitioMeta['public_id'] . '.' . $publitioMeta['extension'];
            } elseif ($this->isVideoType($publitioMeta['extension'])) {
                $qualityVideo = get_option('publitio_offloading_video_quality') ? get_option('publitio_offloading_video_quality') : '480';
                return $media_url . ($qualityVideo ? 'h_' . $qualityVideo . '/' : '') . ($publitioMeta['folder_name'] ? $publitioMeta['folder_name'] : '') . $publitioMeta['public_id'] . '.' . $publitioMeta['extension'];
            } else {
                return $media_url . ($publitioMeta['folder_name'] ? $publitioMeta['folder_name'] : '') . $publitioMeta['public_id'] . '.' . $publitioMeta['extension'];
            }
        } else {
            return $media_url . 'w_' . $dimensions['width'] . ',' . ($dimensions['height'] ? 'h_' . $dimensions['height'] . ',' : '') . $dimensions['crop'] . '/' . ($publitioMeta['folder_name'] ? $publitioMeta['folder_name'] : '') . $publitioMeta['public_id'] . '.' . $publitioMeta['extension'];
        }
    }

    /**
     * Get size information for all currently-registered image sizes.
     *
     * @return array $sizes Data for all currently-registered image sizes.
     */
    public function _get_all_image_sizes()
    {
        global $_wp_additional_image_sizes;
        $default_image_sizes = array('thumbnail', 'medium', 'medium_large', 'large');
        $image_sizes = array();
        foreach ($default_image_sizes as $size) {
            $image_sizes[$size]['width'] = intval(get_option("{$size}_size_w"));
            $image_sizes[$size]['height'] = intval(get_option("{$size}_size_h"));
            $image_sizes[$size]['crop'] = (bool)get_option("{$size}_crop");
        }
        if (isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes)) $image_sizes = array_merge($image_sizes, $_wp_additional_image_sizes);
        return $image_sizes;
    }

    /**
     * Try to get list of folders with api key and api secret
     */
    private function check_credentials()
    {
        $response = $this->get_account_settins();
        $this->handle_response($response);
    }

    /**
     * Handle response from Publitio API
     * @param $response
     */
    private function handle_response($response)
    {
        if (!$response->success && $response->error->code === 401) {
            $this->handle_unauthorized();
        } else if (!$response->success) {
            $this->handle_error();
        }
        $this->handle_success($response);
    }

    /**
     * When user is unauthorized remove credentials
     */
    private function handle_unauthorized()
    {
        PublitioOffloadingAuthService::delete_credentials();
        wp_send_json(['status' => 401]);
    }

    /**
     * When error remove credentials
     */
    private function handle_error()
    {
        PublitioOffloadingAuthService::delete_credentials();
        wp_send_json(['status' => 500]);
    }

    /**
     * When success handle response
     * @param $response
     */
    private function handle_success($response)
    {
        if (!get_option('publitio_offloading_allow_download')) {
            update_option('publitio_offloading_allow_download', 'yes');
        }
        if (!get_option('publitio_offloading_image_quality')) {
            update_option('publitio_offloading_image_quality', '80');
        }
        if (!get_option('publitio_offloading_video_quality')) {
            update_option('publitio_offloading_video_quality', '480');
        }
        wp_send_json([
            'status' => 200,
            'folders' => $response->folders,
            'cnames' => $response->cnames,
            'default_folder_id' => get_option('publitio_offloading_default_folder'),
            'default_cname_url' => get_option('publitio_offloading_default_cname'),
            'allow_download' => get_option('publitio_offloading_allow_download'),
            'image_quality' => get_option('publitio_offloading_image_quality'),
            'video_quality' => get_option('publitio_offloading_video_quality'),
        ]);
    }

    /**
     * Check if attachment type is image
     * @param $type
     * @return bool
     */
    public function isImageType($type) {
        $image_types = array('jpg', 'jpeg', 'jpe', 'png', 'gif', 'bmp', 'psd', 'webp', 'ai', 'tif', 'tiff', 'svg');
        return in_array($type, $image_types);
    }

    /**
     * Check if attachment type is video
     * @param $type
     * @return bool
     */
    public function isVideoType($type) {
        $video_types = array('mp4', 'webm', 'ogv', 'avi', 'mov', 'flv', '3gp', '3g2', 'wmv', 'mpeg', 'mkv');
        return in_array($type, $video_types);
    }

    /**
     * Check if attachment type is pdf
     * @param $type
     * @return bool
     */
    public function isAudioType($type) {
        $audio_types = array('mp3', 'wav', 'ogg', 'aac', 'aiff', 'amr', 'ac3', 'au', 'flac', 'm4a', 'aac', 'ra', 'voc' , 'wma');
        return in_array($type, $audio_types);
    }

    /**
     * Check if attachment type is pdf
     * @param $type
     * @return bool
     */
    public function isDocumentType($type) {
        $doc_types = array('pdf');
        return in_array($type, $doc_types);
    }
}
