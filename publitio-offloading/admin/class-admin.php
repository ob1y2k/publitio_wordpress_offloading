<?php
/**
 * @package Publitio
 */

require_once PLUGIN_PATH . '/includes/publitio_api_service.php';

/**
 * Class Admin - handle all plugin changes on admin side
 */
class Admin
{
    /**
     * Instance of PublitioApiService class
     */
    private $publitioApi;

    public function __construct()
    {
        $this->publitioApi = new PublitioApiService();
        $this->register();
    }

    /**
     * Register all admin actions and filters
     */
    public function register()
    {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
        add_filter("plugin_action_links_" . PLUGIN, array($this, 'settings_link'));
        add_action('wp_ajax_update_offloading_settings', array($this, 'update_offloading_settings'));
        add_action('wp_ajax_get_offloading_folders_tree', array($this, 'get_offloading_folders_tree'));
        add_action('wp_ajax_update_default_offloading_folder',array($this, 'update_default_offloading_folder'));
    }

    /**
     * Add Publitio Offloading option in Dashboard menu
     */
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

    /**
     * Get page for plugin settings
     */
    public function admin_index()
    {
        require_once PLUGIN_PATH . 'admin/partials/admin_offloading_settings.php';
    }

    /**
     * Register all styles and scripts
     */
    public function enqueue()
    {
        wp_enqueue_style('offloadingstyle', PLUGIN_URL . 'admin/css/offloading-style.css');
        wp_enqueue_script('offloadingscripts', PLUGIN_URL . 'admin/js/offloading-script.js', array('jquery'));
    }

    /**
     * Add settings link
     */
    public function settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=publitio_offloading">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Update plugin settings
     */
    public function update_offloading_settings()
    {
        if (isset($_POST['api_key']) && isset($_POST['api_secret'])) {
            $api_key = $_POST['api_key'];
            $api_secret = $_POST['api_secret'];
            $this->publitioApi->init($api_key, $api_secret);
        }

    }

    /**
     * Get players for account
     */
    public function get_offloading_folders_tree() {
        $response =  $this->publitioApi->get_folders();
        if($response) {
            wp_send_json([
                'status' => 200,
                'folders' => $response->folders,
                'default_folder_id' => get_option('publitio_offloading_default_folder')
            ]);
        } else {
            wp_send_json([
                'folders' => null,
                'default_folder_id' => ''
            ]);
        }
    }

    /**
     * Update default folder
     */
    public function update_default_offloading_folder() {
        if (isset($_POST['folder_id'])) {
            $this->publitioApi->set_default_offloading_folder($_POST['folder_id']);
        }
    }
}