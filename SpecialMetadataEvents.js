/**
 * Copyright 2013 Drexel University
 */

function checkFilePath(path) {
    var re1 = new RegExp('^/[^\< \> \: \"  \\ \| \? \*]*$');
    var re2 = new RegExp('^http://[^\< \> \: \"  \\ \| \? \*]*$');
    var re3 = new RegExp('^[^\< \> \: \"  \\ \| \? \*]*$');
    if (path.match(re1))
        return true
    else if (path.match(re2))
        return true
    else if (path.match(re3))
        return true;

    return false;
}

$('#link_to_file').keyup(function() {
    var val = $('#link_to_file').val();
    //autoselectPathType(val);
    $('#link_to_file').focus();
    $('#link_to_file').val(val);
});

$('#btnSaveLinkToFile').click(function() {
    var path = $.trim($('#link_to_file').val());
    if (!checkFilePath(path)) {
        alert("The file path does not match any of the shown types.")
        return false;
    }
    var irodsPath = '';
    var display_link = true;
    var re1 = new RegExp('^/[^\< \> \: \" \\ \| \? \*]*$');
    var re2 = new RegExp('^http://.*filename=[^\< \> \" \& \\ \| \*]*.*$');
    var re3 = new RegExp('^[^\< \> \: \"  \\ \| \? \*]*$');
    if (path.match(re1)) {
        irodsPath = path;
    }
    else if (path.match(re2)) {
        var start = path.toLowerCase().indexOf('filename=') + 9;
        var link_start_pointer = path.substring(start);
        var limit = link_start_pointer.length;
        if (link_start_pointer.indexOf('&') > 0) {
            limit = link_start_pointer.indexOf('&');
        }
        irodsPath = path.substring(start, start + limit);
    }
    else if (path.match(re3)) {
        irodsPath = path;
        $.post($(location).attr('href'), {get_full_path: irodsPath}, function(data) {
            var obj = $.parseJSON(data);
            var val = obj[1];
            if (obj[0] == 0) {
                alert("The specified file does not exist.");
                return 0;
            }
            else if (obj[0] > 1) {
                alert("There are multiple files using this alias or having this file name. Please insert a full path to avoid confusions.");
                return 0;
            }
            var row = add_row($('#link_attribute').val(), val, 'iRODS Path', true);
            save_avu(row, true, false);
            return 0;
        });
        return 0;
    }
    else {
        display_link = false;
        irodsPath = path;
    }
    var row = add_row($('#link_attribute').val(), irodsPath, 'iRODS Path', true);
    save_avu(row, true, false);
});

$('#btnCancelLinkToFile').click(function() {
    $('#tbl_link_file').hide("fast");
    $('#btnShowLinkToFileTable').show("fast");
});

$('#btnShowLinkToFileTable').click(function() {
    $('#tbl_link_file').show("fast");
    $(this).hide("fast");
});

