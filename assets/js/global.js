import $, { get, each, post } from 'jquery';
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

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

    $('.sync_mappings').unbind('click').click(function() {
        sync_mappings();
    });

    let current_selected_client = parse_current_selected_client();

    load_clients_list(current_selected_client);

    load_current_client_mappings(current_selected_client);
});

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

function init_client_select() {
    $('#clients_select_container a').each(function() {
        $(this).unbind('click').on('click', function() {
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
    get("/mapping/edit/"+id+"/?client="+client_id, function(data) {
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
    });
}

function parse_current_selected_client() {
    return $(location).attr('hash').replace(/^#/, "");
}

function load_current_client_mappings(current_selected_client) {
    get("/mapping?client_id="+current_selected_client+"&response_type=json", function(response) {
        let clients_container = $('#mapping_content');
        clients_container.html(response);

        init_mapping_options();
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
 * Create select string entry for online client.
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

    $.get("/client/detail/"+Base64.encode('http://'+client['ip']+':'+client['port']), 
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
        console.log(response.content);
        init_modal(response.content, controller, true, callback);
    }
}

function sync_mappings() {
    let mappings = [];
    let clients = [];

    let selectedMappings = $('#mapping_content .checkbox-select-row:checked').each(function() {
        mappings.push($(this).data('id'));
    });

    if (0 >= mappings.length) {
        alert("Keine Mappings ausgewählt!");
        return false;
    }

    get('/client/select-dialog', function(response) {
        let modal = $(response);
        modal.on('hidden.bs.modal', function() {modal.remove()}).modal();

        modal.find('.btn-primary').on('click', function() {
            modal.find('input[type="checkbox"]:checked').each(function() {
                clients.push($(this).data('id'));
            });

            if (0 >= clients.length) {
                alert("Kein Client ausgewählt!");
                return false;
            }

            post('/mapping/sync?response_type=json', {'mappings': mappings, 'clients': clients}, function(response) {
                if (true === response.success) {
                    modal.modal('hide');
                }
            })
        });
    });
}
