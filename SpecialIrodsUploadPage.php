<?php

/**
 * Copyright 2013 Drexel University
 */
// SPECIAL PAGE: This page is an upload form that saves a file directly on the IRODS server
// (no storage on the local server)

class SpecialIrodsUploadPage extends SpecialPage
{
    public function __construct()
    {
        require_once dirname(__FILE__) . "/RodsWiki.functions.php";
        require_once dirname(__FILE__) . "/RodsWiki.install.php";
        parent::__construct('SpecialIrodsUploadPage');
    }
    public function execute($par)
    {
        global $wgOut, $wgScriptPath, $wgRodsWikiDefaultIrodsRootPath, $wgUser;
        $this->setHeaders();
        //$options = filter_input(INPUT_POST, 'options');

        //OpenRodsWiki($IrodsAccount);
        if ($wgUser->mId == 0) { // checks if there is a user logged in
            $wgOut->setPagetitle("Restricted access");
            $wgOut->addHTML("You must be logged in to have access to this page.");
            return;
        }

        // get the maximum file size
        $max_upload = (ini_get('upload_max_filesize'));
        $max_post = (ini_get('post_max_size'));
        $memory_limit = (ini_get('memory_limit'));
        $max_file_upload = min($max_upload, $max_post, $memory_limit);

        // this block uploads the file to irods
        $success = 0;
        $filepath = filter_input(INPUT_POST, 'filename');
        if (isset($_FILES['uploadedfile'])) { // it is executed only if the form was submitted already
            $filename = basename($_FILES['uploadedfile']['name']);
            if (!existFile($wgRodsWikiDefaultIrodsRootPath . '/' . getWikiFilePath($filename))) {
                if ($filepath != "") {
                    $filename = basename(getWikiFilePath($filepath));
                }
            }
            if (preg_match('/\.[0-9]*$/i', $filename)) {
                $pathinfo = pathinfo($filename);
                $filename = $pathinfo['filename'];
            }
            $dest_file = $wgRodsWikiDefaultIrodsRootPath . '/' . getWikiFilePath($filename);
	    $fixed_filename = filenameToLegalCharacters($filename);

            $md5 = md5($fixed_filename);
            $directoryName1 = substr($md5, 0, 1);
            $directoryName2 = substr($md5, 0, 2);
            $directory1 = makeIrodsDirectory($wgRodsWikiDefaultIrodsRootPath, $directoryName1);
            $directory2 = makeIrodsChildDirectory($directory1, $directoryName2);
            if (uploadFileOnIrods($_FILES['uploadedfile']['tmp_name'], $dest_file)) {
                $success = 1;
            }
        }
        $server_name = filter_input(INPUT_SERVER, 'SERVER_NAME');
        $server_port = serverPortForUrl(filter_input(INPUT_SERVER, 'SERVER_PORT'));
        $request_uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        if ($success) {
            $redirect = "http://{$server_name}{$server_port}{$wgScriptPath}/index.php/Special:IrodsMetadataPage?filename={$filename}";
            die("<script>window.location = '$redirect';</script><META HTTP-EQUIV='refresh' CONTENT=\"0; URL='$redirect'\">");
        }
        $get_filename = filter_input(INPUT_GET, 'filename');
        if (!$success) {
            $wgOut->addHTML("To include a file in a page, use a link in one of the following form: <br /><pre>{{#irods:" . ((isset($get_filename)) ? ($get_filename) : 'File.jpg') . "}}</pre>");
        }

        // shows the upload form, no matter if the file was uploaded or not
        $wgOut->addWikiText("== Source file ==");
        if (isset($get_filename)) {
            $filenameString = ' value="' . $get_filename . '"';
        } else {
            $filenameString = '';
        }
        $fileExists = (int) isset($get_filename);
        $output = <<<OUTPUT
    <form enctype="multipart/form-data" action="http://$server_name$server_port$request_uri" method="POST" name="uploadform">
        <div>
            <p>Choose a file to upload: <input id="uploadedfile" name="uploadedfile" type="file" size="50"></p>
        </div>
        <div>Maximum file size: $max_file_upload</div>
            <div id="file_exists" style="display: none">
                <font color="red">&#9888; Warning: This file already exists!</font>
            </div>
            <div id="file_safe" style="display: none">
                <font color="green">&#10003; This file could be uploaded safely!</font>
            </div>
        </div>
        <div id="results"></div>
        <input type="submit" value="Upload File" name="upload_button" id="upload_button"/>
    </form>
    <script>
        configIrodsRootPath = "$wgRodsWikiDefaultIrodsRootPath";
        wikiBaseUrl = "http://$server_name$server_port$wgScriptPath";
        filenameLocked = $fileExists;
    </script>
    <script src="$wgScriptPath/extensions/RodsWiki/SpecialUpload.js"></script>
OUTPUT;
        $wgOut->addHTML($output);
        $wgOut->setPagetitle("Upload mediafile");
    }
}

