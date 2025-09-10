<?php
/**
 * @package Publitio
 */

require_once PUBLITIO_OFFLOADING_PLUGIN_PATH . '/includes/publitio_api_service.php';

/**
 * Class Admin - handle all plugin changes on admin side 
 */
class PWPO_Admin
{
    /**
     * Instance of PublitioApiService class
     */
    private $publitioApi;

    public function __construct()
    {
        $this->publitioApi = new PublitioApiService();
        $this->pwpo_register();
    }

    /**
     * Register all admin actions and filters
     */
    public function pwpo_register()
    {
        add_action('admin_enqueue_scripts', array($this, 'pwpo_enqueue'));
        add_action('admin_menu', array($this, 'pwpo_add_admin_pages'));
        add_filter("plugin_action_links_" . PUBLITIO_OFFLOADING_PLUGIN, array($this, 'pwpo_settings_link'));
        add_action('wp_ajax_pwpo_update_offloading_settings', array($this, 'pwpo_update_offloading_settings'));
        add_action('wp_ajax_pwpo_get_offloading_account_settings', array($this, 'pwpo_get_offloading_account_settings'));
        add_action('wp_ajax_pwpo_get_media_list', array($this, 'pwpo_get_media_list'));
        add_action('wp_ajax_pwpo_sync_media_file', array($this, 'pwpo_sync_media_file'));
        add_action('wp_ajax_pwpo_update_replace_media', array($this, 'pwpo_update_replace_media'));
        add_action('wp_ajax_pwpo_get_media_list_for_delete', array($this, 'pwpo_get_media_list_for_delete'));
        add_action('wp_ajax_pwpo_delete_media_file', array($this, 'pwpo_delete_media_file'));
    }

    /**
     * Add Publitio Offloading option in Dashboard menu
     */
    public function pwpo_add_admin_pages()
    {
        add_menu_page(
            'Publitio Offloading',
            'Publitio Offloading',
            'manage_options',
            'publitio_offloading',
            array($this, 'pwpo_admin_index'),
            PUBLITIO_OFFLOADING_PLUGIN_URL . 'admin/images/cloud-icon.png',
            110
        );
    }

    /**
     * Get page for plugin settings
     */
    public function pwpo_admin_index()
    {
        require_once PUBLITIO_OFFLOADING_PLUGIN_PATH . 'admin/partials/admin_offloading_settings.php';
    }

    /**
     * Register all styles and scripts
     */
    public function pwpo_enqueue()
    {
        if (isset( $_GET['page'] ) && $_GET['page'] == 'publitio_offloading' ) {
            wp_enqueue_style('offloadingstyle', PUBLITIO_OFFLOADING_PLUGIN_URL . 'admin/css/offloading-style.css');
			wp_enqueue_style( 'publitio-offloading-toastify-css', 'https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css' );

            wp_enqueue_script('offloadingscripts', PUBLITIO_OFFLOADING_PLUGIN_URL . 'admin/js/offloading-script.js', array('jquery'));
			wp_enqueue_script( 'publitio-offloading-toastify-js', 'https://cdn.jsdelivr.net/npm/toastify-js', array( 'jquery' ), null, true );

        }
    }

    /**
     * Add settings link
     */
    public function pwpo_settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=publitio_offloading">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Update plugin settings
     */
    public function pwpo_update_offloading_settings()
    {
        // Check the nonce
		if (!isset( $_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'publitio_settings_nonce_action')) {
			wp_die(__('Unauthorized request.', 'publitio'));
		}

		// Check user permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have permission to update settings.', 'publitio'));
		}

        $api_key = sanitize_text_field($_POST['api_key']);
        $api_secret = sanitize_text_field($_POST['api_secret']);

        $status = PWPO_AuthService::validate_api_credentials($api_key, $api_secret);

        if($status === 200) {
            // Save the validated API credentials
            PWPO_AuthService::add_credentials($api_key, $api_secret);
            
            $allow_download = sanitize_text_field($_POST['allow_download']);
            update_option('publitio_offloading_allow_download', $this->pwpo_return_yes_no_value($allow_download));
            $offload_templates = sanitize_text_field($_POST['offload_templates']);
            update_option('publitio_offloading_offload_templates', $this->pwpo_return_yes_no_value($offload_templates));
            $image_checkbox = sanitize_text_field($_POST['image_checkbox']);
            update_option('publitio_offloading_image_checkbox', $this->pwpo_return_yes_no_value($image_checkbox));
            $video_checkbox = sanitize_text_field($_POST['video_checkbox']);
            update_option('publitio_offloading_video_checkbox', $this->pwpo_return_yes_no_value($video_checkbox));
            $audio_checkbox = sanitize_text_field($_POST['audio_checkbox']);
            update_option('publitio_offloading_audio_checkbox', $this->pwpo_return_yes_no_value($audio_checkbox));
            $document_checkbox = sanitize_text_field($_POST['document_checkbox']);
            update_option('publitio_offloading_document_checkbox', $this->pwpo_return_yes_no_value($document_checkbox));
            $folder_id = sanitize_text_field($_POST['folder_id']);
            update_option('publitio_offloading_default_folder', $folder_id);
            $cname_url = sanitize_text_field($_POST['cname_url']);
            update_option('publitio_offloading_default_cname', $cname_url);
            $image_quality = sanitize_text_field($_POST['image_quality']);
            update_option('publitio_offloading_image_quality', $image_quality);
            $video_quality = sanitize_text_field($_POST['video_quality']);
            update_option('publitio_offloading_video_quality', $video_quality);
            $delete_checkbox = sanitize_text_field($_POST['delete_checkbox']);
            update_option('publitio_offloading_delete_checkbox', $this->pwpo_return_yes_no_value($delete_checkbox));

            $response = $this->publitioApi->get_publitio_account_settings();
            wp_send_json([
                'status' => 200,
                'folders' => $response->folders,
                'cnames' => $response->cnames,
                'default_folder_id' => get_option('publitio_offloading_default_folder'),
                'default_cname_url' => get_option('publitio_offloading_default_cname'),
                'allow_download' => get_option('publitio_offloading_allow_download'),
                'offload_templates' => get_option('publitio_offloading_offload_templates'),
                'image_quality' => get_option('publitio_offloading_image_quality'),
                'video_quality' => get_option('publitio_offloading_video_quality'),
                'image_checkbox' => get_option('publitio_offloading_image_checkbox'),
                'video_checkbox' => get_option('publitio_offloading_video_checkbox'),
                'audio_checkbox' => get_option('publitio_offloading_audio_checkbox'),
                'document_checkbox' => get_option('publitio_offloading_document_checkbox'),
                'delete_checkbox' => get_option('publitio_offloading_delete_checkbox'),
                'replace_checkbox' => get_option('publitio_offloading_replace_checkbox'),
                'wordpress_data' => $response->wordpress_data->message
            ]);
        } else if($status === 401) {
            $this->pwpo_reset_values();
            wp_send_json([
                'status' => 401,
            ]);
        } else if($status === 500) {
            $this->pwpo_reset_values();
            wp_send_json([
                'status' => 500,
            ]);
        }
    }

    /**
     * Return yes/no value from boolean
     */
    public function pwpo_return_yes_no_value($value)
    {
        if ($value === true || $value === 'true' || $value === '1') {
            return 'yes';
        } else {
            return 'no';
        }
    }

    /**
     * Reset values for all options
     */
    public function pwpo_reset_values()
    {
        delete_option('publitio_offloading_key');
        delete_option('publitio_offloading_secret');
        delete_option('publitio_offloading_default_folder');
        update_option('publitio_offloading_allow_download', 'yes');
        delete_option('publitio_offloading_default_cname');
        delete_option('publitio_offloading_delete_checkbox');
        update_option('publitio_offloading_allow_download', 'yes');
        update_option('publitio_offloading_image_quality', '80');
        update_option('publitio_offloading_video_quality', '480');
        update_option('publitio_offloading_delete_checkbox', 'no');
        delete_option('publitio_offloading_replace_checkbox');
        update_option('publitio_offloading_image_checkbox', 'yes');
        update_option('publitio_offloading_video_checkbox', 'yes');
        update_option('publitio_offloading_audio_checkbox', 'yes');
        update_option('publitio_offloading_document_checkbox', 'yes');
        update_option('publitio_offloading_offload_templates', 'yes');
        update_option('publitio_offloading_image_checkbox', 'yes');
        update_option('publitio_offloading_video_checkbox', 'yes');
        update_option('publitio_offloading_audio_checkbox', 'yes');
        update_option('publitio_offloading_document_checkbox', 'yes');
    }

    /**
     * Get account settings
     */
    public function pwpo_get_offloading_account_settings()
    {
        $response = $this->publitioApi->get_publitio_account_settings();
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
                'replace_checkbox' => get_option('publitio_offloading_replace_checkbox'),
                'offload_templates' => get_option('publitio_offloading_offload_templates'),
                'wordpress_data' => $response->wordpress_data->message
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
                'replace_checkbox' => '',
                'offload_templates' => '',
                'wordpress_data' => ''
            ]);
        }
    }

    /**
     * Return list of media objects
     */
    public function pwpo_get_media_list()
    {
        $attachments = $this->publitioApi->get_media_for_sync();
        wp_send_json([
            'media' => $attachments
        ]);
    }

    /**
     * Synchronize media file with Publitio
     */
    public function pwpo_sync_media_file()
    {
        // Check the nonce
        if (!isset( $_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'publitio_settings_nonce_action')) {
            wp_die(__('Unauthorized request.', 'publitio'));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to update settings.', 'publitio'));
        }

        if (isset($_POST['attach_id'])) {
            $this->publitioApi->syncMedia(sanitize_text_field($_POST['attach_id']));
        }
    }

    /**
     * Set up flag for replace all media with Publitio media
     */
    public function pwpo_update_replace_media()
    {
        // Check the nonce
        if (!isset( $_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'publitio_settings_nonce_action')) {
            wp_die(__('Unauthorized request.', 'publitio'));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to update settings.', 'publitio'));
        }

        if (isset($_POST['replace_checkbox'])) {
            $this->publitioApi->set_replace_checkbox(sanitize_text_field($_POST['replace_checkbox']));
        }
    }

    /**
     * Return list of media objects that needs to be deleted
     */
    public function pwpo_get_media_list_for_delete() {
        $attachments = $this->publitioApi->get_undeleted_attachments();
        wp_send_json([
            'media' => $attachments
        ]);
    }

    /**
     * Remove media file from uploads folder
     */
    public function pwpo_delete_media_file()
    {
        // Check the nonce
        if (!isset( $_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'publitio_settings_nonce_action')) {
            wp_die(__('Unauthorized request.', 'publitio'));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to update settings.', 'publitio'));
        }

        if (isset($_POST['attach_id'])) {
            $this->publitioApi->deleteAtachment(sanitize_text_field($_POST['attach_id']));
        }
    }

}