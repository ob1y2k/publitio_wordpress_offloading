(function ($) {
    'use strict';

    const STATUSES = {
        ERROR_UNAUTHORIZED: 401,
        ERROR: 500,
        SUCCESS: 200
    }

    $(function () {
        getAccountSettings()
        updateSettingsButtonClick()
        updateDefaultFolderChange()
        updateDefaultCnameChange()
        changeAllowDownload()
        changeImageQuality()
        changeVideoQuality()
        checkboxFiles()
        changeDelete()
        syncMediaFiles()
    });

    function updateSettingsButtonClick() {
        $('#update-offloading-button').bind('click', function (event) {
            clearBlocks();
            jQuery.post(ajaxurl, {
                action: 'update_offloading_settings',
                api_secret: $('#api-offloading-secret').val(),
                api_key: $('#api-offloading-key').val()
            }, function (response) {
                if (response.status === STATUSES.ERROR_UNAUTHORIZED) {
                    showBlock($('#error-offload-block'), 'Wrong credentials');
                    clearFolderList(true);
                    clearCnameList(true);
                    setCheckBoxValue('');
                    $(".form-offload-select").attr("disabled", true);
                    $(".files-offload-input").attr("disabled", true);
                } else if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#success-offload-block'), 'Great!');
                    handleResponse(response);
                } else {
                    showBlock($('#error-offload-block'), 'Something went wrong.');
                    clearFolderList(true);
                    clearCnameList(true);
                    setCheckBoxValue('');
                    $(".form-offload-select").attr("disabled", true);
                    $(".files-offload-input").attr("disabled", true);
                }
            });
        });
    }

    function handleResponse(response) {
        if (response.folders != null) {
            $(".form-offload-select").removeAttr("disabled");
            $(".files-offload-input").removeAttr("disabled");
            addFoldersList(response.folders, response.default_folder_id);
            addCnameList(response.cnames, response.default_cname_url);
            setCheckBoxValue(response.allow_download);
            setImageQualityValue(response.image_quality);
            setVideoQualityValue(response.video_quality);
            setFilesCheckbox('image_checkbox', response.image_checkbox);
            setFilesCheckbox('video_checkbox', response.video_checkbox);
            setFilesCheckbox('audio_checkbox', response.audio_checkbox);
            setFilesCheckbox('document_checkbox', response.document_checkbox);
            setFilesCheckbox('delete_checkbox', response.delete_checkbox);
        } else {
            setCheckBoxValue('');
            $(".form-offload-select").attr("disabled", true);
            $(".files-offload-input").attr("disabled", true);
        }
    }

    function getAccountSettings() {
        jQuery.get(ajaxurl, {action: 'get_offloading_account_settings'}, function (response) {
            handleResponse(response);
        })
    }

    function addFoldersList(folders, defaultFolderId = '') {
        clearFolderList();
        if (folders !== undefined && folders !== null) {
            $('<option value="">/</option>').appendTo($('#default-offloading-folder'));
            folders.forEach((folder) => {
                $('<option value="' + folder.id + '">' + folder.path + '</option>').appendTo($('#default-offloading-folder'));
            });
            setSelectedOffloadingFolder(defaultFolderId);
        }
    }

    function addCnameList(cnames, defaultCnameId = '') {
        clearCnameList();
        if (cnames !== undefined && cnames !== null) {
            $('<option value="">https://media.publit.io</option>').appendTo($('#default-offloading-cname'));
            cnames.forEach((cname) => {
                $('<option value="' + cname.url + '">' + cname.url + '</option>').appendTo($('#default-offloading-cname'));
            })
            setSelectedOffloadingCname(defaultCnameId);
        }
    }

    function setSelectedOffloadingFolder(id) {
        $('#default-offloading-folder > option[value="' + id + '"]').attr("selected", "selected");
    }

    function setSelectedOffloadingCname(id) {
        $('#default-offloading-cname > option[value="' + id + '"]').attr("selected", "selected");
    }

    function clearFolderList(show = false) {
        $('#default-offloading-folder').empty();
        if (show === true) {
            $('<option selected hidden disabled>None</option>').appendTo($('#default-offloading-folder'));
        }
    }

    function clearCnameList(show = false) {
        $('#default-offloading-cname').empty();
        if (show === true) {
            $('<option selected hidden disabled>None</option>').appendTo($('#default-offloading-cname'));
        }
    }

    function clearBlocks() {
        $('#error-offload-block').empty();
        $('#success-offload-block').empty();
        $('#folder-success-block').empty();
        $('#folder-error-block').empty();
        $('#folder-success-image-quality').empty();
        $('#folder-error-image-quality').empty();
        $('#folder-success-video-quality').empty();
        $('#folder-error-video-quality').empty();
        $('#cname-success-block').empty();
        $('#cname-error-block').empty();
        $('#success-checkbox-block').empty();
        $('#error-checkbox-block').empty();
        $('#success-allow-block').empty();
        $('#error-allow-block').empty();
        $('#success-delete-block').empty();
        $('#error-delete-block').empty();
        $('#media-upload-message-success').empty();
        $('#media-upload-message-error').empty();
    }

    function showBlock(elem, content) {
        $(elem).text(content)
        setTimeout(function () {
            clearBlocks()
        }, 3000)
    }

    function updateDefaultFolderChange() {
        $('#default-offloading-folder').bind('change', function (event) {
            jQuery.post(ajaxurl, {
                action: 'update_default_offloading_folder',
                folder_id: event.target.value
            }, function (response) {
                if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#folder-success-block'), 'Great!');
                } else {
                    showBlock($('#folder-error-block'), 'Something went wrong.');
                }
            });
        });
    }

    function updateDefaultCnameChange() {
        $('#default-offloading-cname').bind('change', function (event) {
            jQuery.post(ajaxurl, {
                action: 'update_default_offloading_cname',
                cname_url: event.target.value
            }, function (response) {
                if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#cname-success-block'), 'Great!');
                } else {
                    showBlock($('#cname-error-block'), 'Something went wrong.');
                }
            });
        });
    }

    function changeImageQuality() {
        $("#offloading-image-quality").bind('change', function (event) {
            jQuery.post(ajaxurl, {
                    action: 'update_image_offloading_quality',
                    image_quality: event.target.value
                }, function (response) {
                    if (response.status === STATUSES.SUCCESS) {
                        showBlock($('#folder-success-image-quality'), 'Great!');
                    } else {
                        showBlock($('#folder-error-image-quality'), 'Something went wrong.');
                    }
                }
            )
        })
    }

    function changeVideoQuality() {
        $("#offloading-video-quality").bind('change', function (event) {
            jQuery.post(ajaxurl, {
                    action: 'update_video_offloading_quality',
                    video_quality: event.target.value
                }, function (response) {
                    if (response.status === STATUSES.SUCCESS) {
                        showBlock($('#folder-success-video-quality'), 'Great!');
                    } else {
                        showBlock($('#folder-error-video-quality'), 'Something went wrong.');
                    }
                }
            )
        })
    }

    function checkboxFiles() {
        $(".files-offload-input").bind('change', function (event) {
            jQuery.post(ajaxurl, {
                    action: 'update_files_checkbox',
                    id: event.target.id,
                    value: event.target.checked
                }, function (response) {
                    if (response.status === STATUSES.SUCCESS) {
                        showBlock($('#success-checkbox-block'), 'Great!');
                    } else {
                        showBlock($('#error-checkbox-block'), 'Something went wrong.');
                    }
                }
            )
        })
    }

    function changeDelete() {
        $("#delete_checkbox").bind('change', function (event) {
            jQuery.post(ajaxurl, {
                    action: 'update_delete_checkbox',
                    delete_checkbox: event.target.checked
                }, function (response) {
                    if (response.status === STATUSES.SUCCESS) {
                        showBlock($('#success-delete-block'), 'Great!');
                    } else {
                        showBlock($('#error-delete-block'), 'Something went wrong.');
                    }
                }
            )
        })
    }

    function syncMediaFiles() {
        $('#media-offload').bind('click', function (event) {
            let media_list = null;
            jQuery.get(ajaxurl, {action: 'get_media_list'}, function (response) {
                media_list = response.media;
                syncMedia(media_list);
            })
        })
    }

    function syncMedia(media_list) {
        let numOfUploaded = 0;
        $('#popup1').show();
        if (media_list !== undefined && media_list !== null) {
            let numOfMedia = media_list.length;
            media_list.forEach((media) => {
                jQuery.post(ajaxurl, {
                    action: 'sync_media_file',
                    attach_id: media.ID
                }, function (responseMedia) {
                    if (responseMedia.sync === true) {
                        numOfUploaded++;
                        let result = Math.round((numOfUploaded / numOfMedia) * 100);
                        console.log(result);
                        $("#myBar").width(result + "%");
                        $("#loadNumber").empty();
                        $("#loadNumber").text(result + "%");
                        if (numOfUploaded === numOfMedia) {
                            setTimeout(function () {
                                $('#popup1').hide();
                                $("#loadNumber").text(result + "%");
                                showBlock($('#media-upload-message-success'), 'You\'r media library is synchronized successfully!');
                            }, 1000)
                        }
                    } else {
                        $('#popup1').hide();
                        $("#myBar").width("0%");
                        $("#loadNumber").text("0%");
                        showBlock($('#media-upload-message-error'), 'Something went wrong.!');
                        return;
                    }
                })
            })
        }
    }


    function changeAllowDownload() {
        $('#allow-download').bind('change', function (event) {
            jQuery.post(ajaxurl, {
                action: 'update_allow_download',
                allow: event.target.checked
            }, function (response) {
                if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#success-allow-block'), 'Great!');
                } else {
                    showBlock($('#error-allow-block'), 'Something went wrong.');
                }
            });
        });
    }

    function setCheckBoxValue(allow) {
        if (allow !== '') {
            setCheckBoxDisabled('allow-download', false);
            if (allow === 'no') {
                $("#allow-download").attr('checked', false);
            } else {
                $("#allow-download").attr('checked', true);
            }
        } else {
            setCheckBoxDisabled('allow-download', true);
        }
    }

    function setCheckBoxDisabled(id, value) {
        if (value) {
            $("#" + id).attr("disabled", true);
        } else {
            $("#" + id).removeAttr("disabled");
        }
    }

    function setImageQualityValue(quality) {
        if (!quality || quality === "") {
            quality = '80';
        }
        $('#offloading-image-quality > option[value="' + quality + '"]').attr("selected", "selected");
    }

    function setVideoQualityValue(quality) {
        if (!quality || quality === "") {
            quality = '480';
        }
        $('#offloading-video-quality > option[value="' + quality + '"]').attr("selected", "selected");
    }

    function setFilesCheckbox(id, value) {
        if ((!value || value === "" || value === 'yes') && (id !== 'delete_checkbox')) {
            $("#" + id).attr('checked', true);
        } else if (id === 'delete_checkbox') {
            if (value === "yes") {
                $("#" + id).attr('checked', true);
            } else {
                $("#" + id).attr('checked', false);
            }
        } else {
            $("#" + id).attr('checked', false);
        }
    }

})(jQuery);

