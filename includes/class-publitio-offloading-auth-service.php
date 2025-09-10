<?php

class PWPO_AuthService {

    /**
     * Check if user is is authenticated
     * @return bool
     */
    public static function is_user_authenticated() {
        return get_option('publitio_offloading_key') && get_option('publitio_offloading_secret');
    }

    /**
     * Validate api credentials
     * @return array
     */
    public static function validate_api_credentials($api_key = null, $api_secret = null) {

        if($api_key && $api_secret) {
            $api_key = $api_key;
            $api_secret = $api_secret;
        } else {
            $api_key = self::get_key();
            $api_secret = self::get_secret();
        }
    
        try {
            $temp_api = new \Publitio\API($api_key, $api_secret);
            $response = $temp_api->call('/wordpress/data', 'GET');
            
            if ($response && $response->success === true) {
                return 200;
            } else if ($response && isset($response->error) && $response->error->code === 401) {
                return 401;
            } else {
                return 500;
            }
        } catch (Exception $e) {
            return 500;
        }
    }

    /**
     * Setup options for api key and api secret
     * @param $api_key
     * @param $api_secret
     */
    public static function add_credentials($api_key, $api_secret) {
        update_option('publitio_offloading_key', $api_key);
        update_option('publitio_offloading_secret', $api_secret);
    }

    /**
     * Remove options when user is unauthorized
     */
    public static function delete_credentials() {
        delete_option('publitio_offloading_secret');
        delete_option('publitio_offloading_key');
        delete_option('publitio_offloading_default_folder');
        delete_option('publitio_offloading_allow_download');
        delete_option('publitio_offloading_offload_templates');
        delete_option('publitio_offloading_default_cname');
        delete_option('publitio_offloading_image_quality');
        delete_option('publitio_offloading_video_quality');
        delete_option('publitio_offloading_delete_checkbox');
        delete_option('publitio_offloading_replace_checkbox');
        delete_option('publitio_offloading_image_checkbox');
        delete_option('publitio_offloading_video_checkbox');
        delete_option('publitio_offloading_audio_checkbox');
        delete_option('publitio_offloading_document_checkbox');
        delete_option('publitio_offloading_offload_templates');
        delete_option('publitio_offloading_image_checkbox');
        delete_option('publitio_offloading_video_checkbox');
        delete_option('publitio_offloading_audio_checkbox');
        delete_option('publitio_offloading_document_checkbox');
    }

    /**
     * Get option api key
     * @return mixed|void
     */
    public static function get_key() {
        return get_option('publitio_offloading_key');
    }

    /**
     * Get option api secret
     * @return mixed|void
     */
    public static function get_secret() {
        return get_option('publitio_offloading_secret');
    }
}
