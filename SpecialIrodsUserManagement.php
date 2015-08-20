<?php

/**
 * Copyright 2013 Drexel University
 */
class SpecialIrodsUserManagement extends SpecialPage
{
    public function __construct()
    {
        require_once dirname(__FILE__) . "/RodsWiki.functions.php";
        parent::__construct('SpecialIrodsUserManagement');
    }
    public function execute($par)
    {
        global $wgOut, $wgUser, $wgDBprefix, $wgScriptPath, $wgRodsWikiIrodsHost, $wgRodsWikiIrodsServerPort;
        $this->setHeaders();

        if ($wgUser->mId == 0) {
            die("Access restricted");
        }

        $dbr = wfGetDB(DB_SLAVE);
        $dbw = wfGetDB(DB_MASTER);
        $query = "CREATE TABLE IF NOT EXISTS {$wgDBprefix}irods_users (wiki_user_id int(5) NOT null PRIMARY KEY,";
        $query .= 'irods_user_name varchar(64) NOT null,';
        $query .= 'irods_user_password varchar(64) NOT null,';
        $query .= 'irods_user_root_path varchar(128) NOT null,';
        $query .= 'irods_user_resource varchar(128) NOT null,';
        $query .= 'irods_user_zone varchar(64) NOT null,';
        $query .= 'irods_custom_account_active tinyint(1) NOT null DEFAULT 0';
        $query .= ');';
        $dbw->query($query);

        $user_info = array();
        $user_info['wiki_user_id'] = $wgUser->mId;

        // add iRODS user
        $setting = filter_input(INPUT_POST, 'setting');
        if (isset($setting)) {
            $user_info['irods_user_name'] = filter_input(INPUT_POST, 'irods_user_name');
            $user_info['irods_user_password'] = filter_input(INPUT_POST, 'irods_user_password');
            $user_info['irods_user_root_path'] = filter_input(INPUT_POST, 'irods_user_root_path');
            $user_info['irods_user_resource'] = filter_input(INPUT_POST, 'irods_user_resource');
            $user_info['irods_user_zone'] = filter_input(INPUT_POST, 'irods_user_zone');
            $user_info['irods_custom_account_active'] = filter_input(INPUT_POST, 'setting');

            if ($this->checkConnection(
                $wgRodsWikiIrodsHost,
                $wgRodsWikiIrodsServerPort,
                $user_info['irods_user_name'],
                $user_info['irods_user_password']
            )) {
                $dbw->delete("irods_users", array("wiki_user_id" => $wgUser->mId));
                $dbw->insert("irods_users", $user_info);
                $wgOut->addHtml('<p style="color: green; font-size: 18px; font-weight: bold">Account credentials updated</p>');
            } else {
                $wgOut->addHtml('<p style="color: red; font-size: 18px; font-weight: bold">Invalid account credentials</p>');
            }
        } else {
            $row_curr_user = $dbr->selectRow(
                array('irods_users'),
                array('irods_user_name, irods_user_password, irods_user_root_path, irods_user_resource, irods_user_zone, irods_custom_account_active'),
                array('wiki_user_id' => $wgUser->mId)
            );
            if ($row_curr_user === false) {
                $user_info['irods_user_name'] = '';
                $user_info['irods_user_password'] = '';
                $user_info['irods_user_root_path'] = '';
                $user_info['irods_user_resource'] = '';
                $user_info['irods_user_zone'] = '';
                $user_info['irods_custom_account_active'] = '';
            } else {
                $user_info['irods_user_name'] = $row_curr_user->irods_user_name;
                $user_info['irods_user_password'] = $row_curr_user->irods_user_password;
                $user_info['irods_user_root_path'] = $row_curr_user->irods_user_root_path;
                $user_info['irods_user_resource'] = $row_curr_user->irods_user_resource;
                $user_info['irods_user_zone'] = $row_curr_user->irods_user_zone;
                $user_info['irods_custom_account_active'] = $row_curr_user->irods_custom_account_active;
            }
        }

        $wgOut->addWikiText('== User rights ==');
        $wgOut->addWikiText("Current MediaWiki user: {$wgUser->mName}");
        $wgOut->addWikiText('=== Linked iRODS Account ===');

        $server_name = filter_input(INPUT_SERVER, 'SERVER_NAME');
        $server_port = filter_input(INPUT_SERVER, 'SERVER_PORT');
        $request_uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        $output = "<form method='POST' id='form_change_user' action='http://$server_name:$server_port$request_uri'>";
        $output .= '<input type="radio" value="0" name="setting" id="setting_def"';
        if ($user_info['irods_custom_account_active'] == 0) {
            $output .= ' checked';
        }
        $output .= '> Use the default iRODS Account<br>';
        $output .= '<input type="radio" value="1" name="setting" id="setting_cus"';
        if ($user_info['irods_custom_account_active'] != 0) {
            $output .= ' checked';
        }
        $output .= '> Use a custom iRODS Account';
        $output .= '<table id="custom_user"';
        if ($user_info['irods_custom_account_active'] == 0) {
            $output .= ' style="display:none"';
        }
        $output .= <<<OUTPUT
        >
        <tr>
            <td>User name:</td>
            <td><input type='text' value='{$user_info['irods_user_name']}' name='irods_user_name' id='irods_user_name'></td>
        </tr>
        <tr>
            <td>Password:</td>
            <td><input type='password' value='{$user_info['irods_user_password']}' name='irods_user_password' id='irods_user_password'></td>
        </tr>
        <tr>
            <td>Root path:</td>
            <td><input type=\"text\" value='{$user_info['irods_user_root_path']}' name=\"irods_user_root_path\" id=\"irods_user_root_path\"></td>
        </tr>
        <tr>
            <td>Resource:</td> <td><input type=\"text\" value='{$user_info['irods_user_resource']}' name=\"irods_user_resource\" id=\"irods_user_resource\"></td></tr>
        <tr>
            <td>Zone:</td>
            <td><input type='text' value='{$user_info['irods_user_zone']}' name='irods_user_zone' id='irods_user_zone'></td>
        </tr>
    </table>
    <br/>
    <input type="submit" value="Save iRODS account">
</form>
<script src='$wgScriptPath/extensions/RodsWiki/SpecialUserManage.js'></script>
OUTPUT;
        $wgOut->addHTML($output);
    }
    public static function checkConnection($configIrodsHost, $configIrodsPort, $configIrodsUser, $configIrodsPass)
    {
        $IrodsAccount = new RODSAccount($configIrodsHost, $configIrodsPort, $configIrodsUser, $configIrodsPass);
        try {
            $IrodsAccount->getUserInfo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

