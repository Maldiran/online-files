<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set();

$fullpaths = get_fullpaths();

if (count($fullpaths) !== 1 || !isset($_POST["name"])) {
    header('HTTP/1.0 400 Bad Request');
    die();
} else {
    $name = $_POST["name"];
}

// Check if directory is writable
if ($_SESSION["is_dir_writable"]) {
    $fullpath = current($fullpaths);

    // Validate the new name
    if (strpbrk($name, "\\/?%*:|\"<>") !== false || strpos($name, '..') !== false || $name === '') {
        header('HTTP/1.0 400 Bad Request');
        die();
    }

    // Prevent directory traversal
    if (strpos($name, '/') !== false || strpos($name, '\\') !== false) {
        header('HTTP/1.0 400 Bad Request');
        die();
    }

    // Check if a file or directory with the new name already exists
    $newpath = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/" . $name;
    if (file_exists($newpath)) {
        header('HTTP/1.1 409 Conflict');
        die();
    }

    // Attempt to rename the file or directory
    if (!rename($fullpath, $newpath)) {
        header('HTTP/1.0 500 Internal Server Error');
        die();
    }

    header("HTTP/1.1 200 OK");
} else {
    header('HTTP/1.0 403 Forbidden');
    die();
}
