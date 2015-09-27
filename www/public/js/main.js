if (typeof asticode === 'undefined') {
    asticode = {};
}

if (typeof asticode.deployment === 'undefined') {
    asticode.deployment = {};
}

asticode.deployment.init = function (oJson) {
    asticode.deployment.refresh.init();
    asticode.deployment.update(oJson);
};

asticode.deployment.update = function (oJson) {
    // Initialize.
    var jContent = $('#content');

    oJson.forEach(function (oItem) {
        // Initialize.
        var jPanel, jPanelHeader, jPanelBody, sHeader, sWrapperSelector = '#project-' + oItem['build_id'], jWrapper = $(sWrapperSelector),
            jPanelHeaderTitle;

        // New project.
        if (jWrapper.length === 0) {
            jPanel = $('<div class="panel panel-default" id="project-' + oItem['build_id'] + '"></div>').appendTo(jContent);
            jPanelHeader = $('<div class="panel-heading"></div>').appendTo(jPanel);
            jPanelHeaderTable = $('<table style="width:100%"><tbody></tbody></table>').appendTo(jPanelHeader);
            jPanelHeaderTr = $('<tr></tr>').appendTo(jPanelHeaderTable);
            jPanelHeaderTitle = $('<td class="title"></td>').appendTo(jPanelHeaderTr);
            jPanelHeaderButtonWrapper = $('<td style="text-align: right"></td>').appendTo(jPanelHeaderTr);
            jButtonLastLog = $('<button type="button" class="btn btn-info" data-project="' + oItem['build_id'] + '" style="margin-right:7px">Last Log</button>').appendTo(jPanelHeaderButtonWrapper);
            /*jButtonLastLog.click(function () {
                asticode.toolbox.loading.show();
                $.ajax({
                    type: 'GET',
                    url: '/ajax/last_log.php?project=' + $(this).attr('data-project'),
                    dataType: 'json',
                    success: function (data) {
                        asticode.toolbox.loading.hide(0);
                        var sContent = '<table class="table table-bordered table-striped" style="margin:0"><thead><tr><th>Date</th><th>Message</th></tr></thead><tbody>';
                        data.forEach(function (aItem) {
                            if (aItem['type'] === 'error') {
                                sContent += '<tr class="danger">';
                            } else {
                                sContent += '<tr>';
                            }
                            sContent += '<td>' + aItem['created_at'] + '</td><td>' + aItem['message'] + '</td>';
                            sContent += '</tr>';
                        });
                        sContent += '</tbody></table>';
                        asticode.toolbox.modaling.open(sContent, {
                            "sMaxWidth": "600px"
                        });
                    },
                    error: function () {
                        asticode.toolbox.loading.hide();
                        asticode.toolbox.notifying.notify('There was a problem while retrieving the logs', 'danger');
                    }
                });
            });*/
            jButtonRun = $('<button type="button" class="btn btn-success" data-project="' + oItem['build_id'] + '">Run</button>').appendTo(jPanelHeaderButtonWrapper);
            /*jButtonRun.click(function () {
                asticode.toolbox.loading.show();
                $.ajax({
                    type: 'GET',
                    url: '/ajax/run.php?project=' + $(this).attr('data-project'),
                    dataType: 'json',
                    success: function () {
                        asticode.toolbox.loading.hide();
                        asticode.toolbox.notifying.notify('The build has been added to the queues');
                    },
                    error: function () {
                        asticode.toolbox.loading.hide();
                        asticode.toolbox.notifying.notify('There was a problem while adding the build to the queues', 'danger');
                    }
                });
            });*/
            jPanelBody = $('<div class="panel-body"></div>').appendTo(jPanel);
        } else {
            jPanelHeader = jWrapper.find('.panel-heading');
            jPanelHeaderTitle = jPanelHeader.find('.title');
            jPanelBody = jWrapper.find('.panel-body');
        }

        // Update title.
        sHeader = oItem['project'] + ' (' + oItem['project'] + ')';
        if (oItem['pending_queues'] !== null) {
            sHeader += ': <span style="text-decoration: underline">' + oItem['pending_queues'] + ' pending queue(s)</span>';
        }
        jPanelHeaderTitle.html(sHeader);

        // Build has ended.
        if (oItem['ended_at_diff'] !== null) {
            // Success.
            if (oItem['error'] === null) {
                var jAlert = $('<div class="alert alert-success" style="margin-bottom:0px"></div>');
                var jAlertContent = $('<div class="alert-content">[' + asticode.toolbox.datetime.formatDiff(oItem['ended_at_diff']) + '] ' + oItem['message'] + '</div>').appendTo(jAlert);
                jPanelBody.html(jAlert);
            } else {
                var jAlert = $('<div class="alert alert-danger" style="margin-bottom:0px"></div>');
                var jAlertContent = $('<div class="alert-content">[' + asticode.toolbox.datetime.formatDiff(oItem['ended_at_diff']) + '] ' + oItem['error'] + '</div>').appendTo(jAlert);
                jPanelBody.html(jAlert);
            }
        } else {
            if (jPanelBody.find('.alert-info').length === 0) {
                var jAlert = $('<div class="alert alert-info" style="margin-bottom:0px"></div>');
                jPanelBody.html(jAlert);
                var jAlertMessageTable = $('<table style="width:100%"><tbody></tbody></table>').appendTo(jAlert);
                var jAlertMessageTr = $('<tr style="width:100%"></tr>').appendTo(jAlertMessageTable);
                var jAlertMessageIcons = $('<td style="padding-right:7px;width:50px;"></td>').appendTo(jAlertMessageTr);
                asticode.toolbox.loading.createIcons(jAlertMessageIcons, {
                    "oIcons": {
                        "bAnimate": true,
                        "oWrapper": {
                            "sWidth": "50px"
                        },
                        "sColor": "#31b0d5",
                        "iCount": 4
                    },
                    "oAnimation": {
                        "sFontSize": "10px"
                    }
                });
                var jAlertMessageContent = $('<td class="alert-message"></td>').appendTo(jAlertMessageTr);
                var jAlertMessageButtons = $('<td style="text-align: right"></td>').appendTo(jAlertMessageTr);
                jButtonCancel = $('<button type="button" class="btn btn-danger" data-project="' + oItem['project_id'] + '">Cancel</button>').appendTo(jAlertMessageButtons);
                jButtonCancel.click(function () {
                    asticode.toolbox.loading.show();
                    $.ajax({
                        type: 'GET',
                        url: '/ajax/cancel.php?project=' + $(this).attr('data-project'),
                        dataType: 'json',
                        success: function (data) {
                            asticode.toolbox.loading.hide();
                            asticode.toolbox.notifying.notify('The build has been cancelled successfully');
                        },
                        error: function () {
                            asticode.toolbox.loading.hide();
                            asticode.toolbox.notifying.notify('There was a problem while cancelling the build', 'danger');
                        }
                    });
                });
                var jAlertProgress = $('<div class="progress alert-progress" style="margin-bottom:0;margin-top:10px"></div>').appendTo(jAlert);
            } else {
                var jAlert = jPanelBody.find('.alert');
                var jAlertMessageContent = jAlert.find('.alert-message');
                var jAlertProgress = jAlert.find('.alert-progress');
            }
            var iPercent = 0;
            var aMatches = asticode.toolbox.preg_match('/^[0-9]+/', oItem['last_command']);
            if (aMatches.length > 0) {
                iPercent = parseInt(aMatches[0] / oItem['nb_of_commands'] * 100);
            }
            var sMessage = asticode.toolbox.preg_replace('/^[0-9]+./', '', oItem['last_command']);
            jAlertMessageContent.html('<td>[' + asticode.toolbox.datetime.formatDiff(oItem['last_command_created_at_diff']) + '] ' + sMessage + '</td>');
            jAlertProgress.html('<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: ' + iPercent + '%"><span class="sr-only">' + iPercent + '% Complete</span></div>');
        }
    });
};

asticode.deployment.refresh = {
    action: function () {
        $.ajax({
            type: 'GET',
            url: '/ajax/heartbeat.php',
            dataType: 'json',
            success: function (oJson) {
                asticode.deployment.update(oJson);
            }
        });
    },
    delay: 5000,
    init: function () {
        //setInterval(asticode.deployment.refresh.action, asticode.deployment.refresh.delay);
    },
    interval: null
};