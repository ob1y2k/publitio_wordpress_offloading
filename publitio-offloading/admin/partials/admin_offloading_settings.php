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
 * @subpackage Publitio/admin/partials
 */
?>

<div class="page-offloading-wrapper">
    <div class="section-offloading-wrapper">
        <h2 class="section-offloading-title">
            Publitio Offloading Settings
        </h2>
    </div>
        <div class="section-offloading-wrapper">
            <label class="form-label" for="api-offloading-key">API key:</label>
            <input class="form-input" id="api-offloading-key" name="api-offloading-key" type="password" value="<?php echo get_option('publitio_offloading_key', ''); ?>"/>

            <label class="form-label" for="api-offloading-secret">API secret:</label>
            <input class="form-input" id="api-offloading-secret" name="api-offloading-secret" type="password" value="<?php echo get_option('publitio_offloading_secret', ''); ?>"/>

            <div class="offloading-block error-block" id="error-block"></div>
            <div class="offloading-block success-block" id="success-block"></div>
        </div>

        <div class="button-section-offloading-wrapper">
            <button type="button" class="publitio-button" id="update-offloading-button">Update Settings</button>
        </div>

    <hr />

    <div class="section-offloading-wrapper">
        <label class="form-label" for="default-offloading-player">Player:</label>
        <select class="form-select" name="default-offloading-player" id="default-offloading-player"></select>

        <div class="offloading-block error-block" id="player-error-block"></div>
        <div class="offloading-block success-block" id="player-success-block"></div>
    </div>
</div>
