<?php

/**
 * Copyright 2013 Drexel University
 */
// class that handles all the hooks
class RodsWikiHooks
{
    // this function is called when the toolbox is loaded
    // PURPOSE: used to add new links in the toolbox
    public static function toolboxAddUploadPage(&$sk, &$toolbox)
    {
        global $wgScript, $wgUser;
        // checks if there is an user logged in
        if ($wgUser->mId > 0) {
            // adds a new link in the toolbox in order to allow the users to access the upload irods page
            $toolbox['specialirodsuploadpage'] = array(
                'href' => "$wgScript/Special:SpecialIrodsUploadPage",
                'id' => 't-SpecialIrodsUploadPage'
            );
            $toolbox['specialirodscollectionbrowser'] = array(
                'href' => "$wgScript/Special:SpecialIrodsCollectionBrowser",
                'id' => 't-SpecialIrodsCollectionBrowser'
            );
        }
        return true;
    }
}

