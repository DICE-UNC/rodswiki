<?php
/**
 * Copyright 2013 Drexel University
 */
class RodsWikiParsers
{
    // Tell MediaWiki that the parser function exists.
    public static function irodsExtensionSetupParserFunction(&$parser)
    {
        // Create a function hook associating the "irods" magic word with the
        // rodsWikiRenderParserFunction() function.
        $parser->setFunctionHook('irods', 'RodsWikiParsers::rodsWikiRenderParserFunction');

        // Return true so that MediaWiki continues to load extensions.
        return true;
    }

    // Render the output of the parser function.
    // Specifically: it renders a link to the special page from this extention
    // which shows metadata of the IRODS file.
    // There the file could be downloaded
    public static function rodsWikiRenderParserFunction($parser, $param1 = '')
    {
        global $wgScriptPath, $wgRodsWikiDefaultIrodsRootPath;
        require_once 'RodsWiki.functions.php';

        $server_name = filter_input(INPUT_SERVER, 'SERVER_NAME');
        $server_port = filter_input(INPUT_SERVER, 'SERVER_PORT');

//        $param1 = filenameToLegalCharacters($param1);
        try {
            $basename = basename($param1);
//            if (substr($param1, 0, 1) == '/') { // check for iRODS full path
                //$output = "[http://$server_name:$server_port$wgScriptPath/index.php/Special:IrodsMetadataPage?filename=$param1 $basename] \n";
		$output = "[http://$server_name:$server_port$wgScriptPath/index.php/Special:IrodsMetadataPage?filename=" . rawurlencode($param1) . " $basename] \n";
  //          } else {
    //            $wiki_hash_file = $wgRodsWikiDefaultIrodsRootPath . '/' . getWikiFilePath($param1);
      //          $output = "[http://$server_name:$server_port$wgScriptPath/index.php/Special:IrodsMetadataPage?filename=$wiki_hash_file $param1]\n";
        //    }
        } catch (Exception $e) {
            $output = 'Error connecting to iRODS server.';
        }

        return array(
            $output,
            'noparse' => false,
            'isHTML' => false,
            'no_files' => 1,
            'file_path' => 'where does this get used' //TODO: do I need all these args?
        );
    }
}

