<?php
// Regular linux umask that sets permissions for newly created files
umask(0007);

// Path to htlocked folder, can be outside webroot
define('HTLOCKED_PATH', $_SERVER['DOCUMENT_ROOT'] . '/htlocked');

// MYSQL credentials
const MYSQL = array(
    'server'   => '127.0.0.1',
    'user'     => 'files',
    'password' => 'password',
    'dbname'   => 'files',
);
