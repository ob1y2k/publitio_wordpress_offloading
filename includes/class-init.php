<?php


/**
 * @package Publitio
 */

require_once PLUGIN_PATH.'/admin/class-admin.php';
require_once PLUGIN_PATH . '/includes/class-offloading.php';

class Init
{
    /**
     * Get all classes and store them inside array
     * @return array
     */
    public static function get_services()
    {
        return [
            new Admin(),
            new Offload()
        ];
    }
}