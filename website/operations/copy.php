<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set();

if (!isset($_POST["cut"])) {
    header('HTTP/1.0 400 Bad Request');
    die('');
}

$cut = ($_POST["cut"] === 'true') ? true : false;

$fullpaths = get_fullpaths(); // Retrieves the full paths of the files to copy or cut

if ($cut && !$_SESSION["is_dir_writable"]) {
    header('HTTP/1.0 403 Forbidden');
    die('');
}

// Store the operation details in the session
$_SESSION["copy_cut"] = $cut;
$_SESSION["copy_fullpaths"] = $fullpaths;

header("HTTP/1.1 200 OK");
