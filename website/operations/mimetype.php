<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");
session_start();
is_session_set(); // CSRF verification is performed

$file = get_file(); // Get the file parameter from POST data

// Verify that the file exists in the session contents
if (is_int($file) && array_key_exists($file, $_SESSION["contents"])) {
    $filename = $_SESSION["contents"][$file];
    $path = $_SESSION["user_dir"] . "/" . $filename;
    $fullpath = $_SESSION["user_root"] . $path;
} else {
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Check if the path is a file and not a directory, and is readable
if (!is_dir($fullpath) && is_readable($fullpath)) {
    $mimetype = mime_content_type($fullpath);
    if ($mimetype === false) {
        header('HTTP/1.0 500 Internal Server Error');
        die();
    }
    // Sanitize MIME type output
    echo htmlspecialchars($mimetype, ENT_QUOTES, 'UTF-8');
} else {
    header('HTTP/1.0 403 Forbidden');
    die();
}
