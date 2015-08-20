<?php
/**
 * Copyright 2013 Drexel University
 */
if (!defined('MEDIAWIKI')) {
    die('Invalid entry point');
}
if (!file_exists(dirname(__FILE__) . "/installed")) {
    global $wgDBprefix, $wgRodsWikiDefaultIrodsRootPath;

    // create table irods_users for user permissions management
    $dbw = wfGetDB(DB_MASTER);
    $dbw->query(
        'CREATE TABLE IF NOT EXISTS ' . $wgDBprefix . 'irods_users (
            wiki_user_id int(5) NOT null PRIMARY KEY,
            irods_user_name varchar(64) NOT null,
            irods_user_password varchar(64) NOT null,
            irods_user_root_path varchar(128) NOT null,
            irods_user_resource varchar(128) NOT null,
            irods_user_zone varchar(64) NOT null,
            irods_custom_account_active tinyint(1) NOT null DEFAULT 0
        );'
    );
    $dbw->query(
        'CREATE TABLE IF NOT EXISTS `' . $wgDBprefix . 'irods_tickets` (
            `irods_ticket_string` varchar(64) NOT null,
            `irods_file_url` varchar(128) NOT null,
            `irods_ticket_creation` timestamp NOT null DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );'
    );

    if (!file_exists(dirname(__FILE__) . "/installed")) {
        touch(dirname(__FILE__) . "/installed");
    }
}

