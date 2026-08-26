<?php

if (!defined('WPINC')) {
    die;
}

if (!class_exists('PWPO_Publitio_API')) {

    /**
     * Self-contained Publitio API client. Must stay dependency-free: bundled
     * HTTP libraries (Guzzle/PSR-7) collide with other plugins' copies and
     * fatal on PHP 8.1+. Uploads use raw cURL with CURLFile so large files
     * stream from disk — the WP HTTP API would buffer the whole body in memory.
     */
    class PWPO_Publitio_API
    {
        const BASE_URL = 'https://api.publit.io/v1/';

        private $key;
        private $secret;

        public function __construct($key, $secret)
        {
            $this->key = $key;
            $this->secret = $secret;
        }

        /**
         * Make a signed API call (no file upload).
         * @param string $path API endpoint, e.g. '/files/show/{id}'
         * @param string $method HTTP method
         * @param array $args Extra query parameters
         * @return object Decoded JSON response, or a synthetic error object
         *                (success=false, error->code, error->message). Never throws.
         */
        public function call($path, $method = 'GET', $args = array())
        {
            $res = wp_remote_request($this->build_url($path, $args), array(
                'method' => strtoupper($method),
                'timeout' => 30,
            ));
            if (is_wp_error($res)) {
                return $this->error_response($res->get_error_message());
            }
            return $this->decode(wp_remote_retrieve_body($res));
        }

        /**
         * Upload a local file via multipart POST.
         * @param string $file_path Absolute path to the file on disk
         * @param string $action 'file' or 'watermark'
         * @param array $args Extra query parameters
         * @return object Decoded JSON response or a synthetic error object. Never throws.
         */
        public function uploadFile($file_path, $action = 'file', $args = array())
        {
            if ($action === 'file') {
                $endpoint = 'files/create';
            } else if ($action === 'watermark') {
                $endpoint = 'watermarks/create';
            } else {
                return $this->error_response("Unknown action $action");
            }
            if (!function_exists('curl_init')) {
                return $this->error_response('cURL is required for file uploads');
            }
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => $this->build_url($endpoint, $args),
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => array('file' => new CURLFile($file_path)),
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYPEER => true,
            ));
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($errno !== 0) {
                return $this->error_response("cURL error #$errno: $error");
            }
            return $this->decode($body);
        }

        /**
         * Append the standard Publitio auth query arguments.
         */
        private function api_args($args)
        {
            $timestamp = time();
            $nonce = random_int(10000000, 99999999);
            $args['api_key'] = $this->key;
            $args['api_timestamp'] = $timestamp;
            $args['api_nonce'] = $nonce;
            $args['api_signature'] = sha1($timestamp . $nonce . $this->secret);
            $args['api_kit'] = 'php-wpo-' . PUBLITIO_OFFLOADING_PLUGIN_NAME_VERSION;
            return $args;
        }

        private function build_url($path, $args)
        {
            return self::BASE_URL . ltrim($path, '/') . '?' . http_build_query($this->api_args($args), '', '&');
        }

        private function decode($body)
        {
            $json = json_decode($body);
            return is_object($json) ? $json : $this->error_response('Invalid JSON response from Publitio API');
        }

        private function error_response($message, $code = 0)
        {
            return (object) array(
                'success' => false,
                'error' => (object) array('code' => $code, 'message' => $message),
            );
        }
    }
}
