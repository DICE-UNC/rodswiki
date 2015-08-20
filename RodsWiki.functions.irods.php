<?php
/**
 * Copyright 2013 Drexel University
 */
if (!defined('MEDIAWIKI')) {
    die('Invalid entry point');
}

function getCustomAccount()
{
    global $wgUser;
    if ($wgUser->mId == 0) {
        return null;
    }
    $dbr = wfGetDB(DB_SLAVE);
    $returnValue = $dbr->selectRow(
        array("irods_users"),
        array("irods_user_name, irods_user_password, irods_user_resource, irods_user_zone"),
        array("wiki_user_id" => $wgUser->mId, "irods_custom_account_active" => 1)
    );
    if (isset($returnValue->irods_user_name) && isset($returnValue->irods_user_password)) {
        return $returnValue;
    } else {
        return null;
    }
}
// creates a global irods connection that could be used by all functions
function getAccount($forceDefault = false)
{
    global $wgRodsWikiIrodsHost, $wgRodsWikiIrodsServerPort, $wgRodsWikiDefaultIrodsUser, $wgRodsWikiDefaultIrodsPass,
    $wgRodsWikiDefaultIrodsResource, $wgRodsWikiDefaultIrodsZone;
    $customInfo = getCustomAccount();
    if (!$forceDefault && isset($customInfo)) {
        $account = new RODSAccount(
            $wgRodsWikiIrodsHost,
            $wgRodsWikiIrodsServerPort,
            $customInfo->irods_user_name,
            $customInfo->irods_user_password,
            $customInfo->irods_user_zone,
            $customInfo->irods_user_resource
        );
        return $account;
    }
    $account = new RODSAccount(
        $wgRodsWikiIrodsHost,
        $wgRodsWikiIrodsServerPort,
        $wgRodsWikiDefaultIrodsUser,
        $wgRodsWikiDefaultIrodsPass,
        $wgRodsWikiDefaultIrodsZone,
        $wgRodsWikiDefaultIrodsResource
    );
    return $account;
}

// Small shortcut for ensuring $path does not end in a traling slash.
function buildProdsDirectory($path, $account, $verify = true)
{
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }
    $newDirectory = new ProdsDir($account, $path, $verify);
    return $newDirectory;
}
// $parentDirectory is a ProdsDir object representing the parent dir.
// $newDirectoryName is the desired name for the new directory.
function makeIrodsChildDirectory($parentDirectory, $newDirectoryName)
{
    try {
        $childDirectory = $parentDirectory->mkdir($newDirectoryName); // creates dir
    } catch (Exception $e) {
        $errorCode = (int) $e->getCode();
        switch ($errorCode) {
            case -809000:
                $childDirectory = buildProdsDirectory(
                    "{$parentDirectory->path_str}/$newDirectoryName",
                    $parentDirectory->account,
                    false
                );
                break;
            case -818000:
                die("User not allowed to create iRODS collections.");
                break;
            default:
                throw $e;
        }
    }
    return $childDirectory;
}
// Shortcut for creating a new ProdsDir object and creating a child directory.
// $account argument is available for use when an account object has already been created.
function makeIrodsDirectory($path, $newDirectoryName, $account = null, $verify = true)
{
    if (!isset($account)) {
        $account = getAccount();
    }
    $parentDirectory = buildProdsDirectory($path, $account, $verify);
    $childDirectory = makeIrodsChildDirectory($parentDirectory, $newDirectoryName);
    return $childDirectory;
}
function uploadFileOnIrods(&$src_path, $dest_path)
{
    $account = getAccount();
    // set running time for PHP to 0 = no limit
    set_time_limit(0);
    $input_f = fopen($src_path, 'r');
    try {
        $myfile = new ProdsFile($account, $dest_path, $verify = false); // creates irods file object
        //read and print out the file
        $myfile->open("w+", $account->default_resc); // creates an empty file on irods server

        while (!feof($input_f)) {
            // fills the empty file with the content stored in memory on irods server
            $myfile->write(fread($input_f, 4096));
        }
        //close the file pointer
        $myfile->close();
        fclose($input_f);
    } catch (Exception $e) {
        print_r(var_dump($e));
        if ($e->getCode() == -818000) {
            die("User not allowed to upload iRODS file at this location.");
        } else {
            die("An error occured when trying to upload the file at the specified path.");
        }
        return false;
    }
    // creates irods file object to check if it was successfully written
    $myfile_check = new ProdsFile($account, $dest_path);
    $fileInfo = $myfile_check->getReplInfo();
    if ($fileInfo[0]['size'] == filesize($src_path)) {
        // in case the file was completely uploaded
        return true;
    }
    return false;
}
function getIrodsMetadata($filepath, $account = null)
{
    if (!isset($account)) {
        $account = getAccount();
    }
    $myfile = new ProdsFile($account, $filepath);
    $metadata = $myfile->getReplInfo();
    $attributeValueUnits = $myfile->getMeta();
    $AVUsArray = array();
    $sizeOfAVUs = sizeof($attributeValueUnits);
    for ($i = 0; $i < $sizeOfAVUs; $i++) {
        $AVUsArray[$i][0] = $AVUsArray[$i]['name']  = $attributeValueUnits[$i]->name;
        $AVUsArray[$i][1] = $AVUsArray[$i]['value'] = $attributeValueUnits[$i]->value;
        $AVUsArray[$i][2] = $AVUsArray[$i]['units'] = $attributeValueUnits[$i]->units;
    }
    if (!isset($metadata[0])) {
        $metadata[0] = array();
    }
    $metadata = array_merge(
        array('filename' => basename($filepath)),
        array('AVUs' => $AVUsArray), // for AVUs
        $metadata[0]
    );
    return $metadata;
}
function updateAVUs(
    $filepath,
    $attributeOld,
    $valueOld,
    $unitOld,
    $attributeNew,
    $valueNew,
    $unitNew
) {
    try {
        if (existsAVU($filepath, $attributeNew, $valueNew, $unitNew)) {
            addAVU($filepath, $attributeNew, $valueNew, $unitNew);
        } else {
            $account = getAccount();
            if ($account == null) {
                throw new RODSException('Cannot write metadata of file.', 'FILE_WRITE_ERR');
                //die("User not allowed to update the metadata of this file object.");
            }
            $myfile = new ProdsFile($account, $filepath);
            $metaOld = new RODSMeta($attributeOld, $valueOld, $unitOld);
            $metaNew = new RODSMeta($attributeNew, $valueNew, $unitNew);
            $myfile->updateMeta($metaOld, $metaNew);
        }
    } catch (Exception $e) {
        if ($e->getCode() == -818000) {
            die("User not allowed to update the metadata of this file object.");
        } else {
            print_r($e);
        }
    }
}
function existsAVU($filepath, $attribute, $value, $unit)
{
    $metadata = getIrodsMetadata($filepath);
    foreach ($metadata['AVUs'] as $AVU) {
        if ($AVU['name'] == $attribute && $AVU['value'] == $value && $AVU['units'] == $unit) {
            return true;
        }
    }
    return false;
}
function addAVU($filepath, $attributeNew, $valueNew, $unitNew)
{
    if (existsAVU($filepath, $attributeNew, $valueNew, $unitNew)) {
        return 0;
    } else {
        $account = getAccount(true);
        $myfile = new ProdsFile($account, $filepath);
        $metaNew = new RODSMeta($attributeNew, $valueNew, $unitNew);
        $myfile->addMeta($metaNew);
    }
}
function deleteAVU($filepath, $attribute, $value, $unit)
{
    try {
        if (existsAVU($filepath, $attribute, $value, $unit)) {
            $account = getAccount();
            $myfile = new ProdsFile($account, $filepath);
            $meta = new RODSMeta($attribute, $value, $unit);
            $myfile->rmMeta($meta);
        }
    } catch (Exception $e) {
        if ($e->getCode() == -818000) {
            die("User not allowed to delete metadata of this iRODS file.");
        } else {
            print_r($e);
        }
    }
}

function deleteFile($dest_path)
{
    $account = getAccount();
    $myfile = new ProdsFile($account, $dest_path); // creates irods file object
    if ($myfile->exists()) {
        $account_rights = getAccount();
        if ($account != $account_rights) {
            $account = $account_rights;
            $myfile = new ProdsFile($account, $dest_path); // creates irods file object
        } elseif ($account_rights == null) {
            return false;
        }
    }
    try {
        if ($myfile->exists()) {
            $myfile->unlink($account->default_resc); // delete command
        }
        $myfile->close();
    } catch (Exception $e) {
        if ($e->getCode() == -818000) {
            die("User not allowed to delete this iRODS file.");
        } else {
            print_r($e);
        }
    }
}
function existFile($file)
{
    $account = getAccount(true);
    // creates irods file object
    $myfile = new ProdsFile($account, $file, $verify = false);
    // checks if the file exists and returns the result
    return $myfile->exists();
}
function existCollection($path)
{
    $account = getAccount();
    $newdir = new ProdsDir($account, $path);
    return $newdir->exists();
}
function getIrodsChildFiles($path)
{
    $account = getAccount(true);
    $dir = new ProdsDir($account, $path);
    return $dir->getChildFiles();
}
function getIrodsPathInformation($input_filename, &$full_file_path, &$filename, &$format)
{
    global $wgRodsWikiDefaultIrodsRootPath;
    $fileinfo = pathinfo($input_filename);
    if (isset($fileinfo['extension'])) {
        $format = $fileinfo['extension'];
    }
    if (substr($input_filename, 0, 1) == '/') {
        $filename = $fileinfo['basename'];
        $full_file_path = $input_filename;
    } else {
        $filename = filenameToLegalCharacters(strip_tags(str_replace('\\', ' ', $input_filename)));
        $additional_path = dirname(getWikiFilePath($filename));
        $full_file_path = "$wgRodsWikiDefaultIrodsRootPath/$additional_path/$filename";
    }
}

