<?php
/**
 * @package AnicaPlugin
 */
/*
Plugin Name:Publitio Offloading
Plugin URI: https://publit.io/
Description: Publitio Offloading description
Version: 1.0.0
Author: Publitio
Author URI: https://publit.io/
License: GPLv2 or later
Text Domain: publitio-offloading
*/

use Inc\Base\Activate;
use Inc\Base\Deactivate;

if (!defined('ABSPATH')) {
    die;
}
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}


define('PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));
define('PLUGIN', plugin_basename(__FILE__));

function activate_publitio_offloading_plugin()
{
    Activate::activate();
}

function deactivate_publitio_offloading_plugin()
{
    Deactivate::deactivate();
}

register_activation_hook(__FILE__, 'activate_publitio_offloading_plugin');
register_deactivation_hook(__FILE__, 'deactivate_publitio_offloading_plugin');

if (class_exists('Inc\\Init')) {
    Inc\Init::register_services();
}






