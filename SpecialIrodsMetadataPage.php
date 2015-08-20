<?php

/**
 * Copyright 2013 Drexel University
 */
// SPECIAL PAGE: This page is shown when you click on a file that is stored on IRODS.
//                 Information about the specified file is shown, like metadata and AVUs
// Feature: add/edit/delete the AVUs
// TO DO: implement user permissions.

class SpecialRodsWiki extends SpecialPage
{
    private $dir;
    public function __construct()
    {
        $this->dir = dirname(__FILE__) . '/';
        require_once $this->dir . "RodsWiki.functions.php";
        parent::__construct('IrodsMetadataPage', '', false);
    }
    //$par is a special argument for MediaWiki. It's ok that it's unused in this scope.
    public function execute($par)
    {
        require_once "{$this->dir}RodsWiki.functions.php";
        global $wgOut, $wgUser, $wgScriptPath;

        $this->setHeaders();

        $cleanScriptPath = htmlSafe($wgScriptPath);

        $wgOut->addHTML("<script src='$cleanScriptPath/extensions/RodsWiki/SpecialMetadata.js'></script>");
        $input_filename = filter_input(INPUT_GET, 'filename');
        if (isset($input_filename)) {

            $full_file_path = $filename = $format = null;
            getIrodsPathInformation($input_filename, $full_file_path, $filename, $format);
            $cleanFilename = htmlSafe($filename);
            $wgOut->setPagetitle(htmlSafe(basename($filename)));
            $irodsUser = getAccount();
            $useraccess = 1;
            $modify = $read = true;

            $myFile = new ProdsFile($irodsUser, $full_file_path, $verify = false);
            try {
                if ($myFile->exists()) {
                    // checks if the file exists on irods
                    // Display the page content
                    $metadata = $myFile->getReplInfo();
                    $avus = $myFile->getMeta();
                    $avus_array = array();
                    $sizeOfAVUs = sizeof($avus);
                    for ($i = 0; $i < $sizeOfAVUs; $i++) {
                        $avus_array[$i][0] = $avus_array[$i]['name'] = htmlspecialchars($avus[$i]->name, $flags = ENT_QUOTES);
                        $avus_array[$i][1] = $avus_array[$i]['value'] = htmlspecialchars($avus[$i]->value, $flags = ENT_QUOTES);
                        $avus_array[$i][2] = $avus_array[$i]['units'] = htmlspecialchars($avus[$i]->units, $flags = ENT_QUOTES);
                    }
                    if (!isset($metadata[0])) {
                        $metadata[0] = array();
                    }
                    $metadata = array_merge(
                        array('filename' => basename($full_file_path)),
                        array('AVUs' => $avus_array),
                        $metadata[0]
                    );

                    // Execute different tasks
                    // $wgUser->Mid will be >0 if a user is logged in
                    // AVU triplets are only retrieved if they are actually needed.
                    if ($wgUser->mId > 0) {
                        try {
                            $avu_add = filter_input(INPUT_POST, 'avu_add');
                            if (isset($avu_add)) {
                                $avu_attr = filter_input(INPUT_POST, 'avu_attr');
                                $avu_val  = filter_input(INPUT_POST, 'avu_val');
                                $avu_unit = filter_input(INPUT_POST, 'avu_unit');
                                addAVU($full_file_path, $avu_attr, $avu_val, $avu_unit);
                                $avu_attr = htmlspecialchars($avu_attr, $flags = ENT_QUOTES);
                                $avu_val  = htmlspecialchars($avu_val, $flags = ENT_QUOTES);
                                $avu_unit = htmlspecialchars($avu_unit, $flags = ENT_QUOTES);
                                die("SUCCESS, ADD AVU, ($avu_attr, $avu_val, $avu_unit)");
                            }
                            $avu_delete = filter_input(INPUT_POST, 'avu_delete');
                            if (isset($avu_delete)) {
                                $avu_attr = filter_input(INPUT_POST, 'avu_attr');
                                $avu_val  = filter_input(INPUT_POST, 'avu_val');
                                $avu_unit = filter_input(INPUT_POST, 'avu_unit');
                                deleteAVU($full_file_path, $avu_attr, $avu_val, $avu_unit);
                                $avu_attr = htmlspecialchars($avu_attr, $flags = ENT_QUOTES);
                                $avu_val  = htmlspecialchars($avu_val, $flags = ENT_QUOTES);
                                $avu_unit = htmlspecialchars($avu_unit, $flags = ENT_QUOTES);
                                die("SUCCESS, DELETE AVU, ($avu_attr, $avu_val, $avu_unit)");
                            }
                            $get_full_path = filter_input(INPUT_POST, 'get_full_path');
                            if (isset($get_full_path)) {
                                $avu_attr = filter_input(INPUT_POST, 'avu_attr');
                                $avu_val  = filter_input(INPUT_POST, 'avu_val');
                                $avu_unit = filter_input(INPUT_POST, 'avu_unit');
                                $file_info = RodsWikiParsers::rodsWikiRenderParserFunction(null, $get_full_path, null);
                                die(json_encode(array($file_info['no_files'], $file_info['file_path'])));
                            }
                        } catch (Exception $e) {
                            $errorMessage = (string) $e;
                            die("FAILURE, $errorMessage");
                        }
                    }
                    //echo "An exception occured. " .  $e;

                    // Loat linked files now, since it is user to display the thumbnail
                    $array_links_to_files = array();
                    $linked_files_output = SpecialRodsWiki::searchLinkedFiles($full_file_path, $array_links_to_files);
                    if (isset($array_links_to_files['COL_META_DATA_ATTR_NAME'])) {
                        $key = array_search('thumbnail_of', $array_links_to_files['COL_META_DATA_ATTR_NAME']);
                    } else {
                        $key = false;
                    }
                    $thumbnail = '';
                    if ($key) {
                        if (strtolower($array_links_to_files['COL_META_DATA_ATTR_UNITS'][$key]) == 'irods path') {
                            $thumbnail  = "<img style='border: 1px solid black' ";
                            $thumbnail .= "src='{$cleanScriptPath}/index.php/Special:SpecialIrodsFileDownload?filename=";
                            $thumbnail .= "{$array_links_to_files['COL_COLL_NAME'][$key]}/";
                            $thumbnail .= "{$array_links_to_files['COL_DATA_NAME'][$key]}'";
                            $thumbnail .= "align='right'><br />";
                        }
                    }

                    // Add wikitext header for the metadata

                    //$wgOut->addWikiText("== $cleanFilename ==");
                    $wgOut->addHTML($thumbnail);
                    SpecialRodsWiki::printFileSystemInfo($wgOut, $full_file_path, $metadata);

                    // Download button
                    if ($read || $modify) {
                        $wgOut->addHTML(
                            "<a href='$cleanScriptPath/index.php/Special:SpecialIrodsFileDownload?filename=" .
                            filter_input(INPUT_GET, 'filename') . "'>" .
                            '<button class="icon" value="Download" id="btnDownloadFile" link="' .
                            $cleanScriptPath .
                            '/index.php/Special:SpecialIrodsFileDownload?filename=' .
                            filter_input(INPUT_GET, 'filename') .
                            '"><span class="icon_download">&nbsp;</span>&nbsp;<span class="btn_caption">Download ' .
                            $cleanFilename .
                            '</span></button></a>'
                        );
                    }

                    // Add wikitext header for the metadata
                    $wgOut->addWikiText("== Metadata ==");

                    if ($wgUser->mId > 0) {// checks if there is a user logged in
                        $wgOut->addHTML(SpecialRodsWiki::printIrodsMetadata($metadata, $useraccess));
                    } else {// in case there is no logged in visitor:
                        $wgOut->addHTML(SpecialRodsWiki::printIrodsMetadata($metadata, 0));
                    }

                    // Display the files that have links to the accessed one
                    if ($linked_files_output != false) {
                        $wgOut->addHTML('<br />');
                        $wgOut->addWikiText("== Files having links to $cleanFilename ==");
                        $wgOut->addHTML($linked_files_output);
                    }

                    // Add markup
                    $wgOut->addHTML('<br />');
                    $wgOut->addWikiText("== File Integration in MediaWiki ==");

                    $tag_content = $full_file_path;

                    $wgOut->addHTML('<pre>{{#irods:' . $tag_content . '}}</pre>'); // For file link
                } else {
                    $wgOut->setPagetitle("Missing file " . $filename);
                    $redirect_get = filter_input(INPUT_GET, 'redir');//TODO get rid of this 'redir'. XSS vulnerable.
                    $redirect = '';
                    if (isset($redirect_get)) {
                        $redirect = '&redir=' . $redirect_get;
                    }
                    $wgOut->addHTML(
                        'We are sorry but the file ' .
                        $filename .
                        ' was not found. <a href="' .
                        $cleanScriptPath .
                        '/index.php/Special:SpecialIrodsUploadPage?filename=' .
                        $filename .
                        $redirect .
                        '">Upload this file!</a>'
                    );
                }
            } catch (Exception $e) {
                $wgOut->addWikiText('=== <span style="color:red;">Error connecting to iRODS server</span> ===');
            }
        } else { // in case there is no given filename
            $wgOut->setPagetitle("Filename error");
            $wgOut->addHTML('No filename or path specified.');
        }

        $wgOut->addHTML("<link href='$cleanScriptPath/extensions/RodsWiki/SpecialMetadata.css' rel='stylesheet'>");
        $wgOut->addHTML("<script src='$cleanScriptPath/extensions/RodsWiki/SpecialMetadataEvents.js'></script>");
    }
    public function printFileSystemInfo(&$wgOut, &$full_file_path, &$irods_metadata)
    {
        $formattedSize = getFormattedSize($irods_metadata['size']);
        $dateCreated = date("F j, Y, g:i a", $irods_metadata['ctime']);
        $dateModified = date("F j, Y, g:i a", $irods_metadata['mtime']);
        
	$mystr = <<<FILEINFO
    <table cellpadding="4" cellspacing="4" border=0>
        <tr align="left">
            <td>File size</td>
            <td>$formattedSize</td>
        </tr>
        <tr align="left">
            <td>Created</td>
            <td>$dateCreated</td>
        </tr>
        <tr align="left">
            <td>Modified</td>
            <td>$dateModified</td>
        </tr>
        <tr align="left">
            <td>System full path:</td>
            <td>$full_file_path</td>
        </tr>
        <tr align="left">
            <td>Parent Collection:</td>
            <td>
FILEINFO;

	$wgOut->addHTML($mystr);
	$wgOut->addWikiText("[{{fullurl:Special:SpecialIrodsCollectionBrowser|path=" . dirname($full_file_path) . " " . dirname($full_file_path) . "}}]");
	$wgOut->addHTML("</td></tr></table>");

    }
    /**
     * this function is like a template, which shows the metadata
     *
     * @global type $wgRodsWikiDefaultIrodsRootPath
     * @global type $wgRodsWikiFileFormatURL
     * @global type $configFFUnit
     * @global type $wgRodsWikiFileFormatAttribute
     * @global type $wgRodsWikiAttributeAliases
     * @global type $wgRodsWikiUnitAliases
     * @param type $irods_metadata
     * @param type $useraccess
     * @return string
     */
    public function printIrodsMetadata(&$irods_metadata, $useraccess = 0)
    {
        global $wgRodsWikiDefaultIrodsRootPath, $wgRodsWikiFileFormatURL;
        global $wgRodsWikiFileFormatAttribute, $wgRodsWikiAttributeAliases, $wgRodsWikiUnitAliases;
        // unused? global $configFFUnit

        // create the output list of data
        $metadata = $irods_metadata['AVUs'];
        $configAttrAliasesJson = json_encode($wgRodsWikiAttributeAliases);
        $configUnitAliasesJson = json_encode($wgRodsWikiUnitAliases);
        $output = <<<AVU
<table cellpadding="4" cellspacing="4" border="0" id="avu_table">
    <tr align="left">
        <th width="150">Attribute</th>
        <th width="150">Value</th>
        <th width="150">Units</th>
        <th></th>
    </tr>
</table>
<script>
    var configFFUrl = "$wgRodsWikiFileFormatURL";
    var configFFAttr = "$wgRodsWikiFileFormatAttribute";
    var attrAliases = $configAttrAliasesJson;
    var unitAliases = $configUnitAliasesJson;
AVU;

        $sizeOfMetadata = sizeof($metadata);
        if ($useraccess == 0) { // this is for case when the user should not have access to edit/add irods data
            for ($i = 0; $i < $sizeOfMetadata; $i++) {
                $output .= 'add_row(';
                $output .= "'{$metadata[$i][0]}', ";
                $output .= "'{$metadata[$i][1]}', ";
                $output .= "'{$metadata[$i][2]}', ";
                $output .= "false, ";
                $output .= "'{$metadata[$i][1]}', ";
                $output .= "'', ";
                $output .= "false);";
                #$link '' . // $link was undefined. What is it for?
            }
        } elseif ($useraccess == 1) {
            for ($i = 0; $i < $sizeOfMetadata; $i++) {
                if ($metadata[$i][0] == $wgRodsWikiFileFormatAttribute
                    && $metadata[$i][2] == "http://www.w3.org/2001/XMLSchema#anyURI"
                ) {
                    $output .= 'add_row(';
                    $output .= "'{$metadata[$i][0]}', ";
                    $output .= "'{$metadata[$i][1]}', ";
                    $output .= "'{$metadata[$i][2]}', ";
                    $output .= " false, ";
                    $output .= "'{$metadata[$i][1]}', ";
                    $output .= "'{$wgRodsWikiFileFormatURL}edit?entity={$metadata[$i][1]}'";
                    $output .= "', true);";
                } else {
                    $output .= 'add_row(';
                    $output .= "'{$metadata[$i][0]}', ";
                    $output .= "'{$metadata[$i][1]}', ";
                    $output .= "'{$metadata[$i][2]}', ";
                    $output .= "false);";
                }
            }
        }
        $output .= "\n</script>\n";

        if ($useraccess == 1) {
            $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
            $portName   = filter_input(INPUT_SERVER, 'PORT_NAME');
            if (empty($portName) || $portName == 80) {
                $portName = '';
            } else {
                $portName = ":$portName";
            }
            $requestURIDir = dirname(filter_input(INPUT_SERVER, 'REQUEST_URI'));

            $output .= <<<CONTENT
<table>
    <tr><td>
        <button class="icon" onclick="add_row('', '', '', true);" value="Add metadata">
            <span class="icon_add">&nbsp;</span>
            &nbsp;
            <span class="btn_caption">Add metadata</span>
        </button>
        <button class="icon" id="btnShowLinkToFileTable" value="Link to file">
            <span class="icon_add">&nbsp;</span>
            &nbsp;
            <span class="btn_caption">Add link to file</span>
        </button>
    </td></tr>
</table>
<br>
<table style="display: none" id="tbl_link_file">
    <tr>
        <td colspan = 2><i><b>Link to a file</b></i></td>
        <td></td>
    </tr>
    <tr>
        <td>Link&nbsp;name:</td>
        <td><input type="text" id="link_attribute" value="link_to_file" size="40"></td>
        <td></td>
    </tr>
    <tr>
        <td>File&nbsp;path:</td>
        <td><input type="text" id="link_to_file" id="link_to_file" size="40"></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td colspan="3">Please use one of the following formats:
            <li>iRODS Path (e.g. <b>$wgRodsWikiDefaultIrodsRootPath/<i>image.jpg</i></b>)</li>
            <li>MediaWiki iRODS File URL (e.g. <b>http://$serverName$portName$requestURIDir/index.php?title=Special:IrodsMetadataPage&filename=<i>image.jpg</i></b> )</li>
            <li>Filename (e.g. <b><i>Image.jpg</i></b>)</li>
        </td>
        <td></td>
    </tr>
    <tr>
        <td colspan="2">
            <button class="icon" value="Link to file" id="btnSaveLinkToFile">
                <span class="icon_add">&nbsp;</span>
                &nbsp;
                <span class="btn_caption">Save link to file</span>
            </button>
            <button class="icon" value="Cancel" id="btnCancelLinkToFile">
                <span class="icon_cancel">&nbsp;</span>
                &nbsp;
                <span class="btn_caption">Cancel</span>
            </button>
        </td>
    </tr>
</table>

CONTENT;
        }
        return $output;
    }
    public function changeMetadata($filepath, &$new_raw_data)
    { // this function handles any change that was made (AVUs added/edited/deleted)
        $no_fields = (sizeof($new_raw_data) - 3) / 6;
        $edit_fields = array();
        $delete_fields = array();

        // create the task lists
        for ($i = 1; $i <= $no_fields; $i++) {
            if (trim($new_raw_data['avuattrnew' . $i]) == "" || trim($new_raw_data['avuvalnew' . $i]) == "") {
                $delete_fields[] = array(
                    trim($new_raw_data['avuattrold' . $i]),
                    trim($new_raw_data['avuvalold'  . $i]),
                    trim($new_raw_data['avuunitold' . $i])
                );
            } else {
                $edit_fields[] = array(
                    trim($new_raw_data['avuattrold' . $i]),
                    trim($new_raw_data['avuvalold'  . $i]),
                    trim($new_raw_data['avuunitold' . $i]),
                    trim($new_raw_data['avuattrnew' . $i]),
                    trim($new_raw_data['avuvalnew'  . $i]),
                    trim($new_raw_data['avuunitnew' . $i])
                );
            }
        }

        // editing the fields
        foreach ($edit_fields as $edit_field) {
            updateAVUs(
                $filepath,
                $edit_field[0],
                $edit_field[1],
                $edit_field[2],
                $edit_field[3],
                $edit_field[4],
                $edit_field[5]
            );
        }

        // deleting AVUs

        foreach ($delete_fields as $delete_field) {
            deleteAVU(
                $filepath,
                $delete_field[0],
                $delete_field[1],
                $delete_field[2]
            );
        }

        // adding AVUs
        if (trim($new_raw_data['avuaddattr']) != "" && trim($new_raw_data['avuaddval']) != "") {
            addAVU(
                $filepath,
                trim($new_raw_data['avuaddattr']),
                trim($new_raw_data['avuaddval']),
                trim($new_raw_data['avuaddunit'])
            );
        }
    }
    public function searchLinkedFiles($original_file_full_path, &$result_values)
    {
        global $wgRodsWikiAttributeAliases, $wgScriptPath;

        $cleanScriptPath = htmlSafe($wgScriptPath);
        $IrodsAccount = getAccount();
        // look for the mediaWiki Alias
        $flds = array(
            'COL_DATA_NAME' => null,
            'COL_DATA_SIZE' => null,
            'COL_COLL_NAME' => null,
            'COL_META_DATA_ATTR_NAME' => null,
            'COL_META_DATA_ATTR_UNITS' => null
        );
        $select = new RODSGenQueSelFlds(array_keys($flds), array_values($flds));
        $condition = new RODSGenQueConds();
        $condition->add('COL_META_DATA_ATTR_NAME', 'LIKE', '%%');
        $condition->add('COL_META_DATA_ATTR_VALUE', 'LIKE', $original_file_full_path);
        $condition->add('COL_COLL_NAME', 'not like', '%/trash/%');
        $condition->add('COL_COLL_NAME', 'like', '/%'); // recursive search

        $conn = RODSConnManager::getConn($IrodsAccount);
        $results = $conn->query($select, $condition, 0, -1);
        RODSConnManager::releaseConn($conn);

        $result_values = $results->getValues();

        if (!isset($result_values)
            || !array_key_exists('COL_DATA_NAME', $result_values)
            || (sizeof($result_values['COL_DATA_NAME']) == 0)
        ) {
            return false;
        }
        function getDifferentPathFile2($file1, $file2)
        {
            $q = 0;
            $minStringLengthOfFiles = min(strlen($file1), strlen($file2));
            for ($i = 0; $i < $minStringLengthOfFiles; $i++) {
                if ($file1[$i] == $file2[$i]) {
                    $q++;
                } else {
                    break;
                }
            }
            return substr($file2, $q);
        }
        $output = '<table cellpadding="4" cellspacing="4" border=0>';
        $output .= "<tr>\n<th>File</th>\n<th>From Attribute</th>\n</tr>";
        $sizeOfColDataName = sizeof($result_values['COL_DATA_NAME']);
        for ($i = 0; $i < $sizeOfColDataName; $i++) {
            $col = getDifferentPathFile2($original_file_full_path, $result_values["COL_COLL_NAME"][$i]);
            if (substr($col, 0, 5) == "arch-") {
                $col = substr($col, 1 + strpos($col, '/'));
            }
            $attr_val = $result_values["COL_META_DATA_ATTR_NAME"][$i];
            if (isset($wgRodsWikiAttributeAliases[$attr_val])) {
                $attr_alias = '<abbr title="' . htmlSafe($attr_val) . '">';
                $attr_alias .= htmlSafe($wgRodsWikiAttributeAliases[$attr_val]) . '</attr>';
            } else {
                $attr_alias = htmlSafe($attr_val);
            }
            $col .= '/';
            $col = htmlSafe($col);
            $output .= <<<COLDATA
    <tr>
        <td>
            <a href="$cleanScriptPath/index.php/Special:IrodsMetadataPage?filename={$result_values["COL_COLL_NAME"][$i]}/{$result_values["COL_DATA_NAME"][$i]}">
                $col {$result_values["COL_DATA_NAME"][$i]}
            </a>
        </td>
        <td>$attr_alias</td>
    </tr>
</table>
COLDATA;
        }
    }
}

