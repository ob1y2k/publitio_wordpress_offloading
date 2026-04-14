(function ($) {
        'use strict'; 

        let timers = {
            recursiveTimeout : null,
            deleteTimeout : null,
            restoreTimeout : null
        };

        let updateLoading = false;
        let updateDangerLoading = false;
        let pwpoFolderSlimSelect = null;

        jQuery.extend({
            whenAll: function(expires, firstParam) {
                return whenAllFx(0, jQuery.makeArray(arguments));
            }
        });

        function whenAllFx(expires, args) {
            const def = jQuery.Deferred();
            let failed = false;
            const results = [];
            let to;

            if (expires) {
                to = setTimeout(function() {
                    def.reject('expired', results.slice(0), args);
                }, expires);
            }

            function next(remainingArgs) {
                if (remainingArgs.length) {
                    const arg = remainingArgs.shift();
                    results.push(arg);
                    jQuery.when(arg).fail(function() {
                        failed = true;
                    }).always(function() {
                        next(remainingArgs);
                    });
                }
                else {
                    to && clearTimeout(to);
                    if (failed) {
                        def.reject(results);
                    }
                    else {
                        def.resolve(results);
                    }
                }
            }

            next(args.slice(0));

            return def.promise();
        }

        const STATUSES = {
            ERROR_UNAUTHORIZED: 401,
            ERROR: 500,
            SUCCESS: 200
        }

        $(function () {
            if ($('#_wpnonce').length) {
                getPublitioAccountSettings()
            }
            updatePublitioSettingsButtonClick()
            updatePublitioDangerSettingsButtonClick()
            rebuildPostDataToggle()
            syncPublitioMediaFiles()
            deletePublitioMediaFiles()
            restorePublitioMediaFiles()
            $('#pwpo-popup-close-btn').on('click', function () {
                hideBulkOperationPopup()
            })
        });

        function parseAjaxJsonResponse(data) {
            if (data == null) {
                return {}
            }
            if (typeof data === 'string') {
                try {
                    return JSON.parse(data)
                } catch (e) {
                    return {}
                }
            }
            return data
        }

        function hideBulkOperationPopup() {
            $('#pwpo-popup').hide()
            $('#pwpo-popup-title').hide()
            $('#pwpo-popup-close-btn').hide()
            $('#pwpo-popup-results').hide()
            $('#pwpoPublitioProgress').show()
            $('#pwpo-publitioBar').removeClass('pwpo-progress-idle').width('0%')
            $('#pwpoLoadPublitioNumber').html('')
            $('#pwpo-popup-results-summary').html('')
        }

        function showBulkOperationProgress(titleText) {
            $('#pwpo-popup-close-btn').hide()
            $('#pwpo-popup-results').hide()
            $('#pwpoPublitioProgress').show()
            $('#pwpo-publitioBar').removeClass('pwpo-progress-idle').width('0%')
            $('#pwpoLoadPublitioNumber').html('')
            $('#pwpo-popup-title').text(titleText).show()
            $('#pwpo-popup').show()
        }

        function showBulkOperationComplete(options) {
            const o = $.extend({
                headline: 'Finished',
                successLabel: 'Completed',
                summaryHtml: '',
                success: 0,
                failed: 0,
                skipped: 0,
                total: 0
            }, options)

            const total = o.total > 0 ? o.total : (o.success + o.failed + o.skipped)

            $('#pwpo-popup-title').text(o.headline).show()
            $('#pwpoPublitioProgress').hide()
            $('#pwpo-popup-results-summary').html(o.summaryHtml)
            $('#pwpo-stat-success-label').text(o.successLabel)
            $('#pwpo-stat-success').text(o.success)
            $('#pwpo-stat-failed').text(o.failed)
            $('#pwpo-stat-skipped').text(o.skipped)
            $('#pwpo-stat-total').text(total)
            $('#pwpo-popup-results').show()
            $('#pwpo-popup-close-btn').show()
            $('#pwpo-popup').show()
        }

        function showBulkOperationEmpty(headline, messageHtml, successStatLabel) {
            showBulkOperationComplete({
                headline: headline,
                successLabel: successStatLabel || 'Synced',
                summaryHtml: messageHtml,
                success: 0,
                failed: 0,
                skipped: 0,
                total: 0
            })
        }

        function updatePublitioSettingsButtonClick() {
            $('#pwpo-update-offloading-button').on('click', function (event) {
                if(updateLoading) {
                    return;
                }
                setUpdateLoading(true);
                const api_key = $('#api_key').val();
                const api_secret = $('#api_secret').val();
                if(api_key === '' || api_secret === '') {
                    showToast('⚠ Please fill in all fields', 'error');
                    setUpdateLoading(false)
                    return
                  }
                jQuery.post(ajaxurl, {
                    action: 'pwpo_update_offloading_settings',
                    api_secret: api_secret,
                    api_key: api_key,
                    wpnonce: $('#_wpnonce').val(),
                    allow_download: $('#allow_download').is(':checked'),
                    offload_templates: $('#offload_templates').is(':checked'),
                    image_checkbox: $('#image_checkbox').is(':checked'),
                    video_checkbox: $('#video_checkbox').is(':checked'),
                    audio_checkbox: $('#audio_checkbox').is(':checked'),
                    document_checkbox: $('#document_checkbox').is(':checked'),
                    folder_id: $('#pwpo-default-offloading-folder').val(),
                    cname_url: $('#pwpo-default-offloading-cname').val(),
                    image_quality: $('#offloading-image-quality').val(),
                    video_quality: $('#offloading-video-quality').val(),
                    delete_checkbox: $('#delete_checkbox').is(':checked'),
                    rebuild_post_data: $('#pwpo-rebuild-post-data').is(':checked'),
                }, function (response) {
                    if (response.status === STATUSES.ERROR_UNAUTHORIZED) {
                        clearFolderList(true);
                        clearCnameList(true);
                        authError();
                        showToast('⚠ Bad credentials', 'error');
                    } else if (response.status === STATUSES.SUCCESS) {
                        handleResponse(response);
                        showToast('🎉 Great, settings updated!', 'success');
                    } else {
                        clearFolderList(true);
                        clearCnameList(true);
                        authError();
                        showToast('⚠ Something went wrong', 'error');
                    }
                    setUpdateLoading(false);
                });
            });
        }

        function updatePublitioDangerSettingsButtonClick() {
            $('#pwpo-update-danger-settings-button').on('click', function (event) {
                if(updateDangerLoading) {
                    return;
                }
                setDangerUpdateLoading(true);

                jQuery.post(ajaxurl, {
                    action: 'pwpo_update_replace_media',
                    wpnonce: $('#_wpnonce').val(),
                    replace_checkbox: $('#replace_checkbox').is(':checked'),
                }, function (response) {
                    if (response.status === STATUSES.ERROR_UNAUTHORIZED) {
                        clearFolderList(true);
                        clearCnameList(true);
                        authError();
                        showToast('⚠ Bad credentials', 'error');
                    } else if (response.status === STATUSES.SUCCESS) {
                        showToast('🎉 Great, settings updated!', 'success');
                    } else {
                        clearFolderList(true);
                        clearCnameList(true);
                        authError();
                        showToast('⚠ Something went wrong', 'error');
                    }
                    setDangerUpdateLoading(false);
                });
            });
        }

        function handleResponse(response) {
            if (response.folders != null) {
                authSuccess();
                updateCharts(response.wordpress_data);
                addFoldersList(response.folders, response.default_folder_id);
                addCnameList(response.cnames, response.default_cname_url);
                setImageQualityValue(response.image_quality);
                setVideoQualityValue(response.video_quality);
            } else {
                authError();
            }
        }

        function setUpdateLoading(loading) {
            if(loading) {
              $('#pwpo-update-offloading-button').text('Updating Settings...')
              $('#pwpo-update-offloading-button').css('opacity', 0.5)
              $('#pwpo-update-offloading-button').css('cursor', 'not-allowed')
            } else {
                $('#pwpo-update-offloading-button').text('Update Settings')
                $('#pwpo-update-offloading-button').css('opacity', 1)
                $('#pwpo-update-offloading-button').css('cursor', 'pointer')
                updateLoading = false;
            }
            $('#pwpo-update-offloading-button').prop('disabled', loading)
          }

          function setDangerUpdateLoading(loading) {
            if(loading) {
                $('#pwpo-update-danger-settings-button').text('Updating Settings...')
                $('#pwpo-update-danger-settings-button').css('opacity', 0.5)
                $('#pwpo-update-danger-settings-button').css('cursor', 'not-allowed')
            } else {
                $('#pwpo-update-danger-settings-button').text('Update Settings')
                $('#pwpo-update-danger-settings-button').css('opacity', 1)
                $('#pwpo-update-danger-settings-button').css('cursor', 'pointer')
                updateLoading = false;
            }
            $('#pwpo-update-danger-settings-button').prop('disabled', loading)
          }

        function authSuccess() {
            $('.pwpo-page-warning-message').css('display', 'none')
            $(".pwpo-requires-auth").css("opacity", "1");
            $(".pwpo-requires-auth").css("pointer-events", "auto");
        }

        function authError() {
            $('.pwpo-page-warning-message').css('display', 'flex')
            $(".pwpo-requires-auth").css("opacity", "0.5");
            $(".pwpo-requires-auth").css("pointer-events", "none");

            const $chartStorage = $('.pwpo-storage-chart')
            const $percentageStorage = $('.pwpo-storage-percentage')
            $percentageStorage.text('0%')
            $chartStorage.attr('data-percentage', 0)
            $chartStorage.css('background', 'conic-gradient(#e5e7eb 0deg, #e5e7eb 360deg )')
            $('.pwpo-storage-used').text(`Storage used: 0B`)
            $('.pwpo-storage-limit').text(`Storage limit: 0B`)

            const $chartBandwidth = $('.pwpo-bandwidth-chart')
            const $percentageBandwidth = $('.pwpo-bandwidth-percentage')
            $percentageBandwidth.text('0%')
            $chartBandwidth.attr('data-percentage', 0)
            $chartBandwidth.css('background', 'conic-gradient(#e5e7eb 0deg, #e5e7eb 360deg )')
            $('.pwpo-bandwidth-used').text(`Bandwidth used: 0B`)
            $('.pwpo-bandwidth-limit').text(`Bandwidth limit: 0B`)

            $('#pwpo-plan-used').text('None')
        }

        function updateCharts(wordpressData) {
            if (!wordpressData) {
                return
              }
          
              const usedStorage = wordpressData.account_storage ?? '0B'
              const maxStorage = wordpressData.account_max_storage ?? '0B'
              const percentStorage = wordpressData.account_storage_percentage ?? 0
              
              const $chartStorage = $('.pwpo-storage-chart')
              const $percentageStorage = $('.pwpo-storage-percentage')

              if ($chartStorage.length && $percentageStorage.length) {
                $percentageStorage.text(percentStorage + '%')
                $chartStorage.attr('data-percentage', percentStorage)
                
                const degrees = percentStorage * 3.6
                const gradient = `conic-gradient(
                  #4099de 0deg,
                  #4099de ${degrees}deg,
                  #e5e7eb ${degrees}deg,
                  #e5e7eb 360deg
                )`
                $chartStorage.css('background', gradient)
                      
                $('.pwpo-storage-used').text(`Storage used: ${usedStorage}`)
                $('.pwpo-storage-limit').text(`Storage limit: ${maxStorage}`)
              }
          
              const usedBandwidth = wordpressData.account_bandwidth ?? '0B'
              const maxBandwidth = wordpressData.account_max_bandwidth ?? '0B'
              const percentBandwidth = wordpressData.account_bandwidth_percentage ?? 0
          
              const $chartBandwidth = $('.pwpo-bandwidth-chart')
              const $percentageBandwidth = $('.pwpo-bandwidth-percentage')
              
              if ($chartBandwidth.length && $percentageBandwidth.length) {
                $percentageBandwidth.text(percentBandwidth + '%')
                $chartBandwidth.attr('data-percentage', percentBandwidth)
                
                const degrees = percentBandwidth * 3.6
                const gradient = `conic-gradient(
                  #4099de 0deg,
                  #4099de ${degrees}deg,
                  #e5e7eb ${degrees}deg,
                  #e5e7eb 360deg
                )`
                $chartBandwidth.css('background', gradient)
                      
                $('.pwpo-bandwidth-used').text(`Bandwidth used: ${usedBandwidth}`)
                $('.pwpo-bandwidth-limit').text(`Bandwidth limit: ${maxBandwidth}`)
              }
          
              const userPlan = wordpressData.account_plan ?? 'None'
              $('#pwpo-plan-used').text(userPlan)
        }

        function getPublitioAccountSettings() {
            jQuery.post(ajaxurl, {
                action: 'pwpo_get_offloading_account_settings',
                wpnonce: $('#_wpnonce').val()
            }, function (response) {
                handleResponse(response);
            });
        }

        function destroyPwpoFolderSlimSelect() {
            if (typeof SlimSelect === 'undefined' || !pwpoFolderSlimSelect) {
                pwpoFolderSlimSelect = null;
                return;
            }
            try {
                pwpoFolderSlimSelect.destroy();
            } catch (e) {
                /* ignore */
            }
            pwpoFolderSlimSelect = null;
        }

        function initPwpoFolderSlimSelect() {
            destroyPwpoFolderSlimSelect();
            if (typeof SlimSelect === 'undefined') {
                return;
            }
            const $folder = $('#pwpo-default-offloading-folder');
            if (!$folder.length || $folder.find('option').length === 0) {
                return;
            }
            const l10n = typeof pwpoOffloadingL10n !== 'undefined' ? pwpoOffloadingL10n : {};
            pwpoFolderSlimSelect = new SlimSelect({
                select: '#pwpo-default-offloading-folder',
                settings: {
                    showSearch: true,
                    searchHighlight: true,
                    searchPlaceholder: l10n.folderSearchPlaceholder || 'Search folders…',
                },
                cssClasses: {
                    option: 'pwpo-ss-option',
                    list: 'pwpo-ss-list',
                    content: 'pwpo-ss-content'
                }
            });
        }

        function addFoldersList(folders, defaultFolderId = '') {
            clearFolderList();
            if (folders !== undefined && folders !== null) {
                const $sel = $('#pwpo-default-offloading-folder');
                $('<option value="">/</option>').appendTo($sel);
                folders.forEach((folder) => {
                    $('<option/>').val(folder.id).text(folder.path).appendTo($sel);
                });
                setSelectedOffloadingFolder(defaultFolderId);
                initPwpoFolderSlimSelect();
            }
        }

        function addCnameList(cnames, defaultCnameId = '') {
            clearCnameList();
            if (cnames !== undefined && cnames !== null) {
                cnames.forEach((cname) => {
                    $('<option value="' + cname.url + '">' + cname.url + '</option>').appendTo($('#pwpo-default-offloading-cname'));
                })
                setSelectedOffloadingCname(defaultCnameId);
            }
        }

        function setSelectedOffloadingFolder(id) {
            $('#pwpo-default-offloading-folder').val(id);
        }

        function setSelectedOffloadingCname(id) {
            $('#pwpo-default-offloading-cname > option[value="' + id + '"]').attr("selected", "selected");
        }

        function clearFolderList(show = false) {
            destroyPwpoFolderSlimSelect();
            $('#pwpo-default-offloading-folder').empty();
            if (show === true) {
                $('<option selected hidden disabled>None</option>').appendTo($('#pwpo-default-offloading-folder'));
            }
        }

        function clearCnameList(show = false) {
            $('#pwpo-default-offloading-cname').empty();
            if (show === true) {
                $('<option selected hidden disabled>None</option>').appendTo($('#pwpo-default-offloading-cname'));
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

        function syncPublitioMediaFiles() {
            $('#pwpo-sync-now-button').on('click', function (event) {
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('Checking…');
                jQuery.post(ajaxurl, {
                    action: 'pwpo_get_media_list',
                    wpnonce: $('#_wpnonce').val()
                })
                    .done(function (response) {
                        $btn.prop('disabled', false).text(originalText);
                        syncPublitioMedia(response ? response.media : null);
                    })
                    .fail(function () {
                        $btn.prop('disabled', false).text(originalText);
                    });
            });
        }

        function rebuildPostDataToggle() {
            $('#pwpo-rebuild-post-data').on('change', function () {
                const enabled = $(this).is(':checked');
                jQuery.post(ajaxurl, {
                    action: 'pwpo_update_rebuild_post_data',
                    wpnonce: $('#_wpnonce').val(),
                    rebuild_post_data: enabled,
                });
            });
        }

         function media_list_sync(mainList,media_list,index,resultInfo) {
            const requestList = [];
            media_list.forEach((media) => {
                requestList.push(
                    jQuery.post(ajaxurl, {
                        sync:false,
                        action: 'pwpo_sync_media_file',
                        attach_id: media.ID,
                        wpnonce: $('#_wpnonce').val()
                    },
                        function (responseMedia) {
                            const res = parseAjaxJsonResponse(responseMedia)
                            if (res.sync === true) {
                                if (res.skipped) {
                                    resultInfo.numOfSkipped++;
                                } else {
                                    resultInfo.numOfUploaded++;
                                }
                            } else if (res.reason === 'no_publitio_meta') {
                                resultInfo.numOfSkipped++;
                            } else {
                                resultInfo.numOfFailed++;
                                console.error('[Publitio Offloading] Sync failed for attachment', media.ID, {
                                    title: media.post_title,
                                    response: res
                                });
                            }
                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        resultInfo.numOfFailed++;
                        console.error('[Publitio Offloading] Sync request failed for attachment', media.ID, {
                            title: media.post_title,
                            textStatus: textStatus,
                            errorThrown: errorThrown,
                            status: jqXHR.status,
                            responseText: jqXHR.responseText
                        });
                    }).always(function() {
                        const done = resultInfo.numOfUploaded + resultInfo.numOfFailed + resultInfo.numOfSkipped;
                        let result = ((done / resultInfo.numOfMedia) * 100).toFixed(1);
                        $("#pwpo-publitioBar").width(result + "%");
                        let resFailed = "";
                        if (resultInfo.numOfFailed !== 0) {
                            resFailed = ' <span class="red-text">(' + resultInfo.numOfFailed + ' failed)</span>';
                        }
                        let resSkipped = "";
                        if (resultInfo.numOfSkipped !== 0) {
                            resSkipped = ' (' + resultInfo.numOfSkipped + ' skipped)';
                        }
                        $("#pwpoLoadPublitioNumber").html(done + " of " + resultInfo.numOfMedia + resFailed + resSkipped + " / " + result + "% completed");
                    })
                );
            });

             $.whenAll(...requestList).done(function(x){
                 recursiveMediaLoading(mainList,index+1,resultInfo);
             }).fail(function(x) {
                 recursiveMediaLoading(mainList,index+1,resultInfo);
             })
        }

        function recursiveMediaLoading(media_list, index , resultInfo) {
            if(index < media_list.length) {
                media_list_sync(media_list,media_list[index],index,resultInfo);
            } else {
                const done = resultInfo.numOfUploaded + resultInfo.numOfFailed + resultInfo.numOfSkipped;
                if (done === resultInfo.numOfMedia) {
                    if(timers && timers['recursiveTimeout']) {
                        clearTimeout(timers['recursiveTimeout']);
                        timers['recursiveTimeout'] = null;
                    }
                    timers['recursiveTimeout'] = setTimeout(function () {
                        let summary = '';
                        if (resultInfo.numOfFailed === 0 && resultInfo.numOfUploaded === 0 && resultInfo.numOfSkipped > 0) {
                            summary = 'No new files were uploaded or updated. Skipped includes items already in sync on Publitio and items with no local file (upload not possible).';
                        } else if (resultInfo.numOfFailed === 0 && resultInfo.numOfSkipped === 0) {
                            summary = 'All items finished without errors.';
                        } else if (resultInfo.numOfFailed !== 0) {
                            summary = 'Some items could not be synchronized. Check the browser console for details.';
                        } else {
                            summary = 'Skipped includes items already in sync and items with no local file (cannot upload).';
                        }
                        showBulkOperationComplete({
                            headline: 'Synchronization finished',
                            successLabel: 'Synced',
                            summaryHtml: summary,
                            success: resultInfo.numOfUploaded,
                            failed: resultInfo.numOfFailed,
                            skipped: resultInfo.numOfSkipped,
                            total: resultInfo.numOfMedia
                        })
                    }, 400)
                }
            }
        }

        function syncPublitioMedia(media_list) {
            if (media_list !== undefined && media_list !== null && media_list.length > 0) {
                if (confirm('Are you sure you want to synchronize all media files with Publitio?')) {
                    const resultInfo = {
                        numOfUploaded: 0,
                        numOfFailed: 0,
                        numOfSkipped: 0,
                        numOfMedia: media_list.map((item) => item.length).reduce((a,b) => a+b,0)
                    };
                    showBulkOperationProgress('Synchronizing media with Publitio…');
                    recursiveMediaLoading(media_list,0,resultInfo);
                }
            } else {
                showBulkOperationEmpty('Nothing to synchronize', 'No media items matched your current offloading filters, or the library is empty.', 'Synced');
            }
        }

        function deletePublitioMediaFiles() {
            $('#media-delete').on('click', function (event) {
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('Checking…');
                jQuery.post(ajaxurl, {
                    action: 'pwpo_get_media_list_for_delete',
                    wpnonce: $('#_wpnonce').val()
                })
                    .done(function (response) {
                        $btn.prop('disabled', false).text(originalText);
                        deletePublitioMedia(response ? response.media : null);
                    })
                    .fail(function () {
                        $btn.prop('disabled', false).text(originalText);
                    });
            });
        }

        function deletePublitioMedia(media_list) {
            if (media_list !== undefined && media_list !== null && media_list.length > 0) {
                if (confirm('Are you sure you want to delete all offloaded Media locally and replace it with Publitio Media URLs? Plugin will delete files from local storage - but if you choose to deactivate Publitio Offloading plugin in the future, your site posts/pages will result in broken media links (as they are no longer present locally). Use with caution & at your own risk as there is no going back once you use this options!')) {
                    let numOfDeleted = 0;
                    let numOfDeletedFailed = 0;
                    const numSkippedDelete = 0;
                    const numOfMediaForDelete = media_list.length;
                    let deleteCompleteScheduled = false;
                    showBulkOperationProgress('Deleting local copies of offloaded media…');
                    media_list.forEach((media) => {
                        jQuery.post(ajaxurl, {
                            async: false,
                            action: 'pwpo_delete_media_file',
                            attach_id: media.ID,
                            wpnonce: $('#_wpnonce').val()
                        })
                            .done(function (responseMedia) {
                                const res = parseAjaxJsonResponse(responseMedia);
                                if (res.deleted === true) {
                                    numOfDeleted++;
                                } else {
                                    numOfDeletedFailed++;
                                }
                            })
                            .fail(function () {
                                numOfDeletedFailed++;
                            })
                            .always(function () {
                                const done = numOfDeleted + numOfDeletedFailed;
                                const result = ((done / numOfMediaForDelete) * 100).toFixed(1);
                                $("#pwpo-publitioBar").width(result + "%");
                                let resDeleteFailed = "";
                                if (numOfDeletedFailed !== 0) {
                                    resDeleteFailed = '<span class="red-text"> (' + numOfDeletedFailed + ' failed)</span>';
                                }
                                $("#pwpoLoadPublitioNumber").html(done + " of " + numOfMediaForDelete + resDeleteFailed + " / " + result + "% completed");
                                if (done === numOfMediaForDelete && !deleteCompleteScheduled) {
                                    deleteCompleteScheduled = true;
                                    if (timers && timers['deleteTimeout']) {
                                        clearTimeout(timers['deleteTimeout']);
                                        timers['deleteTimeout'] = null;
                                    }
                                    timers['deleteTimeout'] = setTimeout(function () {
                                        let summary = '';
                                        if (numOfDeletedFailed === 0) {
                                            summary = 'Local copies were removed for all listed items.';
                                        } else {
                                            summary = 'Some items could not be deleted locally. Check file permissions and the browser console.';
                                        }
                                        showBulkOperationComplete({
                                            headline: 'Local delete finished',
                                            successLabel: 'Deleted locally',
                                            summaryHtml: summary,
                                            success: numOfDeleted,
                                            failed: numOfDeletedFailed,
                                            skipped: numSkippedDelete,
                                            total: numOfMediaForDelete
                                        });
                                    }, 400);
                                }
                            });
                    });
                }
            } else {
                showBulkOperationEmpty('Nothing to delete', 'There are no offloaded media items that still have a local file to remove.', 'Deleted locally');
            }
        }

        function restorePublitioMediaFiles() {
            $('#media-restore').on('click', function (event) {
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('Checking…');
                console.log('[Publitio Restore] Fetching restorable media list…');
                jQuery.post(ajaxurl, {
                    action: 'pwpo_get_media_list_for_restore',
                    wpnonce: $('#_wpnonce').val()
                })
                .done(function (response, status, xhr) {
                    console.log('[Publitio Restore] Raw response text:', xhr.responseText);
                    console.log('[Publitio Restore] Parsed response:', response);
                    console.log('[Publitio Restore] Media list:', response ? response.media : 'undefined');
                    $btn.prop('disabled', false).text(originalText);
                    restorePublitioMedia(response ? response.media : null);
                })
                .fail(function (xhr, status, error) {
                    console.error('[Publitio Restore] AJAX request failed:', status, error);
                    console.error('[Publitio Restore] Raw response text:', xhr.responseText);
                    $btn.prop('disabled', false).text(originalText);
                });
            });
        }

        function restorePublitioMedia(media_list) {
            if (media_list !== undefined && media_list !== null && media_list.length > 0) {
                if (confirm('Are you sure you want to return ' + media_list.length + ' media file(s) from Publitio back to their local WordPress folders? The files will be downloaded and the Publitio URL will be replaced with the local file.')) {
                    let numOfRestored = 0;
                    let numOfRestoreFailed = 0;
                    const numSkippedRestore = 0;
                    const numOfMediaForRestore = media_list.length;
                    let restoreCompleteScheduled = false;
                    showBulkOperationProgress('Restoring media to local folders…');
                    media_list.forEach((media) => {
                        jQuery.post(ajaxurl, {
                            action: 'pwpo_restore_media_file',
                            attach_id: media.ID,
                            wpnonce: $('#_wpnonce').val()
                        })
                            .done(function (responseMedia) {
                                const res = parseAjaxJsonResponse(responseMedia);
                                if (res.restored === true) {
                                    numOfRestored++;
                                } else {
                                    numOfRestoreFailed++;
                                }
                            })
                            .fail(function () {
                                numOfRestoreFailed++;
                            })
                            .always(function () {
                                const done = numOfRestored + numOfRestoreFailed;
                                const result = ((done / numOfMediaForRestore) * 100).toFixed(1);
                                $("#pwpo-publitioBar").width(result + "%");
                                let resRestoreFailed = "";
                                if (numOfRestoreFailed !== 0) {
                                    resRestoreFailed = '<span class="red-text"> (' + numOfRestoreFailed + ' failed)</span>';
                                }
                                $("#pwpoLoadPublitioNumber").html(done + " of " + numOfMediaForRestore + resRestoreFailed + " / " + result + "% completed");
                                if (done === numOfMediaForRestore && !restoreCompleteScheduled) {
                                    restoreCompleteScheduled = true;
                                    if (timers && timers['restoreTimeout']) {
                                        clearTimeout(timers['restoreTimeout']);
                                        timers['restoreTimeout'] = null;
                                    }
                                    timers['restoreTimeout'] = setTimeout(function () {
                                        let summary = '';
                                        if (numOfRestoreFailed === 0) {
                                            summary = 'Files were downloaded back to your uploads folder.';
                                        } else {
                                            summary = 'Some downloads failed (network, disk space, or missing Publitio file). Check the browser console.';
                                        }
                                        showBulkOperationComplete({
                                            headline: 'Restore to local finished',
                                            successLabel: 'Restored',
                                            summaryHtml: summary,
                                            success: numOfRestored,
                                            failed: numOfRestoreFailed,
                                            skipped: numSkippedRestore,
                                            total: numOfMediaForRestore
                                        });
                                    }, 400);
                                }
                            });
                    });
                }
            } else {
                showBulkOperationEmpty('Nothing to restore', 'Every attachment already has a local file, or none are eligible for restore.', 'Restored');
            }
        }

        function showToast(content, type) {
            let style = {
              background: "linear-gradient(135deg,#73a5ff,#4099de)",
              borderRadius: "5px",
            }
        
            if(type === 'error') {
              style = {
                background: "linear-gradient(135deg,#ED775A,#E4004B)",
                borderRadius: "5px",
              }
            }
        
            Toastify({
              text: content,
              duration: 3000,
              gravity: 'bottom',
              position: 'center',
              style: style,
            }).showToast();
          }
    }

)(jQuery);
