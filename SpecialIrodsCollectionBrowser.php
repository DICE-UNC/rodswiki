<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SpecialIrodsCollectionBrowser extends SpecialPage
{
    private $userHomeDir;
    private $irodsAccount;
    private $currentDir;

    public function __construct()
    {
        require_once __DIR__ . '/RodsWiki.functions.php';
        parent::__construct('SpecialIrodsCollectionBrowser');
    }

    public function execute($par)
    {
        global $wgOut, $wgScriptPath;
        $tmpOut = '';
        $IrodsAccount = getAccount();
        $this->irodsAccount = $IrodsAccount;
        $this->userHomeDir = new ProdsDir(
            $IrodsAccount,
            "/{$this->irodsAccount->zone}/home/{$this->irodsAccount->user}"
        );
        $this->currentDir = $this->userHomeDir;
        $this->setHeaders();
        $inputPath = filter_input(INPUT_GET, 'path');
        if (isset($inputPath)) {
            $this->currentDir = new ProdsDir($IrodsAccount, $inputPath);
        }
        // Accounting for when the current dir is "/"
        $parentPath = $this->currentDir->getParentPath();
        if ($parentPath == '\\') {
            // getParentPath() returns "\" instead of "/" on Windows.
            $parentPath = '/';
        }
        $currentPath = $this->currentDir->getPath();
        if (isset($parentPath)) {
            $tmpOut .= "*<span class='plainlinks irodsCollection'>[{{fullurl:Special:{{PAGENAMEE}}|path=$parentPath}} &#8593; up]</span>\n";
        }
        try {
            $childDirArray = $this->currentDir->getChildDirs();
            // Local function to compare ProdsDir objects by their names.
            function compareProdsDirs(ProdsDir $a, ProdsDir $b) {
                return strnatcasecmp($a->getName(), $b->getName());
            }
            // Use function to natural sort collections by names.
            // Note: usort sorts in place.
            usort($childDirArray, 'compareProdsDirs');
            foreach ($childDirArray as $childDir) {
                $tmpOut .= '*<span class="plainlinks irodsCollection">' . $this::buildDirLink($childDir) . "</span>\n";
            }
            $childFileArray = $this->currentDir->getChildFiles();
            // Local function to compare ProdsFile objects by their names.
            // Replica of compareProdDirs in order to use type hinting.
            function compareProdsFiles(ProdsFile $a, ProdsFile $b) {
                return strnatcasecmp($a->getName(), $b->getName());
            }
            // Use function to natural sort files by names.
            // Note: usort sorts in place.
            usort($childFileArray, 'compareProdsFiles');
            foreach ($childFileArray as $childFile) {
                $modifiedTime = date('Y/m/d H:i:s T', $childFile->stats->mtime);
                $fileSize = getFormattedSize($childFile->stats->size);
                $tmpOut .= "*<span class='irodsFile'>&emsp;[{{fullurl:Special:IrodsMetadataPage|filename=";
                $tmpOut .= rawurlencode($childFile->getPath()) . '}} ';
                $tmpOut .= urlencode($childFile->getName()) . "]";
                $tmpOut .= "&emsp;Modified:&nbsp;$modifiedTime&emsp;$fileSize";
                $tmpOut .= "&emsp;Owner:&nbsp;{$childFile->stats->owner}</span>\n";
            }
        } catch (Exception $e) {
            $tmpOut = "=== Error connecting to iRODS server. ===\n\nPlease try again later.";
        }
        $wgOut->addWikiText("=== $currentPath ===");
        $wgOut->addWikiText($tmpOut);
        $wgOut->addWikiText("== Link to this page in WikiText==");
        $wgOut->addHTML('<pre>{{IrodsCollection|' . $currentPath . '}}</pre>'); // For file link
        $wgOut->addHTML('<link rel="stylesheet" href=' . htmlSafe($wgScriptPath) . '/extensions/RodsWiki/SpecialCollectionBrowser.css>');
    }

    // IMPORTANT: Make sure you know $dirToLink is sanitized.
    // It should only be used internally.
    private function buildDirLink(ProdsDir $dirPath)
    {
        return '[{{fullurl:Special:{{PAGENAMEE}}|path=' . rawurlencode($dirPath->getPath()) . '}} ' . $dirPath->getName() . ']';
    }
}

