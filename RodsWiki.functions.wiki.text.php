<?php
/**
 * Copyright 2013 Drexel University
 */
if (!defined('MEDIAWIKI')) {
    die('Invalid entry point');
}
function getWikiFilePath($filename, $include_filename = true)
{
    // returns the path the file should be found at
    $filename = filenameToLegalCharacters($filename);
    $md5 = md5($filename);
    if ($include_filename == true) {
        return substr($md5, 0, 1) . '/' . substr($md5, 0, 2) . '/' . $filename;
    } else {
        return substr($md5, 0, 1) . '/' . substr($md5, 0, 2) . '/';
    }
}
function filenameToLegalCharacters($filename)
{
    return preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), trim($filename));
}
function validateFilename($filename)
{
    $valid = preg_match('^[a-zA-Z0-9_-]+(.[a-zA-Z0-9]+)*$', $filename);
    if ($valid === 1) {
        return true;
    } elseif ($valid === 0) {
        return false;
    } else {
        return null;
    }
}
function getFormattedSize($bytes)
{
    // set filesize in different units, depending on the size
    if ($bytes > 1024 * 1024 * 1024) {
        $filesize_units = "GiB";
        $filesize = round($bytes / (1024 * 1024 * 1024.0), 2);
    } elseif ($bytes > 1024 * 1024) {
        $filesize_units = "MiB";
        $filesize = round($bytes / (1024 * 1024.0), 2);
    } elseif ($bytes > 1024) {
        $filesize_units = "KiB";
        $filesize = round($bytes / (1024.0), 2);
    } else {
        $filesize_units = "bytes";
        $filesize = $bytes;
    }
    return "$filesize $filesize_units";
}
function serverPortForUrl($portNumber)
{
    if ($portNumber == '') {
        return '';
    } else {
        return ":$portNumber";
    }
}
function htmlSafe($untrusted)
{
    return htmlspecialchars($untrusted, $flags = ENT_QUOTES);
}

