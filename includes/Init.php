<?php


/**
 * @package Publitio
 */

namespace Inc;

final class Init
{
    /**
     * Store all classes we need inside array
     * @return array
     */
    public static function get_services()
    {
        return [
            Admin\Admin::class,
            Base\Enqueue::class,
            Base\SettingsLinks::class,
            Offload\Offload::class,
        ];
    }

    /**
     * loop through classes and call register method if we have it in that class
     */
    public static function register_services()
    {
        foreach (self::get_services() as $class) {
            $service = self::instantiate($class);
            if (method_exists($service, 'register')) {
                $service->register();
            }
        }
    }

    /**
     * @param $class
     * @return instance
     */
    private static function instantiate($class)
    {
        return new $class();
    }
}