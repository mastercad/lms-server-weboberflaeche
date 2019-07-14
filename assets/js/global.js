import $, { get, each, post } from 'jquery';
import { parse } from 'url';

var Base64 = require('js-base64').Base64;

$(document).ready(function() {
    init_select_checkboxes();

    $('.new-client-entry').unbind('click').click(function() {
        load_client_edit_modal(0);
    });

    // new entry
    $('.new-mapping-entry').unbind('click').click(function() {
        load_mapping_edit_modal(0);
    });

    $('.client-detail').unbind('click').click(function() {
        load_client_detail_modal($(this).data('id'));
    });

    $('[data-toggle="popover"]').popover();

    init_tooltips();

    $('.sync_mappings').unbind('click').click(function() {
        sync_mappings();
    });

    let current_selected_client = parse_current_selected_client();

    init_file_select();

    load_clients_list(current_selected_client);

    if (undefined !== current_selected_client) {
        load_current_client_mappings(current_selected_client);
    }
});

function init_tooltips() {
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
}

function init_mapping_options() {
    $('.mapping-detail').unbind('click').click(function() {
        load_mapping_detail_modal($(this).data('id'));
    });

    $('.mapping-edit').unbind('click').click(function() {
        load_mapping_edit_modal($(this).data('id'));
    });
}

// init checkboxes for select rows
function init_select_checkboxes() {
    $('input[type="checkbox"].checkbox-select-all').change(function () {
        $('input[type="checkbox"]').not(this).prop('checked', this.checked);
    });

    $('input[type="checkbox"]').change(function(){
        $('input[type="checkbox"].checkbox-select-all').prop('checked', $('input[type="checkbox"]').not(':checked').length == 0);
    });
}

/**
 * 
 * Change current visible Client Name in Client DropDown.
 * 
 * @param object client 
 */
function change_selected_client_select(client) {
    $('.nav-link.dropdown-toggle').html($(client).html());
}

/**
 * initialize client select dropdown 
 *  => set onclick listener
 *  => set edit listener
 *  => set current selected client name if known in dropdown
 */
function init_client_select() {
    let selected_client_id = parse_current_selected_client();
    $('#clients_select_container a').each(function() {
        if (selected_client_id == $(this).data('id')) {
            change_selected_client_select($(this));
        }
        $(this).unbind('click').on('click', function() {
            change_selected_client_select($(this));
            load_current_client_mappings($(this).data('id'));
        });
    });

    $('#clients_select_container .edit-client-entry').unbind('click').click(function() {
        load_client_edit_modal($(this).data('id'));
    });
}

// load edit dialog for mapping
function load_mapping_edit_modal(id) {
    let client_id = parse_current_selected_client();
    get("/mapping/edit/"+id, {client: client_id}, function(data) {
        init_modal(data, 'mapping', true, function() {
            load_current_client_mappings(client_id);
        });
    });
}

function load_mapping_detail_modal(id) {
    get("/mapping/show/"+id, function(data) {
        init_modal(data, 'mapping');
    });
}

/**
 * Load current known clients, current selected client means the current active client in the view
 *
 * @param {number} current_selected_client
 */
function load_clients_list(current_selected_client) {
    get("/clients/online?selected="+current_selected_client+"&response_type=json", function(clients) {
        let clients_container = $('#clients_select_container');
        clients_container.html("");

        each(clients, function(index, client) {
            clients_container.append(create_online_client_select_entry(client));
        });

        init_client_select();
        init_tooltips();
    });
}

function parse_current_selected_client() {
    return $(location).attr('hash').replace(/^#/, "");
}

/**
 * Load mappings for given client.
 * 
 * @param int current_selected_client 
 */
function load_current_client_mappings(current_selected_client) {
    get("/mapping?client_id="+current_selected_client+"&response_type=json", function(response) {
        let clients_container = $('#mapping_content');
        clients_container.html(response);

        init_mapping_options();
        init_tooltips();
    });
}

/**
 * Load Edit View for given client id.
 *
 * @param {number} id
 */
function load_client_edit_modal(id) {
    get("/client/edit/"+id, function(data) {
        init_modal(data, 'client', true, function() {
            load_clients_list(parse_current_selected_client());
        });
    });
}

/**
 * Load Detail View for given client id.
 *
 * @param {number} id
 */
function load_client_detail_modal(id) {
    get("/client/show/"+id, function(data) {
        init_modal(data, 'client');
    });
}

/**
 * Create select string entry for online client dropdown.
 *
 * @param {Array} client
 *
 * @returns {string}
 */
function create_online_client_select_entry(client) {
    var clientListEntry = $('<li class="station-drop-down-entry" >' +
        '   <div class="spinner-grow text-primary"></div>'+
        '   <i class="ml-2 fa fa-edit edit-client-entry d-none" data-id="'+client['id']+'"></i>' +
        '</li>');

    $.get("/client/detail/"+Base64.encode(client['IP']+':'+client['PORT']), 
        function(result) {
            if (500 != result.code) {
                clientListEntry.replaceWith(create_client_select_entry(result.content));
                init_client_select();
            } else {
                clientListEntry.remove();
            }
        }
    );

    return clientListEntry;
}

/**
 * Creates static select string entry for given client, without online check.
 * 
 * @param {Array} client 
 */
function create_client_select_entry(client) {
    return $('<li class="station-drop-down-entry" >' +
        '   <a data-id="'+client['id']+'" href="#'+client['id']+'">'+client['name']+'</a>' +
        '   <i class="ml-2 fa fa-edit edit-client-entry" data-id="'+client['id']+'"></i>' +
        '</li>');
}

function init_modal(modal_data, controller, is_editable = false, callback) {
    let modal = $(modal_data);
    modal.on('hidden.bs.modal', function() {modal.remove()}).modal();

    if ('mapping' == controller) {
        modal.on('shown.bs.modal', function() {
            init_file_select();
        });
    }
    if (is_editable) {
        prepare_modal_for_edit(modal, controller, callback);
    }
}

function prepare_modal_for_edit(modal, controller, callback) {
    modal.find('.btn-primary').on('click', function() {
        let form = modal.find('form');
        // Form valid?
        if($(form)[0].checkValidity()) {
            post('/'+controller+'/save?response_type=json', $(form).serialize(), function(response) {
                handle_save_result(modal, controller, response, callback);
            });
        } else {
            $(form)[0].reportValidity();
        }
    });
}

function handle_save_result(modal, controller, response, callback) {
    modal.modal('hide');
    if (true === response.success) {
        if (undefined !== callback) {
            callback();
        }
    } else if (response.content) {
        init_modal(response.content, controller, true, callback);
    }
}

let progressTimeOuts = [];

/**
 * Einsprung für das Syncen des Mappings zwischen den Clients
 */
function sync_mappings() {
    let mappings = [];
    let clients = [];

    let selectedMappings = $('#mapping_content .checkbox-select-row:checked').each(function() {
        let mapping = collect_mapping_data_for_selected_row($(this).data('id'));

        if (mapping) {
            mappings.push(mapping);
        }
    });

    if (0 >= mappings.length) {
        alert("Keine Mappings ausgewählt oder keine Änderungen vorhanden!");
        return false;
    }

    get('/client/select-dialog', function(response) {
        let modal = $(response);
        modal.on('hidden.bs.modal', function() {modal.remove()}).modal();

        modal.find('.btn-primary').on('click', function() {
            modal.find('input[type="checkbox"]:checked').each(function() {
                clients.push($(this).data('id'));
            });

            modal.modal('hide');

            if (0 >= clients.length) {
                alert("Kein Client ausgewählt!");
                return false;
            }

            for (let mappingPos = 0; mappingPos < mappings.length; ++mappingPos) {
                for (let clientPos = 0; clientPos < clients.length; ++clientPos) {
                    let current_mapping_id = mappings[mappingPos]['id'];
                    let client_id = clients[clientPos];

                    $.post('/transfer/handshake?response_type=json', {
                        'mapping': mappings[mappingPos], 
                        'client_id': clients[clientPos],
                        'current_client_id': parse_current_selected_client()
                    }, function(response) {

                        if (true === response.success) {
                            let token = response.token;
                            let progressBarContainer = $('#progress_bar_container_'+current_mapping_id);
                            let progressDiv = $('<div id="prograss_bar_container_'+token+'" class="row progress-bar-row">'+
                                '<div id="prograss_bar_'+token+'" class="progress-bar progress-bar-striped progress-bar-animated progress-bar-'+token+'" role="progressbar" style="width: 0%; height: 5px" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>'+
                            '</div>');
                            
                            progressTimeOuts[token] = setInterval(
                                function() {
                                    $.get('/transfer/progress/'+token+'/'+client_id+'?response_type=json', function(response) {
                                        if (undefined !== response.progress) {
                                            $(progressDiv).attr('width', response.progress.percentage+"%");
                                            $(progressDiv).attr('aria-valuenow', response.progress.current_size);
                                            $(progressDiv).html(response.progress.percentage);

                                            if (100 == parseInt(response.progress.percentage)) {
                                                clearInterval(progressTimeOuts[token]);
                                            }
                                        }
                                    });
                                }, 5000
                            );

                            progressBarContainer.append(progressDiv);

                            $.post('/transfer/send', {
                                'mapping': mappings[mappingPos], 
                                'client_id': clients[clientPos],
                                'response_type': 'json',
                                'token': token,
                                'current_client_id': parse_current_selected_client()
                            }, function (response) {
                                clearInterval(progressTimeOuts[token]);
                                if (200 == response.code) {
                                    $(progressDiv).fadeOut(2000, function() {
                                        progressDiv.remove();
                                    });
                                }
                            });
                        }
                    })
                }
            }
        });
    });
}

/**
 * Collect all changed and selected mappings.
 * 
 * @param {int} id 
 */
function collect_mapping_data_for_selected_row(id) {
    let mapping_row = $('#mapping_row_'+id);
    let rfid = $.trim(mapping_row.find('.input-rfid').text());
    let rfid_orig = Base64.decode(mapping_row.find('.input-rfid').data('orig'));
    let additional_information = $.trim(mapping_row.find('.input-additional-information').text());
    let additional_information_orig = Base64.decode(mapping_row.find('.input-additional-information').data('orig'));
    let lms_path = $.trim(mapping_row.find('.input-lms-path').text());
    let lms_path_orig = Base64.decode(mapping_row.find('.input-lms-path').data('orig'));
    let local_path = $.trim(mapping_row.find('.input-local-path').text());
    let local_path_orig = Base64.decode(mapping_row.find('.input-local-path').data('orig'));
    let client_id = $.trim(mapping_row.find('.input-client-id').text());
    let client_id_orig = Base64.decode(mapping_row.find('.input-client-id').data('orig'));
    let media_type_id = $.trim(mapping_row.find('.input-media-type-id').text());
    let media_type_id_orig = Base64.decode(mapping_row.find('.input-media-type-id').data('orig'));

    // media type orig and client id orig are currently changed because this should not changed
    if (1 
        || rfid != rfid_orig
        || additional_information != additional_information_orig
        || lms_path != lms_path_orig
        || local_path != local_path_orig
    ) {
        let mapping = {};
        mapping['id'] = id;
        mapping['rfid'] = rfid;
        mapping['additional_information'] = additional_information;
        mapping['lms_path'] = lms_path;
        mapping['local_path'] = local_path;
        mapping['client'] = client_id_orig;
        mapping['media_type'] = media_type_id_orig;

        return mapping
    }

    return false;
}


/*****
 * War gesplittet in directory_browser aber ich bekomme es nicht hin, die inhalte von da auch zu verwenden
 */
var current_absolut_path = '';
var current_folder_level = 0;
var file_select_modal = undefined;

function init_file_select() {
    $('.file-select-folder-btn').unbind('click').click(function() {
        open_folder_select($(this).data('path'), $(this));
    });
    $('.file-select-file-btn').unbind('click').click(function() {
        select_file($(this).data('path'));
    });

    $('#mapping_lms_path').unbind('click').click(function() {
        let object = $(this);
        let path = $(this).data('path');
        $(object).next('.folder-container').html('<div class="spinner-grow text-primary"></div>');

        $.get('/transfer/list/'+Base64.encode(path), function(response) {
            if (404 == response.code
                || 500 == response.code
            ) {
                // Fehler Modal!
                console.log("FEHLER!");
                return;
            }
            $(object).find('span.type-icon').removeClass('fa-folder').addClass('fa-folder-open');
            show_modal(generate_file_entries_from_json(response));
            init_file_select();
        });
    });
}

function select_file(path) {
    file_select_modal.on('hidden.bs.modal', function() {
        $('#mapping input#mapping_lms_path').val(path);
        file_select_modal.remove();
        // remove for persist old path for next selection
        current_absolut_path = '';
        current_folder_level = 0;
    });
    file_select_modal.modal('hide');
}

function open_folder_select(path, object) {
    let level = $(object).data('level');

    if ($(object).find('.type-icon').hasClass('fa-folder-open')) {
        $('.folder-container.level_'+level).html('');
        $('.type-icon.level_'+level).removeClass('fa-folder-open').addClass('fa-folder');
        current_folder_level = level;
        return
    } else if (level <= current_folder_level) {
        $('.folder-container.level_'+level).html('');
        $('.type-icon.level_'+level).removeClass('fa-folder-open').addClass('fa-folder');
    }

    current_folder_level = level;
    current_absolut_path = path;

    $(object).next('.folder-container').html('<div class="spinner-grow text-primary"></div>');

    $.get('/transfer/list/'+Base64.encode(path), function(response) {
        if (404 == response.code
            || 500 == response.code
        ) {
            // Fehler Modal!
            console.log("FEHLER!");
            return;
        }
        $(object).find('span.type-icon').removeClass('fa-folder').addClass('fa-folder-open');
        $(object).next('.folder-container').html(generate_file_entries_from_json(response));
        init_file_select();
    });
}

function generate_file_entries_from_json(json) {
    let content = '';
    let absolut_path = '';
    if ('/' !== current_absolut_path) {
        absolut_path = current_absolut_path;
    }
    $.each(json, function(key, value) {
        let type = key.substr(0, key.indexOf('-'));

        if ('f' == type ) {
            content += 
            '<div class="file-select-btn file-select-file-btn type_'+type+'" data-level="'+(current_folder_level+1)+'" data-path="'+absolut_path+'/'+value+'">'+
                '<span class="type-icon fa fa-file pr-1 level_'+(current_folder_level+1)+'"></span>'+
                value+
            '</div>';
        } else if ('d' == type) {
            content += 
            '<div class="file-select-btn file-select-folder-btn type_'+type+'" data-level="'+(current_folder_level+1)+'" data-path="'+absolut_path+'/'+value+'">'+
                '<span class="type-icon fa fa-folder pr-1 level_'+(current_folder_level+1)+'"></span>'+
                value+
            '</div>'+
            '<div style="margin-left: '+((current_folder_level+1) * 10)+'px" class="folder-container level_'+(current_folder_level+1)+' '+absolut_path+'/'+value+'">'+
            '</div>';
        };
    });

    return content;
}

function show_modal(content) {
    file_select_modal = $('<div class="modal fade" id="exampleModalLong" tabindex="-1" role="dialog" aria-hidden="true">'+
        '<div class="modal-dialog" role="document">'+
            '<div class="modal-content">'+
                '<div class="modal-header">'+
                    '<h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>'+
                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'+
                        '<span aria-hidden="true">&times;</span>'+
                    '</button>'+
                '</div>'+
                '<div class="modal-body">'+
                '</div>'+
                '<div class="modal-footer">'+
                    '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'+
                '</div>'+
            '</div>'+
        '</div>'+
    '</div>');

    $(file_select_modal).find('.modal-body').html(content);
    $(file_select_modal).on('shown.bs.modal', function() {
        init_file_select();
    }).modal();
}