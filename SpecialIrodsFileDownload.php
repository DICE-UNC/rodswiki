<?php
class SpecialIrodsFileDownload extends SpecialPage
{
    public function __construct()
    {
        $dir = dirname(__FILE__) . '/';
        require_once $dir . "RodsWiki.functions.php";

        parent::__construct('SpecialIrodsFileDownload', '', false);
    }
    public function execute($par)
    {
        global $wgRodsWikiDefaultIrodsRootPath, $wgOut;
        $this->setHeaders();
        require_once dirname(__FILE__) . "/RodsWiki.functions.php";
        require_once dirname(__FILE__) . "/RodsWiki.install.php";
        $fileparam = filter_input(INPUT_GET, 'filename');
        if (!isset($fileparam)) {
            die("Missing required filename parameter");
        }

        //extract the name of the file, and the full path
        $filename = basename($fileparam);
        if (substr($fileparam, 0, 1) == "/") {
            $fullpath = $fileparam;
        } else {
            $fullpath = $wgRodsWikiDefaultIrodsRootPath . "/" . getWikiFilePath($filename);
        }
        
        $IrodsAccount = getAccount();
        $myfile = new ProdsFile($IrodsAccount, $fullpath);

        $exists = filter_input(INPUT_GET, 'exists');
        if (isset($exists)) {
            try {
                if ($myfile->exists()) {
                    die('1');
                } else {
                    die('0');
                }
            } catch (Exception $e) {
                if ($e->getCode() === -3105000) {
                    die('2');
                } elseif ($e->getCode() === -1000) {
                    die('Could not connect to iRODS server');
                } else {
                    die((string)$e->getCode());
                }
            }
        }

        //check if the file exists
        if (! $myfile->exists()) {
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            die();
        }

        try {
            $myfile->open("r");
            $fileinfo = $myfile->getStats();
            //Begin writing headers
            ob_start();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            //Use the switch-generated Content-Type
            header("Content-Type: application/octet-stream");//$ctype");
            //Force the download
            $header = "Content-Disposition: attachment; filename=\"" . $filename . "\";";
            header($header);
            header("Content-Transfer-Encoding: binary");
            header('Last-Modified: ' . date('r'));
            header("Content-Length: {$fileinfo->size}");
            ob_clean();
            flush();
            // move the cursor to the begining of the file
            $myfile->rewind();  //TODO: why is this here? the cursor hasnt been moved
            // streaming chunks
            // Variable is updated at every 1MB read from the server
            //TODO: larger chunks? do I really need all of those flushes so often?
            while ($str = $myfile->read(10485760)) {
                print($str);
                ob_flush();
                flush();
            }
            ob_end_clean();
            $myfile->close();
        } catch (Exception $e) {
            if ($e->getCode() == -818000) {
                $output = $this->getOutput();
                //$output->showErrorPage('error', 'badarticleerror');
                $output->setPagetitle('Permission Error');
                $output->addWikiText('This iRODS account does not have the right to read this file.');
                $output->addHTML('<input action="action" type="button" value="Go back" onclick="window.history.back();" />');
            }
        }
    }
}

