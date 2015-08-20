/**
 * Copyright 2013 Drexel University
 */

function cancel_avu(obj) {
    var id = $(obj).attr('id');
    var start = parseInt(id.substring(id.lastIndexOf("_") + 1));

    if ($("#cell_avu_span_" + start).text() == '' &&
            $("#cell_avu_span_" + (start + 1)).text() == '' &&
            $("#cell_avu_span_" + (start + 2)).text() == '')
        hide_row("#tr_avu_" + start, 'slow');
    else {
        change_mode(start, 'read', 'input');
    }
    return 0;
}

function handle_response(data) {
    if (typeof console !== 'undefined' && typeof console.log === 'function') {
        console.log(data);
    }
}

function save_avu(obj, send_request, delete_request, display_links) {
    var id = obj.attr('id');
    var start = parseInt(id.substring(id.lastIndexOf("_") + 1));

    if ($("#cell_avu_span_" + start).text() == $('#cell_avu_input_' + start).val() &&
            $("#cell_avu_span_" + (start + 1)).text() == $('#cell_avu_input_' + (start + 1)).val() &&
            $("#cell_avu_span_" + (start + 2)).text() == $('#cell_avu_input_' + (start + 2)).val())
    {
        if (typeof send_requests !== 'undefined')
            send_request = false;
        if (typeof delete_request !== 'undefined')
            delete_request = false;
    }
    send_request = typeof send_requests !== 'undefined' ? send_request : true;
    delete_request = typeof delete_request !== 'undefined' ? delete_request : true;
    display_links = typeof links !== 'undefined' ? display_links : true;


    if (delete_request)
        delete_avu(obj, false);

    change_mode(start, 'read', 'span', display_links);

    if ($("#cell_avu_span_" + start).text() == '' &&
        $("#cell_avu_span_" + (start + 1)).text() == '' &&
        $("#cell_avu_span_" + (start + 2)).text() == ''
    ) {
        $("#tr_avu_" + start).hide('slow');
    }

    // send to PHP
    if (send_request)
        $.post($(location).attr('href'), {avu_add: 1,
            avu_attr: $("#cell_avu_input_" + start).val(),
            avu_val: $("#cell_avu_input_" + (start + 1)).val(),
            avu_unit: $("#cell_avu_input_" + (start + 2)).val()},
            handle_response);

    return 0;
}
function delete_avu(obj, hide) {
    var id = obj.attr('id');
    var start = parseInt(id.substring(id.lastIndexOf("_") + 1));
    hide = typeof hide !== 'undefined' ? hide : true;

    // hide  is false when the save button is pressed. The AVU is first deleted and then added
    if (hide)
        hide_row("#tr_avu_" + start, 'slow');

    // send to PHP
    if (!($("#cell_avu_irods_" + start).val() == '' && $("#cell_avu_irods_" + (start + 1)).val() == ''))
        $.post($(location).attr('href'), {avu_delete: 1,
            avu_attr: $("#cell_avu_irods_" + start).val(),
            avu_val: $("#cell_avu_irods_" + (start + 1)).val(),
            avu_unit: $("#cell_avu_irods_" + (start + 2)).val()},
            handle_response);

    //change_mode(start, false, false, false);
}

function add_row(attr, val, unit, edit_mode, valueAlias, url, editable) {
    var id = $('#avu_table tr:last').attr('id');
    var start, inp_visibility, span_visibility;
    var tr_control = '';

    edit_mode = typeof edit_mode !== 'undefined' ? edit_mode : false;
    valueAlias = typeof valueAlias !== 'undefined' ? valueAlias : val;
    url = typeof url !== 'undefined' ? url : '';
    editable = typeof editable !== 'undefined' ? editable : true;

    if ($('#avu_table').attr('no_rows') === undefined)
        start = 0;
    else
        start = parseInt($('#avu_table').attr('no_rows'));

    if (editable)
        tr_control =
                '<button class="icon" id="cell_avu_edit_' + start + '" onclick="' +
                '$(\'#cell_avu_delete_' + (start) + '\').show();' +
                '$(\'#cell_avu_save_' + (start) + '\').show();' +
                '$(\'#cell_avu_cancel_' + (start) + '\').show();' +
                '$(\'#cell_avu_input_' + start + '\').show();' +
                '$(\'#cell_avu_input_' + (start + 1) + '\').show();' +
                '$(\'#cell_avu_input_' + (start + 2) + '\').show();' +
                '$(\'#cell_avu_span_' + start + '\').hide();' +
                '$(\'#cell_avu_span_' + (start + 1) + '\').hide();' +
                '$(\'#cell_avu_span_' + (start + 2) + '\').hide();' +
                '$(this).hide();"><span class="icon_edit">&nbsp;</span>&nbsp;<span class="btn_caption">Edit</span></button>' +
                '<button class="icon" value="Cancel" id="cell_avu_cancel_' + start + '" onclick="cancel_avu($(this), false)" style="display:none"><span class="icon_cancel">&nbsp;</span>&nbsp;<span class="btn_caption">Cancel</span></button>' +
                '<button class="icon" value="Save" id="cell_avu_save_' + start + '" onclick="save_avu($(this))" style="display:none"><span class="icon_save">&nbsp;</span>&nbsp;<span class="btn_caption">Save</span></button>' +
                '<button class="icon" value="Delete" id="cell_avu_delete_' + start + '" onclick="delete_avu($(this))" style="display:none"><span class="icon_delete">&nbsp;</span>&nbsp;<span class="btn_caption">Delete</span></button>';

    var vals = personalize_entry(attr, val, unit);
    var attrAlias = vals[0];
    var unitAlias = vals[1];
    valueAlias = vals[2];
    url = vals[3];

    var tr =
            '<tr id="tr_avu_' + start + '" style="display:none">' +
            '<td>' + cell_content(start, attr, attrAlias, '', editable) + '</td>' +
            '<td>' + cell_content(start + 1, val, valueAlias, url, editable) + '</td>' +
            '<td>' + cell_content(start + 2, unit, unitAlias, '', editable) + '</td>' +
            '<td>' +
            tr_control +
            '</td>' +
            '</tr>';

    $('#avu_table').append(tr);

    $('#avu_table').attr('no_rows', start + 3);

    if (edit_mode) {
        change_mode(start, 'edit');
        show_row('#avu_table tr:last', 'slow');
    } else {
        change_mode(start, 'read');
        show_row('#avu_table tr:last');
    }
    return $('#avu_table tr:last');
}

function cell_content(id, value, valueAlias, url, editable) {
    var link_start_tag = '';
    var link_end_tag = '';
    var input_tag = '';
    var abbr_start_tag = '';
    var abbr_end_tag = '';
    if (url != '') {
        link_start_tag = '<a href="' + url + '">';
        link_end_tag = '</a>';
    }
    if (value != valueAlias) {
        abbr_start_tag = '<abbr title="' + value + '">';
        abbr_end_tag = '</abbr>';
    }

    if (editable)
        input_tag = '<input type="text" value="' + value + '" id="cell_avu_input_' + id + '" />';
    else
        input_tag = '<input type="hidden" value="' + value + '" id="cell_avu_input_' + id + '" />';

    var hidden_irods_value = '<input type="hidden" id="cell_avu_irods_' + id + '" value = "' + value + '"/>';

    return abbr_start_tag + link_start_tag + '<span id="cell_avu_span_' + id + '">' + valueAlias + '</span>' + link_end_tag + abbr_end_tag + input_tag + hidden_irods_value;
}

function change_mode(start, inp_visibility, update_fields, display_links) {
    var span_visibility;

    inp_visibility = typeof inp_visibility !== 'undefined' ? inp_visibility : 'read';
    update_fields = typeof update_fields !== 'undefined' ? update_fields : false;

    if (inp_visibility == 'read') {
        inp_visibility = 'hide';
        span_visibility = 'show';
    } else {
        inp_visibility = 'show';
        span_visibility = 'hide';
    }

    eval("$('#cell_avu_delete_" + start + "')." + inp_visibility + "();");
    eval("$('#cell_avu_save_" + start + "')." + inp_visibility + "();");
    eval("$('#cell_avu_cancel_" + start + "')." + inp_visibility + "();");
    eval("$('#cell_avu_input_" + start + "')." + inp_visibility + "();");
    eval("$('#cell_avu_input_" + (start + 1) + "')." + inp_visibility + "();");
    eval("$('#cell_avu_input_" + (start + 2) + "')." + inp_visibility + "();");
    eval("$('#cell_avu_span_" + start + "')." + span_visibility + "();");
    eval("$('#cell_avu_span_" + (start + 1) + "')." + span_visibility + "();");
    eval("$('#cell_avu_span_" + (start + 2) + "')." + span_visibility + "();");
    eval("$('#cell_avu_edit_" + start + "')." + span_visibility + "();");

    if (update_fields == 'input') {
        $('#cell_avu_input_' + start).val($('#cell_avu_irods_' + start).val());
        $('#cell_avu_input_' + (start + 1)).val($('#cell_avu_irods_' + (start + 1)).val());
        $('#cell_avu_input_' + (start + 2)).val($('#cell_avu_irods_' + (start + 2)).val());
    } else if (update_fields == 'span') {

        var url, valueAlias;
        var attr = $('#cell_avu_input_' + start).val();
        var val = $('#cell_avu_input_' + (start + 1)).val();
        var unit = $('#cell_avu_input_' + (start + 2)).val();
        var vals = personalize_entry(attr, val, unit);
        var attrAlias = vals[0];
        var unitAlias = vals[1];
        var valueAlias = vals[2];
        url = vals[3];

        if (display_links)
            if (url != "")
                create_link($('#cell_avu_span_' + (start + 1)), url);
            else
                remove_link($('#cell_avu_span_' + (start + 1)));

        $('#cell_avu_irods_' + start).val(attr);
        $('#cell_avu_irods_' + (start + 1)).val(val);
        $('#cell_avu_irods_' + (start + 2)).val(unit);

        $('#cell_avu_span_' + start).text(attrAlias);
        $('#cell_avu_span_' + (start + 1)).text(valueAlias);
        $('#cell_avu_span_' + (start + 2)).text(unitAlias);
    }
}

function create_link(htmlObj, url) {
    remove_link(htmlObj);
    $(htmlObj).wrap("<a href=\"" + url + "\"></a>");
}

function remove_link(htmlObj) {
    if (htmlObj.parent().is("a")) {
        htmlObj.unwrap();
    }
}

function personalize_entry(attr, val, unit) {

    var continue_checks = true;
    var valueAlias = val;
    //var attrAlias = attr;
    //var unitValue = unit;
    var url = '';

    if (continue_checks)
        if (unit.toLowerCase() == 'irods path') {
            valueAlias = baseName(val);
            url = getUrlWithoutGet() + "?title=Special:IrodsMetadataPage&filename=" + val;
        } else if (attr.toLowerCase() == configFFAttr.toLowerCase()) {
            valueAlias = val;
            url = configFFUrl + 'edit?entity=' + val;
        }

    eval("if(attrAliases[attr]) attr = attrAliases[attr];");
    eval("if(unitAliases[unit]) unit = unitAliases[unit];");

    return [attr, unit, valueAlias, url];
}

function getUrlWithoutGet() {
    return new String(window.location.toString()).substring(0, window.location.toString().lastIndexOf('?'));
}

function baseName(str)
{
    return new String(str).substring(str.lastIndexOf('/') + 1);
}

function hide_row(row_obj, effect) {
    if (typeof effect == 'undefined')
        $(row_obj).hide()
    else
        $(row_obj).hide(effect);
}

function show_row(row_obj, effect) {
    if (typeof effect == 'undefined')
        $(row_obj).show()
    else
        $(row_obj).show(effect);
}

