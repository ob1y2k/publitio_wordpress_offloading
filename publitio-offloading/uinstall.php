<?php
/**
 * Trigger file on Plugin uninstall
 *
 * @package Publitio
 */

if( ! defined('WP_UNINSTALL_PLUGIN') ) {
    exit;
}

delete_option('publitio_offloading_key');
delete_option('publitio_offloading_secret');
delete_option('publitio_offloading_default_folder');