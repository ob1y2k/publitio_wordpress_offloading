<?php
/**
 * @package Publitio
 */

namespace Inc\Base;

class Api {

    public static function uploadFile($attachment) {
        $publitioApi = new \Publitio\API('x3jutiZ88fjaBlB4Plly', '1L2WWkHycMjfHb9vZWkVmt5Xivo2Raa8');
        $args = array(
            'public_id' => $attachment->guid
        );
        $responseUpload = $publitioApi->uploadFile(fopen($attachment->guid, 'r'), 'file', $args);
        return $responseUpload;
    }
}