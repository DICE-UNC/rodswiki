/**
 * Copyright 2013 Drexel University
 */

function checkFileExists() {
    var fileInput = $('#uploadedfile')[0];
    if(fileInput.files.length === 0) { return; }
    var filename = fileInput.files[0].name;
    $.ajax({
        type: "get",
        url: wikiBaseUrl + '/index.php/Special:SpecialIrodsFileDownload?exists&filename=' + filename,
        dataType: 'html',
        async: true
    }).done(function(result) {
        $("#results").html('');
        if (result.trim() === '0') {
            document.getElementById("upload_button").disabled = false;
            document.getElementById("file_exists").style.display = 'none';
            document.getElementById("file_safe").style.display = 'block';
        } else if (result.trim() === '1') {
            document.getElementById("upload_button").disabled = true;
            document.getElementById("file_safe").style.display = 'none';
            document.getElementById("file_exists").style.display = 'block';
            document.getElementById("file_exists").innerHTML =
                    '<font color=red>Warning: This file already exists!</font> <a href="'
                    + wikiBaseUrl + '/index.php/Special:IrodsMetadataPage?filename=' + filename + '">Go to file</a>'
            ;
        } else if (result.trim() === '2') {
            document.getElementById("upload_button").disabled = true;
            document.getElementById("file_safe").style.display = 'none';
            document.getElementById("file_exists").style.display = 'block';
            document.getElementById("file_exists").innerHTML =
                    '<span style="color:red;"><strong>Error</strong>: Filename is invalid!</span>'
            ;
        } else {
            document.getElementById("upload_button").disabled = true;
            document.getElementById("file_safe").style.display = 'none';
            document.getElementById("file_exists").style.display = 'block';
            document.getElementById("file_exists").innerHTML =
                    '<span style="color:red;"><strong>Error</strong>: ' + result.trim() + '</span>'
            ;
        }
    });
}

$('#uploadedfile').on('change', checkFileExists);

