(function ($) {
    'use strict';

    const STATUSES = {
        ERROR_UNAUTHORIZED: 401,
        ERROR: 500,
        SUCCESS: 200
    }

    $(function () {
        getFoldersList()
        updateSettingsButtonClick()
        updateDefaultFolderChange()
        changeAllowDownload()
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
                    clearFolderList();
                    setCheckBoxValue('');
                } else if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#success-offload-block'), 'Great!');
                    addFoldersList(response.folders);
                    setCheckBoxValue(response.allow_download);
                } else {
                    showBlock($('#error-offload-block'), 'Something went wrong.');
                    setCheckBoxValue('');
                }
            });
        });
    }

    function getFoldersList() {
        jQuery.get(ajaxurl, {action: 'get_offloading_folders_tree'}, function (response) {
            addFoldersList(response.folders, response.default_folder_id);
            setCheckBoxValue(response.allow_download);
        })
    }

    function addFoldersList(folders, defaultFolderId = '') {
        clearFolderList();
        if (folders != undefined && folders != null) {
            $('<option value="">/</option>').appendTo($('#default-offloading-folder'));
            folders.forEach((folder) => {
                $('<option value="' + folder.id + '">' + folder.path + '</option>').appendTo($('#default-offloading-folder'));
            })
            setSelectedOffloadingFolder(defaultFolderId);
        }
    }

    function setSelectedOffloadingFolder(id) {
        $('#default-offloading-folder > option[value="' + id + '"]').attr("selected", "selected");
    }

    function clearFolderList() {
        $('#default-offloading-folder').empty();
        $('<option selected hidden disabled>None</option>').appendTo($('#default-offloading-folder'));
    }

    function clearBlocks() {
        $('#error-offload-block').empty();
        $('#success-offload-block').empty();
        $('#folder-success-block').empty();
        $('#folder-error-block').empty();
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
                if (response.status === STATUSES.ERROR_UNAUTHORIZED) {
                    showBlock($('#folder-error-block'), 'Wrong credentials');
                } else if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#folder-success-block'), 'Great!');
                } else {
                    showBlock($('#folder-error-block'), 'Something went wrong.');
                }
            });
        });
    }

    function changeAllowDownload() {
        let allow;
        $('#allow-download').bind('change', function (event) {
            if ($('#allow-download').is(":checked")) {
                allow = true;
            } else {
                allow = false;
            }
            jQuery.post(ajaxurl, {
                action: 'update_allow_download',
                allow: allow
            }, function (response) {

            });
        });
    }

    function setCheckBoxValue(allow) {
        if(allow !== '') {
            setCheckBoxDisabled(false);
            if (allow === 'no') {
                $("#allow-download").attr('checked', false);
            } else {
                $("#allow-download").attr('checked', true);
            }
        } else {
            setCheckBoxDisabled(true);
        }
    }

    function setCheckBoxDisabled(value) {
        if(value) {
            $("#allow-download").attr("disabled", true);
        } else {
            $("#allow-download").removeAttr("disabled");
        }
    }

})(jQuery);



