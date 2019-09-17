<?php

class PublitioOffloadingAuthService {

    /**
     * Check if user is is authenticated
     * @return bool
     */
    public static function is_user_authenticated() {
        return get_option('publitio_offloading_key') && get_option('publitio_offloading_secret');
    }

    /**
     * Setup options
     * @param $api_key
     * @param $api_secret
     */
    public static function add_credentials($api_key, $api_secret) {
        update_option('publitio_offloading_key', $api_key);
        update_option('publitio_offloading_secret', $api_secret);
    }

    /**
     * Remove options
     */
    public static function delete_credentials() {
        delete_option('publitio_offloading_secret');
        delete_option('publitio_offloading_key');
        delete_option('publitio_offloading_default_folder');
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
