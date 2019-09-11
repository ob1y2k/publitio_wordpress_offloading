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

    /**
     * @var array of players
     */
    private $players;

    public function __construct()
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $key = PublitioOffloadingAuthService::get_key();
            $secret = PublitioOffloadingAuthService::get_secret();
            $this->publitio_api = new \Publitio\API($key, $secret);
        } else {
            $this->publitio_api = NULL;
        }

        $this->players = [];
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
     * Get list of players for Publitio user
     */
    public function get_players()
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            $resp = $this->publitio_api->call('/players/list', 'GET');
            return $resp;
        } else {
            return false;
        }
    }

    /**
     * Set default player in settings page
     * @param $player_id
     */
    public function set_default_offloading_player($player_id)
    {
        if (PublitioOffloadingAuthService::is_user_authenticated()) {
            update_option('publitio_offloading_default_player', $player_id);
            wp_send_json([
                'status' => 200,
                'player_id' => $player_id
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
            'public_id' => $attachment->guid
        );
        $responseUpload = $this->publitio_api->uploadFile(fopen($attachment->guid, 'r'), 'file', $args);
        if ($responseUpload) {
            $publitioMeta = array(
                'publitioURL' => $responseUpload->url_preview,
                'public_id' => $responseUpload->public_id,
                'extension' => $responseUpload->extension
            );
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
    public function getImageUrl($dimensions, $publitioMeta)
    {
        if ($dimensions['height']) {
            return PUBLITIO_MEDIA . '/w_' . $dimensions['width'] . ',' . 'h_' . $dimensions['height'] . ',' . $dimensions['crop'] . '/' . $publitioMeta['public_id'] . '.' . $publitioMeta['extension'];
        } else {
            return PUBLITIO_MEDIA . '/w_' . $dimensions['width'] . ',' . $dimensions['crop'] . '/' . $publitioMeta['public_id'] . '.' . $publitioMeta['extension'];
        }

    }

    /**
     * Get size information for all currently-registered image sizes.
     *
     * @return array $sizes Data for all currently-registered image sizes.
     * @uses   get_intermediate_image_sizes()
     * @global $_wp_additional_image_sizes
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

        if (isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes))
            $image_sizes = array_merge($image_sizes, $_wp_additional_image_sizes);

        return $image_sizes;
    }

    /**
     * Try to get players with api key and api secret
     */
    private function check_credentials()
    {
        $response = $this->get_players();
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

    private function handle_unauthorized()
    {
        PublitioOffloadingAuthService::delete_credentials();
        wp_send_json(['status' => 401]);
    }

    private function handle_error()
    {
        PublitioOffloadingAuthService::delete_credentials();
        wp_send_json(['status' => 500]);
    }

    private function handle_success($response)
    {
        $this->players = $response->players;
        wp_send_json([
            'status' => 200,
            'players' => $this->players,
            'default_player_id' => get_option('publitio_offloading_default_player')
        ]);
    }
}