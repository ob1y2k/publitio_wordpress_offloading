(function ($) {
    'use strict';

    const STATUSES = {
        ERROR_UNAUTHORIZED: 401,
        ERROR: 500,
        SUCCESS: 200
    }

    $(function () {
        getPlayers()
        updateSettingsButtonClick()
        updateDefaultPlayerChange()
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
                    showBlock($('#error-block'), 'Wrong credentials');
                    clearPlayer();
                } else if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#success-block'), 'Great!');
                    addPlayers(response.players)
                } else {
                    showBlock($('#error-block'), 'Something went wrong.');
                }
            });
        });
    }

    function getPlayers() {
        jQuery.get(ajaxurl, { action: 'get_offloading_players' }, function(response) {
            addPlayers(response.players, response.default_player_id)
        })
    }

    function addPlayers(players, defaultPlayerId = '') {
        clearPlayer();
        if(players != undefined && players != null) {
            players.forEach((player) => {
                $('<option value="' + player.id + '">' + assembleOffloadingOption(player) + '</option>').appendTo($('#default-offloading-player'));
            })
            setSelectedPffloadingPlayer(defaultPlayerId);
        }
    }

    function setSelectedPffloadingPlayer(id) {
        $('#default-offloading-player > option[value="' + id +'"]').attr("selected", "selected");
    }

    function assembleOffloadingOption(player) {
        let adtag = player.adtag_id ? ', adtag: ' + player.adtag_id : '';
        let autoplay = getAutoplayOffloadingOption(player.auto_play)
        return player.id + ' (skin: ' + player.skin + adtag + ', autoplay: ' + autoplay + ')';
    }

    function getAutoplayOffloadingOption(autoPlay) {
        if (autoPlay === 0) {
            return 'off';
        } else if (autoPlay === 1) {
            return 'on';
        }
        return 'mouseover';
    }

    function clearPlayer() {
        $('#default-offloading-player').empty();
        $('<option selected hidden disabled>None</option>').appendTo($('#default-offloading-player'));
    }

    function clearBlocks() {
        $('#error-block').empty();
        $('#success-block').empty();
        $('#player-success-block').empty();
        $('#player-error-block').empty();
    }

    function showBlock(elem, content) {
        $(elem).text(content)
        setTimeout(function() {
            clearBlocks()
        }, 3000)
    }

    function updateDefaultPlayerChange() {
        $('#default-offloading-player').bind('change', function (event) {
            jQuery.post(ajaxurl, {
                action: 'update_default_offloading_player',
                player_id: event.target.value
            }, function (response) {
                if (response.status === STATUSES.ERROR_UNAUTHORIZED) {
                    showBlock($('#player-error-block'), 'Wrong credentials');
                } else if (response.status === STATUSES.SUCCESS) {
                    showBlock($('#player-success-block'), 'Great!');
                } else {
                    showBlock($('#player-error-block'), 'Something went wrong.');
                }
            });
        });
    }

})(jQuery);



