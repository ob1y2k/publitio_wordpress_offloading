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

<div class="publitio-page-wrapper">
  <div class="publitio-page-header">
    <img class="publitio-page-logo" src="<?php echo plugins_url( '/images/publitio_offloading.png', dirname(__FILE__)  ); ?>" alt="Publitio" />
    <a class="publitio-page-review" target="_blank" href="https://wordpress.org/plugins/publitio-offloading/#reviews">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
      <div>Please rate us on<br /><span class="publitio-emphasize">WordPress.org</span></div>
    </a>
  </div>

  <div class="publitio-section-wrapper">

    <div class="publitio-page-warning-message">
      <svg viewBox="0 0 24 24" class="text-yellow-600 w-5 h-5 sm:w-5 sm:h-5 mr-3"><path fill="currentColor" d="M23.119,20,13.772,2.15h0a2,2,0,0,0-3.543,0L.881,20a2,2,0,0,0,1.772,2.928H21.347A2,2,0,0,0,23.119,20ZM11,8.423a1,1,0,0,1,2,0v6a1,1,0,1,1-2,0Zm1.05,11.51h-.028a1.528,1.528,0,0,1-1.522-1.47,1.476,1.476,0,0,1,1.448-1.53h.028A1.527,1.527,0,0,1,13.5,18.4,1.475,1.475,0,0,1,12.05,19.933Z"></path></svg>
      <p>Publitio plugin is not connected to the API. Please update settings to get started.</p>
    </div>

    <div id="publitio-page-data" class="publitio-page-data publitio-requires-auth">
      <div class="publitio-page-data-item">
        <div class="publitio-storage-chart-container">
          <div class="publitio-storage-chart" data-percentage="0">
            <div class="publitio-storage-inner">
              <span class="publitio-storage-percentage">0%</span>
            </div>
          </div>
        </div>

        <div class="publitio-storage-info">
          <span class="publitio-storage-used">Storage used: 0B</span>
          <span class="publitio-storage-limit">Storage limit: 0B</span>
        </div>
      </div>

      <div class="publitio-page-data-item">
        <div class="publitio-bandwidth-chart-container">
          <div class="publitio-bandwidth-chart" data-percentage="0">
            <div class="publitio-bandwidth-inner">
              <span class="publitio-bandwidth-percentage">0%</span>
            </div>
          </div>
        </div>

        <div class="publitio-bandwidth-info">
          <span class="publitio-bandwidth-used">Bandwidth used: 0B</span>
          <span class="publitio-bandwidth-limit">Bandwidth limit: 0B</span>
        </div>
      </div>

      <div class="publitio-page-data-item">
        <div class="publitio-plan-info">
          <span class="publitio-plan-used">Current plan: <b id="publitio-plan-used" class="publitio-emphasize">None</b></span>
          <a class="publitio-button" target="_blank" href="https://publit.io/billing">Manage billing</a>
        </div>
      </div>
    </div>

    <div class="publitio-settings">
      <div class="publitio-settings-description">
        <p>
            The Publitio Offloading plugin for WordPress automatically uploads your media files to <a target="_blank" class="publitio-emphasize" href="https://publit.io/">Publitio's</a> cloud storage instead of keeping them on your WordPress server. Once offloaded, your images, videos, and other files are delivered through <a target="_blank" class="publitio-emphasize" href="https://publit.io/">Publitio's</a> global CDN, reducing server load, saving hosting space, and speeding up your site. It also gives you access to Publitio's features like on-the-fly transformations, video streaming, and secure file delivery.
        </p>

        <p>
            Check out the tutorial <a target="_blank" class="publitio-emphasize" href="https://publit.io/community/blog/wordpress-offloading-with-publitio">How to setup Publitio Offloading Wordpress Plugin</a>
        </p>
      </div>
    </div>

    <div class="publitio-settings-info-title">⚙ Offloading Settings</div>

    <div class="publitio-settings">

      <?php wp_nonce_field('publitio_settings_nonce_action'); ?>

      <div class="publitio-field-wrapper">
        <label for="api_key">API key<br />
          <small>API key and API secret pairs are used to authenticate your requests to the Publitio API.</small>
        </label>
        <input id="api_key" name="api_key" type="password" value="<?php echo get_option('publitio_offloading_key', ''); ?>" autocomplete="off" placeholder="API key" />
      </div>

      <div class="publitio-field-wrapper">
        <label for="api_secret">API secret</label>
        <input id="api_secret" name="api_secret" type="password" value="<?php echo get_option('publitio_offloading_secret', ''); ?>" autocomplete="off" placeholder="API secret" />
      </div>

      <div class="publitio-field-wrapper publitio-requires-auth">
        <label for="allow_download">Allow users to save/download media files</label>
        <div class="checkbox-wrapper-6">
            <input class="tgl tgl-light" id="allow_download" name="allow_download" type="checkbox" <?php echo esc_html((get_option('publitio_offloading_allow_download') && get_option('publitio_offloading_allow_download') === 'no' ? '' : 'checked')) ?> />
            <label class="tgl-btn" for="allow_download"></label>
        </div>
      </div>

      <div class="publitio-field-wrapper publitio-requires-auth">
        <label for="offload_templates">Offload templates<br />
            <small>(headers, footers, custom pages...)</small>
        </label>
        <div class="checkbox-wrapper-6">
            <input class="tgl tgl-light" id="offload_templates" name="offload_templates" type="checkbox" <?php echo esc_html(get_option('publitio_offloading_offload_templates', 'yes') === 'no' ? '' : 'checked') ?> />
            <label class="tgl-btn" for="offload_templates"></label>
        </div>
      </div>

      <div class="publitio-field-wrapper publitio-files-wrapper publitio-requires-auth">
        <label>Select file types to be offloaded:<br />
            <small>Only the selected file types will be offloaded to Publitio</small>
        </label>
        <div class="publitio-files-checkbox-wrapper">
            <div class="publitio-files-checkbox-item">
                <div class="checkbox-wrapper-6">
                    <input class="tgl tgl-light" id="image_checkbox" type="checkbox" <?php echo esc_html((get_option('publitio_offloading_image_checkbox') && get_option('publitio_offloading_image_checkbox') === 'no' ? '' : 'checked')) ?> />
                    <label class="tgl-btn" for="image_checkbox"></label>
                </div>
                <span>Image</span>
            </div>

            <div class="publitio-files-checkbox-item">
                <div class="checkbox-wrapper-6">
                    <input class="tgl tgl-light" id="video_checkbox" type="checkbox" <?php echo esc_html((get_option('publitio_offloading_video_checkbox') && get_option('publitio_offloading_video_checkbox') === 'no' ? '' : 'checked')) ?> />
                    <label class="tgl-btn" for="video_checkbox"></label>
                </div>
                <span>Video</span>
            </div>

            <div class="publitio-files-checkbox-item">
                <div class="checkbox-wrapper-6">
                    <input class="tgl tgl-light" id="audio_checkbox" type="checkbox" <?php echo esc_html((get_option('publitio_offloading_audio_checkbox') && get_option('publitio_offloading_audio_checkbox') === 'no' ? '' : 'checked')) ?> />
                    <label class="tgl-btn" for="audio_checkbox"></label>
                </div>
                <span>Audio</span>
            </div>

            <div class="publitio-files-checkbox-item">
                <div class="checkbox-wrapper-6">
                    <input class="tgl tgl-light" id="document_checkbox" type="checkbox" <?php echo esc_html((get_option('publitio_offloading_document_checkbox') && get_option('publitio_offloading_document_checkbox') === 'no' ? '' : 'checked')) ?> />
                    <label class="tgl-btn" for="document_checkbox"></label>
                </div>
                <span>Document (pdf)</span>
            </div>
        </div>
      </div>

      <div class="publitio-field-wrapper publitio-requires-auth">
        <label for="default-publitio-offloading-folder">Upload folder<br />
          <small>Useful if you want to separate offloaded media from the rest of your media</small>
        </label>
        <select id="default-publitio-offloading-folder" name="default-publitio-offloading-folder"></select>
      </div>

      <div class="publitio-field-wrapper publitio-requires-auth">
        <label for="default-publitio-offloading-cname">Custom CNAME<br />
          <small>Serve your media from your own domain</small>
        </label>
        <select id="default-publitio-offloading-cname" name="default-publitio-offloading-cname"></select>
      </div>

      <div class="publitio-field-wrapper publitio-requires-auth">
        <label for="offloading-image-quality">Default image quality<br />
          <small>Image quality for offloaded images, this drastically affects your site performance</small>
        </label>
        <select id="offloading-image-quality" name="offloading-image-quality">
            <option value="100">100</option>
            <option value="90">90</option>
            <option selected value="80">80 (default)</option>
            <option value="70">70</option>
            <option value="60">60</option>
            <option value="50">50</option>
        </select>
      </div>

      <div class="publitio-field-wrapper publitio-requires-auth">
        <label for="offloading-video-quality">Default video quality</label>
        <select id="offloading-video-quality" name="offloading-video-quality">
            <option value="1080">1080p</option>
            <option value="720">720p</option>
            <option selected value="480">480p</option>
            <option value="360">360p</option>
        </select>
      </div>

      <div class="publitio-field-wrapper publitio-requires-auth">
        <label for="delete_checkbox">Delete Publitio file when media is deleted locally<br />
            <small>Uppon deleting media from your media library, the file will be deleted from Publitio as well</small>
        </label>
        <div class="checkbox-wrapper-6">
            <input class="tgl tgl-light" id="delete_checkbox" name="delete_checkbox" type="checkbox" <?php echo esc_html((get_option('publitio_offloading_delete_checkbox') && get_option('publitio_offloading_delete_checkbox') === 'yes' ? 'checked' : '')) ?> />
            <label class="tgl-btn" for="delete_checkbox"></label>
        </div>
      </div>

      <div class="publitio-field-wrapper publitio-files-wrapper publitio-requires-auth">
        <label for="api-secret">Sync now<br />
            <small>Plugin will automatically upload media from posts during editing & rendering. You can sync entire media library right away via this button - but please be patient, large media library can take some time to upload to Publitio. Use this if you have deleted files from Publitio, and need them re-uploaded. Proceed with caution!</small>
        </label>
        <button type="button" class="publitio-settings-button publitio-success-button" id="publitio-sync-now-button">Sync Now</button>
      </div>
    </div>

    <button type="button" class="publitio-settings-button" id="update-offloading-button">Update Settings</button>
  </div>
  <small class="publitio-version">
    <?php echo PUBLITIO_OFFLOADING_PLUGIN_NAME_VERSION; ?>
  </small>
</div>

<div class="publitio-page-wrapper publitio-requires-auth">
    <div class="publitio-section-wrapper">
        <div class="publitio-settings-info-title">⚠ Danger Zone</div>
        <div class="publitio-settings publitio-border-bottom">
            <div class="publitio-settings-description">
                <p>
                    Options bellow are <b>best to keep OFF</b> (default). If you use them, <b>plugin will delete files from local storage</b> once they are uploaded to Publitio (useful if you have limited space within site) - but if you choose to <b>deactivate Publitio Offloading plugin</b> in the future, your site posts/pages <b>will result in broken media links</b> (as they are no longer present locally). Use with caution & at your own risk as there is no going back once you use this options!
                </p>
            </div>
        </div>
        <div class="publitio-settings">
            <div class="publitio-field-wrapper publitio-requires-auth">
                <label for="replace_checkbox">Delete files from Media Library once uploaded to Publitio</label>
                <div class="checkbox-wrapper-6">
                    <input class="tgl tgl-light" id="replace_checkbox" name="replace_checkbox" type="checkbox" <?php echo esc_html((get_option('publitio_offloading_replace_checkbox') && get_option('publitio_offloading_replace_checkbox') === 'yes' ? 'checked' : '')) ?> />
                    <label class="tgl-btn" for="replace_checkbox"></label>
                </div>
            </div>

        </div>

        <div class="publitio-buttons-wrapper">
            <button type="button" class="publitio-settings-button publitio-warning-button" id="publitio-update-danger-settings-button">Update Settings</button>
            <button type="button" class="publitio-settings-button publitio-danger-button" id="media-delete">Delete All Offloaded Media</button>
        </div>
    </div>
</div>

<div class="publitio-page-wrapper">
  <div class="publitio-page-data">
    <div class="publitio-page-data-item">
      <a class="publitio-page-footer-block" target="_blank" href="https://dashboard.publit.io/app/dashboards/api-tokens">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>
        <div>
          <div class="publitio-emphasize">API Keys</div>
          <div>API Keys allow third-party services such as Wordpress to authenticate with Publitio on your behalf.</div>
        </div>
      </a>
    </div>

    <div class="publitio-page-data-item">
      <a class="publitio-page-footer-block" target="_blank" href="https://publit.io/community">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
        <div>
          <div class="publitio-emphasize">Our blog</div>
          <div>Here you can find tutorials, learn more, stay updated with the latest news, discover best practices, and explore helpful insights.</div>
        </div>
      </a>
    </div>

    <div class="publitio-page-data-item">
      <a class="publitio-page-footer-block" target="_blank" href="https://dashboard.publit.io/app/dashboards/community">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
        <div>
          <div class="publitio-emphasize">Community</div>
          <div>Here you can ask questions, share your ideas, discuss, suggest new features and get help from other users.</div>
        </div>
      </a>
    </div>
  </div>
</div>

<div id="publitio-popup" class="overlay">
    <div class="offloading-popup">
        <div id="publitioProgress">
            <div id="loadPublitioNumber">0</div>
            <div id="publitioBar"></div>
        </div>
    </div>
</div>