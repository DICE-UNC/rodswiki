<?php

// For security reasons;
if (!defined('MEDIAWIKI')) {
    die('Invalid entry point');
}

// global variable used by the other extensions to check if this extension is installed
$RodsWiki = true;

require_once(__DIR__ . "/RodsWiki.config.php");

$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'author' => 'Alexandru Nedelcu, Jacob Olitsky',
    'name' => 'RodsWiki',
    'version' => '0.4.0',
    'descriptionmsg' => 'rodswiki-desc',
    'url' => 'https://bitbucket.org/drexel/rodswiki',
    'license-name' => 'BSD'
);

// Definition of magic words
$wgExtensionMessagesFiles['RodsWikiMagic'] = __DIR__ . '/RodsWiki.i18n.magic.php';

// Special page that displays metadata for a given IRODS file
$wgAutoloadClasses['SpecialRodsWiki'] = __DIR__ . '/SpecialIrodsMetadataPage.php';
$wgExtensionMessagesFiles['RodsWiki'] = __DIR__ . '/RodsWiki.i18n.php';
$wgExtensionMessagesFiles['RodsWikiAlias'] = __DIR__ . '/RodsWiki.alias.php';
// Define the class in the special page
$wgSpecialPages['IrodsMetadataPage'] = 'SpecialRodsWiki';

// Special Page for upload
$wgAutoloadClasses['SpecialIrodsUploadPage'] = __DIR__ . '/SpecialIrodsUploadPage.php';
$wgSpecialPages['SpecialIrodsUploadPage'] = 'SpecialIrodsUploadPage';

// Special Page for iRODS user management
$wgAutoloadClasses['SpecialIrodsUserManagement'] = __DIR__ . '/SpecialIrodsUserManagement.php';
$wgSpecialPages['SpecialIrodsUserManagement'] = 'SpecialIrodsUserManagement';

// Special Page for iRODS File Download
$wgAutoloadClasses['SpecialIrodsFileDownload'] = __DIR__ . '/SpecialIrodsFileDownload.php';
$wgSpecialPages['SpecialIrodsFileDownload'] = 'SpecialIrodsFileDownload';

// Special Page for Collection Browser
$wgAutoloadClasses['SpecialIrodsCollectionBrowser'] = __DIR__ . '/SpecialIrodsCollectionBrowser.php';
$wgSpecialPages['SpecialIrodsCollectionBrowser'] = 'SpecialIrodsCollectionBrowser';

// Implementing the {{#irods:image.jpg}} tag
$wgHooks['ParserFirstCallInit'][] = 'RodsWikiParsers::irodsExtensionSetupParserFunction';
$wgHooks['BaseTemplateToolbox'][] = 'RodsWikiHooks::toolboxAddUploadPage'; // include in toolbox

// Including the other files
require_once 'irods/Prods.inc.php';
require_once 'RodsWiki.functions.php';
require_once 'RodsWiki.hooks.php';
require_once 'RodsWiki.parsers.php';

