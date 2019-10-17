<?php

/**
 * Provide a settings view for the plugin
 *
 * This file is used to markup the settings page of the plugin.
 *
 * @link       https://publit.io
 * @since      1.0.0
 *
 * @package    Publitio
 */
?>

<div class="page-offloading-wrapper">
    <div class="section-offloading-wrapper">
        <h2 class="section-offloading-title">
            <img src="<?php echo PLUGIN_URL . 'admin/images/cloud-icon.png'; ?>" alt=""/>
            Publitio Offloading Settings
        </h2>
    </div>
    <div class="section-offloading-wrapper">
        <label class="form-offload-label" for="api-offloading-key">API key:</label>
        <input class="form-offload-input" id="api-offloading-key" name="api-offloading-key" type="password"
               value="<?php echo get_option('publitio_offloading_key', ''); ?>"/>

        <label class="form-offload-label" for="api-offloading-secret">API secret:</label>
        <input class="form-offload-input" id="api-offloading-secret" name="api-offloading-secret" type="password"
               value="<?php echo get_option('publitio_offloading_secret', ''); ?>"/>

        <div class="offloading-block error-offload-block" id="error-offload-block"></div>
        <div class="offloading-block success-offload-block" id="success-offload-block"></div>
    </div>

    <div class="button-section-offloading-wrapper">
        <button type="button" class="publitio-offload-button" id="update-offloading-button">Save Api Keys</button>
    </div>
    <hr />
    <div class="section-offloading-wrapper">
        <div class="off-margin">
            <label class="switch">
                <input type="checkbox"
                       id="allow-download" <?php echo(get_option('publitio_offloading_allow_download') && get_option('publitio_offloading_allow_download') === 'no' ? '' : 'checked') ?>>
                <span class="slider round"></span>
            </label>
            <span class="download-text">Allow users to save/download media files</span>
        </div>
        <div class="offloading-block error-offload-block" id="error-allow-block"></div>
        <div class="offloading-block success-offload-block" id="success-allow-block"></div>
    </div>
    <hr />
    <div class="section-offloading-wrapper">
        <label class="form-offload-label">Select files to be offloaded:</label>
        <div class="files-checkbox off-margin">
            <div class="offload-checkbox">
                <label class="switch">
                    <input type="checkbox"
                           id="image_checkbox" class="files-offload-input" <?php echo(get_option('publitio_offloading_image_checkbox') && get_option('publitio_offloading_image_checkbox') === 'no' ? '' : 'checked') ?>>
                    <span class="slider round"></span>
                </label>
                <span class="download-text">Image</span>
            </div>
            <div class="offload-checkbox">
                <label class="switch">
                    <input type="checkbox"
                           id="video_checkbox" class="files-offload-input" <?php echo(get_option('publitio_offloading_video_checkbox') && get_option('publitio_offloading_video_checkbox') === 'no' ? '' : 'checked') ?>>
                    <span class="slider round"></span>
                </label>
                <span class="download-text">Video</span>
            </div>
            <div class="offload-checkbox">
                <label class="switch">
                    <input type="checkbox"
                           id="audio_checkbox" class="files-offload-input" <?php echo(get_option('publitio_offloading_audio_checkbox') && get_option('publitio_offloading_audio_checkbox') === 'no' ? '' : 'checked') ?>>
                    <span class="slider round"></span>
                </label>
                <span class="download-text">Audio</span>
            </div>
            <div class="offload-checkbox">
                <label class="switch">
                    <input type="checkbox"
                           id="document_checkbox" class="files-offload-input" <?php echo(get_option('publitio_offloading_document_checkbox') && get_option('publitio_offloading_document_checkbox') === 'no' ? '' : 'checked') ?>>
                    <span class="slider round"></span>
                </label>
                <span class="download-text">Document (pdf)</span>
            </div>
        </div>
        <div class="offloading-block error-offload-block" id="error-checkbox-block"></div>
        <div class="offloading-block success-offload-block" id="success-checkbox-block"></div>
    </div>
    <br/>
    <div class="section-offloading-wrapper">
        <label class="form-offload-label" for="default-offloading-folder">Upload Folder:</label>
        <select class="form-offload-select" name="default-offloading-folder" id="default-offloading-folder"></select>

        <div class="offloading-block error-offload-block" id="folder-error-block"></div>
        <div class="offloading-block success-offload-block" id="folder-success-block"></div>
    </div>
    <br/>
    <div class="section-offloading-wrapper">
        <label class="form-offload-label" for="default-offloading-cname">Custom CNAME:</label>
        <select class="form-offload-select" name="default-offloading-cname" id="default-offloading-cname"></select>

        <div class="offloading-block error-offload-block" id="cname-error-block"></div>
        <div class="offloading-block success-offload-block" id="cname-success-block"></div>
    </div>
    <br/>
    <div class="section-offloading-wrapper">
        <label class="form-offload-label" for="offloading-image-quality">Image quality:</label>
        <select class="form-offload-select" name="offloading-image-quality" id="offloading-image-quality">
            <option value="100">100</option>
            <option value="90">90</option>
            <option selected value="80">80 (default)</option>
            <option value="70">70</option>
            <option value="60">60</option>
            <option value="50">50</option>
        </select>

        <div class="offloading-block error-offload-block" id="folder-error-image-quality"></div>
        <div class="offloading-block success-offload-block" id="folder-success-image-quality"></div>
    </div>
    <br/>
    <div class="section-offloading-wrapper">
        <label class="form-offload-label" for="offloading-video-quality">Video quality:</label>
        <select class="form-offload-select" name="offloading-video-quality" id="offloading-video-quality">
            <option value="1080">1080p</option>
            <option value="720">720p</option>
            <option selected value="480">480p</option>
            <option value="360">360p</option>
        </select>

        <div class="offloading-block error-offload-block" id="folder-error-video-quality"></div>
        <div class="offloading-block success-offload-block" id="folder-success-video-quality"></div>
    </div>
    <hr />
    <div class="section-offloading-wrapper">
        <p class="download-text">Plugin will automatically upload media from posts during editing & rendering. You can sync entire media library right away via this button - but please be patient, large media library can take some time to upload to Publitio. Use this if you have deleted files from Publitio, and need them re-uploaded. Proceed with caution!</p>
        <button class="sync-button" id="media-offload">Sync Now</button>
        <div class="offloading-block error-offload-block" id="media-upload-message-error"></div>
        <div class="offloading-block success-offload-block" id="media-upload-message-success"></div>
    </div>
    <hr />
    <div class="section-offloading-wrapper">
        <div class="offload-checkbox">
            <label class="switch">
                <input type="checkbox"
                       id="delete_checkbox" class="files-offload-delete" <?php echo(get_option('publitio_offloading_delete_checkbox') && get_option('publitio_offloading_delete_checkbox') === 'yes' ? 'checked' : '') ?>>
                <span class="slider round"></span>
            </label>
            <span class="download-text">Delete file from Publitio when Media is deleted permanently</span>
        </div>
        <div class="offloading-block error-offload-block" id="error-delete-block"></div>
        <div class="offloading-block success-offload-block" id="success-delete-block"></div>
    </div>
    <div class="section-offloading-wrapper">
        <div class="off-margin">
            <label class="switch">
                <input type="checkbox" class="files-offload-delete"
                       id="replace_checkbox" <?php echo(get_option('publitio_offloading_replace_checkbox') && get_option('publitio_offloading_replace_checkbox') === 'yes' ? 'checked' : '') ?>>
                <span class="slider round"></span>
            </label>
            <span class="download-text">Delete files from Media library once uploaded to Publitio</span>
        </div>
        <div class="offloading-block error-offload-block" id="media-replace-message-error"></div>
        <div class="offloading-block success-offload-block" id="media-replace-message-success"></div>
    </div>
    <div class="section-offloading-wrapper">
        <button class="sync-button" id="media-delete">Delete All Offloaded Media</button>
        <div class="offloading-block error-offload-block" id="media-delete-message-error"></div>
        <div class="offloading-block success-offload-block" id="media-delete-message-success"></div>
    </div>
    <div id="popup1" class="overlay">
        <div class="popup">
            <div id="myProgress">
                <div id="loadNumber">0</div>
                <div id="myBar"></div>
            </div>
        </div>
    </div>
</div>
