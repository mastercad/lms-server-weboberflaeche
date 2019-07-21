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
