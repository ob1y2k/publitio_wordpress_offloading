<?php
/**
 * @package Publitio
 */
require_once PLUGIN_PATH . '/includes/class-auth-service.php';


/**
 * Class PublitioApiService for handling Publitio Api calls and responses
 */
class PublitioApiService {
    /**
     * Instance of PublitioApiService class
     */
    private $publitio_api;

    public function __construct() {
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
    public function init($api_key, $api_secret) {
        $this->publitio_api = new \Publitio\API($api_key, $api_secret);
        PublitioOffloadingAuthService::add_credentials($api_key, $api_secret);
        $this->check_credentials();
    }
    /**
     * Get list of folders for Publitio account
     */
    public function get_folders() {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $resp = $this->publitio_api->call('/folders/tree', 'GET');
            return $resp;
        } else {
            return false;
        }
    }
    /**
     * Set default folder in settings page
     * @param $folder_id
     */
    public function set_default_offloading_folder($folder_id) {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            update_option('publitio_offloading_default_folder', $folder_id);
            wp_send_json([
                'status' => 200,
                'folder_id' => $folder_id
            ]);
        }
    }
    /**
     * Function for upload image to Publitio Dashboard
     * @param $attachment
     * @return array|null
     */
    public function uploadFile($attachment) {
        $args = array(
            'public_id' => $attachment->post_name
        );
        $folder = get_option('publitio_offloading_default_folder');
        if ($folder && !empty($folder)) {
            $args['folder'] = $folder;
        }
        $responseUpload = $this->publitio_api->uploadFile(fopen($attachment->guid, 'r'), 'file', $args);
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
    public function getImageUrl($dimensions, $publitioMeta) {
        return PUBLITIO_MEDIA . 'w_' . $dimensions['width'] . ',' . ($dimensions['height'] ? 'h_' . $dimensions['height'] . ',' : '') . $dimensions['crop'] . '/' . ($publitioMeta['folder_name'] ? $publitioMeta['folder_name'] : '') . $publitioMeta['public_id'] . '.' . $publitioMeta['extension'];
    }
    /**
     * Get size information for all currently-registered image sizes.
     *
     * @return array $sizes Data for all currently-registered image sizes.
     */
    public function _get_all_image_sizes() {
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
    private function check_credentials() {
        $response = $this->get_folders();
        $this->handle_response($response);
    }
    /**
     * Handle response from Publitio API
     * @param $response
     */
    private function handle_response($response) {
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
    private function handle_unauthorized() {
        PublitioOffloadingAuthService::delete_credentials();
        wp_send_json(['status' => 401]);
    }
    /**
     * When error remove credentials
     */
    private function handle_error() {
        PublitioOffloadingAuthService::delete_credentials();
        wp_send_json(['status' => 500]);
    }
    /**
     * When success handle response
     * @param $response
     */
    private function handle_success($response) {
        wp_send_json([
            'status' => 200,
            'folders' => $response->folders,
            'default_folder_id' => get_option('publitio_offloading_default_folder')
        ]);
    }
}
