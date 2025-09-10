(function ($) {
        'use strict'; 

        let timers = {
            recursiveTimeout : null,
            deleteTimeout : null

        };

        let updateLoading = false;
        let updateDangerLoading = false;

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
            getPublitioAccountSettings()
            updatePublitioSettingsButtonClick()
            updatePublitioDangerSettingsButtonClick()
            syncPublitioMediaFiles()
            deletePublitioMediaFiles()
        });

        function updatePublitioSettingsButtonClick() {
            $('#update-offloading-button').on('click', function (event) {
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
                    folder_id: $('#default-publitio-offloading-folder').val(),
                    cname_url: $('#default-publitio-offloading-cname').val(),
                    image_quality: $('#offloading-image-quality').val(),
                    video_quality: $('#offloading-video-quality').val(),
                    delete_checkbox: $('#delete_checkbox').is(':checked'),
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
            $('#publitio-update-danger-settings-button').on('click', function (event) {
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
              $('#update-offloading-button').text('Updating Settings...')
              $('#update-offloading-button').css('opacity', 0.5)
              $('#update-offloading-button').css('cursor', 'not-allowed')
            } else {
                $('#update-offloading-button').text('Update Settings')
                $('#update-offloading-button').css('opacity', 1)
                $('#update-offloading-button').css('cursor', 'pointer')
                updateLoading = false;
            }
            $('#update-offloading-button').prop('disabled', loading)
          }

          function setDangerUpdateLoading(loading) {
            if(loading) {
                $('#publitio-update-danger-settings-button').text('Updating Settings...')
                $('#publitio-update-danger-settings-button').css('opacity', 0.5)
                $('#publitio-update-danger-settings-button').css('cursor', 'not-allowed')
            } else {
                $('#publitio-update-danger-settings-button').text('Update Settings')
                $('#publitio-update-danger-settings-button').css('opacity', 1)
                $('#publitio-update-danger-settings-button').css('cursor', 'pointer')
                updateLoading = false;
            }
            $('#publitio-update-danger-settings-button').prop('disabled', loading)
          }

        function authSuccess() {
            $('.publitio-page-warning-message').css('display', 'none')
            $(".publitio-requires-auth").css("opacity", "1");
            $(".publitio-requires-auth").css("pointer-events", "auto");
        }

        function authError() {
            $('.publitio-page-warning-message').css('display', 'flex')
            $(".publitio-requires-auth").css("opacity", "0.5");
            $(".publitio-requires-auth").css("pointer-events", "none");
        }

        function updateCharts(wordpressData) {
            if (!wordpressData) {
                return
              }
          
              const usedStorage = wordpressData.account_storage ?? '0B'
              const maxStorage = wordpressData.account_max_storage ?? '0B'
              const percentStorage = wordpressData.account_storage_percentage ?? 0
              
              const $chartStorage = $('.publitio-storage-chart')
              const $percentageStorage = $('.publitio-storage-percentage')
              
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
                      
                $('.publitio-storage-used').text(`Storage used: ${usedStorage}`)
                $('.publitio-storage-limit').text(`Storage limit: ${maxStorage}`)
              }
          
              const usedBandwidth = wordpressData.account_bandwidth ?? '0B'
              const maxBandwidth = wordpressData.account_max_bandwidth ?? '0B'
              const percentBandwidth = wordpressData.account_bandwidth_percentage ?? 0
          
              const $chartBandwidth = $('.publitio-bandwidth-chart')
              const $percentageBandwidth = $('.publitio-bandwidth-percentage')
              
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
                      
                $('.publitio-bandwidth-used').text(`Bandwidth used: ${usedBandwidth}`)
                $('.publitio-bandwidth-limit').text(`Bandwidth limit: ${maxBandwidth}`)
              }
          
              const userPlan = wordpressData.account_plan ?? 'None'
              $('#publitio-plan-used').text(userPlan)
        }

        function getPublitioAccountSettings() {
            jQuery.get(ajaxurl, { action: 'pwpo_get_offloading_account_settings' }, function (response) {
                handleResponse(response);
            });
        }

        function addFoldersList(folders, defaultFolderId = '') {
            clearFolderList();
            if (folders !== undefined && folders !== null) {
                $('<option value="">/</option>').appendTo($('#default-publitio-offloading-folder'));
                folders.forEach((folder) => {
                    $('<option value="' + folder.id + '">' + folder.path + '</option>').appendTo($('#default-publitio-offloading-folder'));
                });
                setSelectedOffloadingFolder(defaultFolderId);
            }
        }

        function addCnameList(cnames, defaultCnameId = '') {
            clearCnameList();
            if (cnames !== undefined && cnames !== null) {
                cnames.forEach((cname) => {
                    $('<option value="' + cname.url + '">' + cname.url + '</option>').appendTo($('#default-publitio-offloading-cname'));
                })
                setSelectedOffloadingCname(defaultCnameId);
            }
        }

        function setSelectedOffloadingFolder(id) {
            $('#default-publitio-offloading-folder > option[value="' + id + '"]').attr("selected", "selected");
        }

        function setSelectedOffloadingCname(id) {
            $('#default-publitio-offloading-cname > option[value="' + id + '"]').attr("selected", "selected");
        }

        function clearFolderList(show = false) {
            $('#default-publitio-offloading-folder').empty();
            if (show === true) {
                $('<option selected hidden disabled>None</option>').appendTo($('#default-publitio-offloading-folder'));
            }
        }

        function clearCnameList(show = false) {
            $('#default-publitio-offloading-cname').empty();
            if (show === true) {
                $('<option selected hidden disabled>None</option>').appendTo($('#default-publitio-offloading-cname'));
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
            $('#publitio-sync-now-button').on('click', function (event) {
                let media_list = null;
                jQuery.get(ajaxurl, {
                    action: 'pwpo_get_media_list'
                }, function (response) {
                    media_list = response.media;
                    syncPublitioMedia(media_list);
                })
            })
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
                            if (responseMedia.sync === true) {
                                resultInfo.numOfUploaded++;
                            } else {
                                resultInfo.numOfFailed++;
                            }
                    }).fail(function() {
                        resultInfo.numOfFailed++;
                    }).always(function() {
                        let result = (((resultInfo.numOfUploaded + resultInfo.numOfFailed) / resultInfo.numOfMedia) * 100).toFixed(1);
                        $("#publitioBar").width(result + "%");
                        let resFailed = "";
                        if (resultInfo.numOfFailed !== 0) {
                            resFailed = ' <span class="red-text">(' + resultInfo.numOfFailed + ' failed)</span>';
                        }
                        $("#loadPublitioNumber").html(resultInfo.numOfUploaded + " of " + resultInfo.numOfMedia + resFailed + " / " + result + "% completed");
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
                if ((resultInfo.numOfUploaded+resultInfo.numOfFailed) === resultInfo.numOfMedia) {
                    if(timers && timers['recursiveTimeout']) {
                        clearTimeout(timers['recursiveTimeout']);
                        timers['recursiveTimeout'] = null;
                    }
                    timers['recursiveTimeout'] = setTimeout(function () {
                        $('#publitio-popup').hide();
                        $("#loadPublitioNumber").html(0);
                        $("#publitioBar").width("0%");
                        if(resultInfo.numOfFailed !== 0) {
                            showToast(resultInfo.numOfUploaded +' synchronized successfully!' + '<span class="red-text"> ('+resultInfo.numOfFailed+' failed)</span>', 'success');
                        } else {
                            showToast('Your media library is synchronized successfully!', 'success');
                        }

                    }, 1000)
                }
            }
        }

        function syncPublitioMedia(media_list) {
            if (media_list !== undefined && media_list !== null && media_list.length > 0) {
                if (confirm('Are you sure you want to synchronize all media files with Publitio?')) {
                    const resultInfo = {
                        numOfUploaded:0,
                        numOfFailed : 0,
                        numOfMedia : media_list.map((item) => item.length).reduce((a,b) => a+b,0)
                    };
                    $('#publitio-popup').show();
                    recursiveMediaLoading(media_list,0,resultInfo);
                }
            } else {
                showToast('Your media library is already synchronized!', 'error');
            }
        }

        function deletePublitioMediaFiles() {
            $('#media-delete').on('click', function (event) {
                let media_list = null;
                jQuery.get(ajaxurl, {
                    action: 'pwpo_get_media_list_for_delete'
                }, function (response) {
                    media_list = response.media;
                    deletePublitioMedia(media_list);
                })
            })
        }

        function deletePublitioMedia(media_list) {
            if (media_list !== undefined && media_list !== null && media_list.length > 0) {
                if (confirm('Are you sure you want to delete all offloaded Media locally and replace it with Publitio Media URLs? Plugin will delete files from local storage - but if you choose to deactivate Publitio Offloading plugin in the future, your site posts/pages will result in broken media links (as they are no longer present locally). Use with caution & at your own risk as there is no going back once you use this options!')) {
                    let numOfDeleted = 0;
                    let numOfDeletedFailed = 0;
                    $('#publitio-popup').show();
                    let numOfMediaForDelete = media_list.length;
                    media_list.forEach((media) => {
                        jQuery.post(ajaxurl, {
                            async: false,
                            action: 'pwpo_delete_media_file',
                            attach_id: media.ID,
                            wpnonce: $('#_wpnonce').val()
                        }, function (responseMedia) {
                            if (responseMedia.deleted === true) {
                                numOfDeleted++;
                            } else {
                                numOfDeletedFailed++;
                            }

                            let result = (((numOfDeleted + numOfDeletedFailed) / numOfMediaForDelete) * 100).toFixed(1);
                            $("#publitioBar").width(result + "%");
                            $("#loadPublitioNumber").empty();
                            let resDeleteFailed = "";
                            if(numOfDeletedFailed !== 0 ) {
                                resDeleteFailed = '<span class="red-text"> ('+numOfDeletedFailed+' failed)</span>';
                            }
                            $("#loadPublitioNumber").html(numOfDeleted + " of "+ numOfMediaForDelete + resDeleteFailed  + " / " + result + "% completed");
                            if (numOfDeleted + numOfDeletedFailed === numOfMediaForDelete) {
                                if(timers && timers['deleteTimeout']) {
                                    clearTimeout(timers['deleteTimeout']);
                                    timers['deleteTimeout'] = null;
                                }
                                timers['deleteTimeout'] = setTimeout(function () {
                                    $('#publitio-popup').hide();
                                    $("#loadPublitioNumber").html(0);
                                    $("#publitioBar").width("0%");
                                    if(numOfDeletedFailed !== 0) {
                                        showToast(numOfDeleted +' deleted successfully!' + '<span class="red-text"> ('+numOfDeletedFailed+' failed)</span>', 'success');
                                    } else {
                                        showToast('All media files are deleted successfully!', 'success');
                                    }

                                }, 1000)
                            }
                        })
                    })
                }
            } else {
                showToast('All media files are already deleted!', 'error');
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
