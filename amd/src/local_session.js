/**
 * Javascript containing function of the admin session
 */

define([
    'jquery',
    'format_edadmin/format_edadmin',
    'local_mentor_core/mentor',
    'jqueryui',
    'local_mentor_core/datatables',
    'local_mentor_core/datatables-buttons',
    'local_mentor_core/jszip',
    'local_mentor_core/buttons.html5'
], function ($, format_edadmin, mentor) {
    var local_session = {
        /**
         * Init JS
         */
        init: function (params) {

            this.params = params;

            //Initial and create admin session datatable
            this.createAdminTable(params.entityid);

            this.initFilters();
            this.initEventsFilter();

            $('#move-session-popup').hide();

        },
        /**
         * Initialise the session form
         *
         * @param params An array of parameter set by the PHP
         */
        initform: function (params) {
            var that = this;
            this.params = params;

            // Init change status confirmation popup.
            mentor.dialog("#session_status_confirm", {
                resizable: true,
                autoOpen: false,
                width: 550,
                title: M.util.get_string('reportsessionmessage', 'local_session'),
                buttons: [
                    {
                        text: "Confirmer",
                        class: "btn btn-primary",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }, {
                        text: "Annuler",
                        class: "btn btn-secondary",
                        click: function () {
                            var previous = $("#session_status_confirm").data('previous');
                            $('#id_status').val(previous);
                            $(this).dialog("close");
                        }
                    }
                ]
            });

            $('#id_opento_other_entities').on('click', function () {
                $('#other_session_content').show();
            });

            $('#id_opento_all').on('click', function () {
                $('#other_session_content').hide();
            });

            $('#id_opento_current_entity').on('click', function () {
                $('#other_session_content').hide();
            });

            $('#id_opento_not_visible').on('click', function () {
                $('#other_session_content').hide();
            });

            if ($('#id_opento_other_entities:checked').length > 0) {
                $('#other_session_content').show();
            }

            $('#id_termsregistration').change(function () {
                if ($('#id_termsregistration').val() === 'autre') {
                    $('#termsregistrationdetail-bloc').show();
                } else {
                    $('#termsregistrationdetail-bloc').hide();
                }
            });

            var previousstatus = '';

            $('#id_status').on('focus', function () {
                // Store the current value on focus and on change
                previousstatus = this.value;
            }).change(function () {
                if ($('#id_status option:selected').val() == 'reported') {
                    $("#session_status_confirm").data('previous', previousstatus).dialog('open');
                }
            });


            $('#infolifecycle').on('click', function () {
                that.sessionWorkflowPopup();
            });

            $('#fgroup_id_sessionpermanent input:radio').on('change', function () {
                $(this).val() === '1' ?
                    $('#fitem_id_maxparticipants').addClass('hidden') :
                    $('#fitem_id_maxparticipants').removeClass('hidden')
                ;
            });
        },

        /**
         * Display the session workflow popup
         */
        sessionWorkflowPopup: function () {

            mentor.dialog('#workflow-session', {
                    width: 920,
                    height: 600,
                    title: M.util.get_string('lifecycle', 'local_session')
                }
            );
        },

        /**
         * Init and create training admin table
         *
         * @param {int} entityid
         */
        createAdminTable: function (entityid) {

            function newexportaction(e, dt, button, config) {
                var self = this;
                var oldStart = dt.settings()[0]._iDisplayStart;
                dt.one('preXhr', function (e, s, data) {
                    // Just this once, load all data from the server...
                    data.start = 0;
                    data.length = 2147483647;
                    dt.one('preDraw', function (e, settings) {
                        // Call the original action function
                        if (button[0].className.indexOf('buttons-copy') >= 0) {
                            $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button, config);
                        } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                            $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config) :
                                $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
                        } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                            $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                                $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
                        } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                            $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config) :
                                $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button, config);
                        } else if (button[0].className.indexOf('buttons-print') >= 0) {
                            $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
                        }
                        dt.one('preXhr', function (e, s, data) {
                            // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                            // Set the property to what it was before exporting.
                            settings._iDisplayStart = oldStart;
                            data.start = oldStart;
                        });
                        // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
                        setTimeout(dt.ajax.reload, 0);
                        // Prevent rendering of the full data to the DOM
                        return false;
                    });
                });
                // Requery the server with the new one-time export settings
                dt.ajax.reload();
            };

            // Admin training table creation
            M.table = $('#session-table').DataTable({
                "columnDefs": [{
                    "targets": 'no-sort',
                    "orderable": false,
                }],
                processing: true,
                serverSide: true,//For use Ajax
                ordering: true,
                dom: 'Blfrtip',
                buttons: [
                    {
                        extend: 'csvHtml5',
                        charset: 'utf-8',
                        fieldBoundary: '',
                        fieldSeparator: ';',
                        bom: true,
                        exportOptions: {
                            format: {
                                body: function (data, row, column, node) {
                                    // Replace br by \r\n.
                                    if (column === 0) {
                                        return decodeURIComponent(data.replace(/<br\s*\/?>/ig, "."));
                                    }
                                    return decodeURIComponent(data.replace(/<.*?>/ig, ""));
                                }
                            }
                        },
                        stripNewlines: false,
                        text: 'Export CSV',
                        className: 'btn btn-secondary csvexport',
                        action: newexportaction
                    }
                ],
                ajax: {
                    //Call edadmin course list
                    url: M.cfg.wwwroot + '/local/session/ajax/ajax.php',
                    data: function (d) {// GET HTTP data setting
                        d.controller = 'session';
                        d.action = 'get_sessions_by_entity';
                        d.format = 'json';
                        d.entityid = entityid;
                    }
                },
                oLanguage: {
                    sUrl: M.cfg.wwwroot + '/local/mentor_core/datatables/lang/' + M.util.get_string('langfile', 'local_session') + ".json"
                },
                columns: [
                    {
                        data: function (data, type, row, meta) {
                            if (type === 'display') {
                                return '<a href="' + data.link + '">' + data.shortname + '</a>';
                            }
                            return data.shortname;
                        }
                    },
                    {data: 'shortname'},
                    {
                        data: function (data, type, row, meta) {
                            return '<span class="session-status" data-status="' + data.statusshortname + '">' + data.status + '</span>';
                        }
                    },
                    {
                        data: function (data, type, row, meta) {
                            if (type === 'display') {
                                return local_session.getDateTimeFromTimestamp(data.timecreated);
                            }
                            return data.timecreated;
                        }
                    },
                    {data: 'nbparticipant'},
                    {
                        data: function (data, type, row, meta) {
                            if (type === 'display') {
                                return data.shared ? M.util.get_string('yes', 'moodle') : M.util.get_string('no', 'moodle');
                            }
                            return data.timecreated;
                        }
                    },
                    {
                        className: 'action-admin-session',
                        data: function (data, type, row, meta) {
                            if (type === 'display') {
                                return local_session.create_action_button(data);
                            }
                            return '';
                        }
                    }
                ]
            });
        },
        /**
         * Initialise filters
         */
        initFilters: function () {

            $('#session-list-status-filters-table').each(function (index, element) {
                $(element).val("").trigger("change");
            });

            $('#datapicker-from-session-filters-table').datepicker({dateFormat: 'dd/mm/yy'});
            $('#datapicker-to-session-filters-table').datepicker({dateFormat: 'dd/mm/yy'});

            $('.datepicker-session').each(function (index, element) {
                $.datepicker._clearDate($('#' + element.id));
            })
        },
        /**
         * Initialise event filters
         */
        initEventsFilter: function () {
            var that = this;

            $('#session-submit-form').on('click', function () {
                var filterdata = $('#session-filters-table').serializeArray()

                var params = {
                    url: M.cfg.wwwroot + '/local/session/ajax/ajax.php',
                    controller: 'session',
                    action: 'get_sessions_by_entity',
                    status: filterdata[0].value,
                    format: 'json',
                    entityid: that.params.entityid,
                    callback: function (response) {

                        response = JSON.parse(response);

                        // Check response
                        if (response.success) {
                            M.table.clear();
                            M.table.rows.add(response.message).draw();
                        } else {
                            // Duplication fail
                            format_edadmin.error_modal(response.message);
                        }
                    }
                };

                if (filterdata[1].value) {
                    params.dateto = that.toTimeStamp(filterdata[1].value) / 1000;
                }
                if (filterdata[2].value) {
                    params.datefrom = that.toTimeStamp(filterdata[2].value) / 1000;
                }

                format_edadmin.ajax_call(params);
            })

            $('#session-init-form').on('click', function () {
                local_session.initFilters();
            });
        },
        /**
         * Create action buttons
         *
         * @param data
         * @returns {string}
         */
        create_action_button: function (data) {
            var render = '';

            $.each(data.actions, function (index, element) {

                    switch (index) {
                        case 'sessionSheet' :
                            render += '<div class="btn btn-sessionsheet"><a href="' + element.url + '"><img class="cursor-image-session-admin-session" src="' + M.util.image_url(index.toLowerCase(), 'local_session') + '" alt="' + element.tooltip + '" title="' + element.tooltip + '"></a></div>';
                            break;

                        case 'moveSession' :
                            render += '<div class="btn btn-movesession"><img src="' + M.util.image_url(index.toLowerCase(), 'local_session') + '" alt="' + element.tooltip + '" title="' + element.tooltip + '" onclick="local_session.moveSession(' + data.id + ')" ></div>';
                            break;

                        case 'deleteSession' :
                            render += '<div class="btn btn-deletesession"><img src="' + M.util.image_url(index.toLowerCase(), 'local_session') + '" alt="' + element.tooltip + '" title="' + element.tooltip + '" onclick="local_session.deleteSession(' + data.id + ')" ></div>';
                            break;

                        case 'manageUser' :
                            render += '<div class="btn btn-manageuser"><a href="' + element.url + '"><img class="cursor-image-session-admin-session" src="' + M.util.image_url(index.toLowerCase(), 'local_session') + '" alt="' + element.tooltip + '" title="' + element.tooltip + '"></a></div>';
                            break;

                        case 'importUsers' :
                            render += '<div class="btn btn-importusers"><a href="' + element.url + '"><img class="cursor-image-session-admin-session" src="' + M.util.image_url('importusers', 'local_session') + '" alt="' + element.tooltip + '" title="' + element.tooltip + '"></a></div>';
                            break;

                        case 'importSIRH' :
                            render += '<div class="btn btn-importsirh"><a href="' + element.url + '"><img class="cursor-image-session-admin-session" src="' + M.util.image_url('handshake', 'local_session') + '" alt="' + element.tooltip + '" title="' + element.tooltip + '"></a></div>';
                            break;

                        case 'cancelSession':
                            // Escape the shortname string.
                            data.shortname = escape(data.shortname);

                            render += '<div class="btn btn-cancelsession"><img class="cursor-image-session-admin-session" src="' + M.util.image_url(index.toLowerCase(),
                                'local_session') + '" alt="' + element.tooltip + '" title="' + element.tooltip + '" onclick="local_session.cancelSession(' + data.id + ',' + '\'' + data.shortname + '\')"></div>';

                            break;
                        default :
                            render += '<div><a href="' + element + '"><img class="cursor-image-session-admin-session" src="' + M.util.image_url(index.toLowerCase(), 'local_session') + '" alt="' + index + '"></a></div>';
                            break;
                    }

                }
            );

            return render;
        },
        /**
         * Ajax call for moving a session into another entity
         *
         * @param {int} sessionid
         */
        moveSession: function (sessionid) {

            mentor.dialog('#move-session-popup', {
                width: 710,
                title: M.util.get_string('movesessiondialogtitle', 'local_session'),
                create: function (event, ui) {
                    // Select default training entity
                    sessiondata = M.table.data().toArray().find(function (x) {
                        return parseInt(x.id) === sessionid
                    });
                    $(this).find('#entity').val(sessiondata.entityid);
                },
                buttons: [
                    {
                        // Remove button
                        text: M.util.get_string('move', 'local_session'),
                        id: 'confirm-move-session',
                        class: "btn btn-primary",
                        click: function (e) {
                            var that = this;

                            // Disable the confirm button.
                            $(e.target).attr("disabled", "disabled");

                            var destentity = $('.ui-dialog #move-session-popup #entity').val();

                            format_edadmin.ajax_call({
                                url: M.cfg.wwwroot + '/local/session/ajax/ajax.php',
                                controller: 'session',
                                action: 'move_session',
                                format: 'json',
                                sessionid: sessionid,
                                destinationentity: destentity,
                                callback: function (response) {

                                    response = JSON.parse(response);

                                    if (response.success) {
                                        // Reload the table on success.
                                        M.table.ajax.reload();
                                    } else {
                                        console.log(response.message);
                                    }

                                    // Just close the modal.
                                    $(that).dialog("destroy");
                                }
                            });
                        }
                    },
                    {
                        // Cancel button
                        text: M.util.get_string('cancel', 'format_edadmin'),
                        class: "btn btn-secondary",
                        click: function () {
                            // Just close the modal.
                            $(this).dialog("destroy");
                        }
                    }
                ]
            });

        },
        /**
         * Ajax call for deleting a session
         *
         * @param {int} sessionid
         */
        deleteSession: function (sessionid) {

            mentor.dialog('#delete-session-popup', {
                width: 710,
                height: 250,
                title: M.util.get_string('deletesessiondialogtitle', 'local_session'),
                buttons: [
                    {
                        // Remove button
                        text: M.util.get_string('confirm', 'moodle'),
                        id: 'confirm-delete-session',
                        class: "btn btn-primary",
                        click: function (e) {
                            var that = this;

                            // Disable the confirm button.
                            $(e.target).attr("disabled", "disabled");

                            // Delete the session.
                            format_edadmin.ajax_call({
                                url: M.cfg.wwwroot + '/local/session/ajax/ajax.php',
                                controller: 'session',
                                action: 'delete_session',
                                format: 'json',
                                sessionid: sessionid,
                                callback: function (response) {

                                    response = JSON.parse(response);

                                    // Check response
                                    if (response.success) {
                                        M.table.ajax.reload();
                                        $(that).dialog("destroy");
                                    } else {
                                        // Remove fail
                                        format_edadmin.error_modal(response.message);
                                    }
                                }
                            });
                        }
                    },
                    {
                        // Cancel button
                        text: M.util.get_string('cancel', 'format_edadmin'),
                        class: "btn btn-secondary",
                        click: function () {
                            // Just close the modal.
                            $(this).dialog("destroy");
                        }
                    }
                ]
            });

        },
        /**
         * Cancel a session
         *
         * @param sessionid
         * @param shortname
         */
        cancelSession: function (sessionid, shortname) {
            // Unescape the shortname string.
            shortname = unescape(shortname);

            mentor.dialog('<div class="text-center"><span>' + M.util.get_string('cancelsessiondialogcontent', 'local_session', shortname) + '</span></div>', {
                height: 240,
                width: 600,
                title: M.util.get_string('cancelsessiondialogtitle', 'local_session'),
                buttons: [
                    {
                        // Confirm button
                        text: M.util.get_string('confirm', 'format_edadmin'),
                        class: "btn btn-primary",
                        id: 'confirm-cancel-session',
                        click: function (e) {
                            var that = this;
                            $(e.target).attr("disabled", "disabled");
                            format_edadmin.ajax_call({
                                url: M.cfg.wwwroot + '/local/session/ajax/ajax.php',
                                controller: 'session',
                                action: 'cancel_session',
                                format: 'json',
                                sessionid: sessionid,
                                callback: function (response) {
                                    M.table.ajax.reload(null, false);
                                    $(that).dialog("destroy");
                                }
                            });
                        }
                    },
                    {
                        // Cancel button
                        text: M.util.get_string('cancel', 'format_edadmin'),
                        class: 'btn btn-secondary',
                        click: function () {//Just close the modal
                            $(this).dialog("destroy");
                        }
                    }
                ],
                close: function (event, ui) {
                    $(this).dialog("destroy");
                },
            });
        }
        ,
        getDateTimeFromTimestamp: function (unixTimeStamp) {
            var dateobject = new Date(unixTimeStamp * 1000);
            return ('0' + dateobject.getDate()).slice(-2) + '/' + ('0' + (dateobject.getMonth() + 1)).slice(-2) + '/' + dateobject.getFullYear();
            //+ ' ' + ('0' + dateobject.getHours()).slice(-2) + ':' + ('0' + dateobject.getMinutes()).slice(-2);
        }
        ,
        toTimeStamp: function (str) {
            return new Date(str.replace(/^(\d{2})(\/)(\d{2}\/)(\d{4})$/,
                '$4$2$3$1')).getTime();
        }
    }

    //add object to window to be called outside require
    window.local_session = local_session;
    return local_session;
});
