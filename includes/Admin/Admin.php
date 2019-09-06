<?php
/**
 * @package Publitio
 */
namespace Inc\Admin;

class Admin
{
    public function register()
    {
        add_action('admin_menu', array($this, 'add_admin_pages'));
    }

    public function add_admin_pages()
    {
        add_menu_page(
            'Publitio Offloading',
            'Publitio Offloading',
            'manage_options',
            'publitio_offloading',
            array($this, 'admin_index'),
            'dashicons-admin-media',
            110
        );
    }

    public function admin_index()
    {
        require_once PLUGIN_PATH . 'templates/admin.php';
    }
}