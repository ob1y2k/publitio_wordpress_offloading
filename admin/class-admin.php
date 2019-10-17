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
        add_action('wp_ajax_get_offloading_account_settings', array($this, 'get_offloading_account_settings'));
        add_action('wp_ajax_update_default_offloading_folder', array($this, 'update_default_offloading_folder'));
        add_action('wp_ajax_update_default_offloading_cname', array($this, 'update_default_offloading_cname'));
        add_action('wp_ajax_update_allow_download', array($this, 'update_allow_download'));
        add_action('wp_ajax_update_image_offloading_quality', array($this, 'update_image_offloading_quality'));
        add_action('wp_ajax_update_video_offloading_quality', array($this, 'update_video_offloading_quality'));
        add_action('wp_ajax_update_files_checkbox', array($this, 'update_files_checkbox'));
        add_action('wp_ajax_update_delete_checkbox', array($this, 'update_delete_checkbox'));
        add_action('wp_ajax_get_media_list', array($this, 'get_media_list'));
        add_action('wp_ajax_sync_media_file', array($this, 'sync_media_file'));
        add_action('wp_ajax_update_replace_media', array($this, 'update_replace_media'));
        add_action('wp_ajax_get_media_list_for_delete', array($this, 'get_media_list_for_delete'));
        add_action('wp_ajax_delete_media_file', array($this, 'delete_media_file'));
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
            PLUGIN_URL . 'admin/images/cloud-icon.png',
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
     * Get account settings
     */
    public function get_offloading_account_settings()
    {
        $response = $this->publitioApi->get_account_settins();
        if ($response) {
            wp_send_json([
                'status' => 200,
                'folders' => $response->folders,
                'cnames' => $response->cnames,
                'default_folder_id' => get_option('publitio_offloading_default_folder'),
                'default_cname_url' => get_option('publitio_offloading_default_cname'),
                'allow_download' => get_option('publitio_offloading_allow_download'),
                'image_quality' => get_option('publitio_offloading_image_quality'),
                'video_quality' => get_option('publitio_offloading_video_quality'),
                'image_checkbox' => get_option('publitio_offloading_image_checkbox'),
                'video_checkbox' => get_option('publitio_offloading_video_checkbox'),
                'audio_checkbox' => get_option('publitio_offloading_audio_checkbox'),
                'document_checkbox' => get_option('publitio_offloading_document_checkbox'),
                'delete_checkbox' => get_option('publitio_offloading_delete_checkbox'),
                'replace_checkbox' => get_option('publitio_offloading_replace_checkbox')
            ]);
        } else {
            wp_send_json([
                'folders' => null,
                'cnames' => null,
                'default_folder_id' => '',
                'default_cname_url' => '',
                'allow_download' => '',
                'image_quality' => '',
                'video_quality' => '',
                'image_checkbox' => '',
                'video_checkbox' => '',
                'audio_checkbox' => '',
                'document_checkbox' => '',
                'delete_checkbox' => '',
                'replace_checkbox' => ''
            ]);
        }
    }

    /**
     * Update default folder
     */
    public function update_default_offloading_folder()
    {
        if (isset($_POST['folder_id'])) {
            $this->publitioApi->set_default_offloading_folder($_POST['folder_id']);
        }
    }

    /**
     * Update default cname
     */
    public function update_default_offloading_cname()
    {
        if (isset($_POST['cname_url'])) {
            $this->publitioApi->set_default_offloading_cname($_POST['cname_url']);
        }
    }

    /**
     * Update allow download option
     */
    public function update_allow_download()
    {
        if (isset($_POST['allow'])) {
            $this->publitioApi->set_allow_download_offloading($_POST['allow']);
        }
    }

    /**
     * Update image quality
     */
    public function update_image_offloading_quality()
    {
        if (isset($_POST['image_quality'])) {
            $this->publitioApi->set_offloading_image_quality($_POST['image_quality']);
        }
    }

    /**
     * Update video quality
     */
    public function update_video_offloading_quality()
    {
        if (isset($_POST['video_quality'])) {
            $this->publitioApi->set_offloading_video_quality($_POST['video_quality']);
        }
    }

    /**
     * Update checkbox to define which files should be offloaded
     */
    public function update_files_checkbox()
    {
        if (isset($_POST['id']) && isset($_POST['value'])) {
            $this->publitioApi->set_files_checkbox($_POST['id'], $_POST['value']);
        }
    }

    /**
     * Update checkbox to define which is delete from publitio is allowed
     */
    public function update_delete_checkbox()
    {
        if (isset($_POST['delete_checkbox'])) {
            $this->publitioApi->set_delete_checkbox($_POST['delete_checkbox']);
        }
    }

    /**
     * Return list of media objects
     */
    public function get_media_list()
    {
        $attachments = $this->publitioApi->get_media_for_sync();
        wp_send_json([
            'media' => $attachments
        ]);
    }

    /**
     * Synchronize media file with Publitio
     */
    public function sync_media_file()
    {
        if (isset($_POST['attach_id'])) {
            $this->publitioApi->syncMedia($_POST['attach_id']);
        }
    }

    /**
     * Set up flag for replace all media with Publitio media
     */
    public function update_replace_media()
    {
        if (isset($_POST['replace_checkbox'])) {
            $this->publitioApi->set_replace_checkbox($_POST['replace_checkbox']);
        }
    }

    /**
     * Return list of media objects that needs to be deleted
     */
    public function get_media_list_for_delete() {
        $attachments = $this->publitioApi->get_undeleted_attachments();
        wp_send_json([
            'media' => $attachments
        ]);
    }

    /**
     * Remove media file from uploads folder
     */
    public function delete_media_file()
    {
        if (isset($_POST['attach_id'])) {
            $this->publitioApi->deleteAtachment($_POST['attach_id']);
        }
    }

}