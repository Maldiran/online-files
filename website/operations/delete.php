<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set();

$fullpaths = get_fullpaths();

if ($_SESSION["is_dir_writable"]) {
    foreach ($fullpaths as $fullpath) {
        if (file_exists($fullpath)) {
            rrm($fullpath);
        } else {
            header('HTTP/1.0 404 Not Found');
            die();
        }
    }
    header("HTTP/1.1 200 OK");
} else {
    header('HTTP/1.0 403 Forbidden');
    die();
}

// Recursive function to delete files and directories securely
function rrm($path) {
    // Ensure the path is within the allowed user directory
    $realBase = realpath($_SESSION["user_root"] . $_SESSION["user_dir"]);
    $realPath = realpath($path);

    if ($realPath === false || strpos($realPath, $realBase) !== 0) {
        // Attempt to access a file outside of the allowed directory
        header('HTTP/1.0 403 Forbidden');
        die();
    }

    if (is_dir($realPath)) {
        $objects = array_diff(scandir($realPath), array('.', '..'));
        foreach ($objects as $object) {
            $currentPath = $realPath . DIRECTORY_SEPARATOR . $object;
            rrm($currentPath);
        }
        if (!rmdir($realPath)) {
            header('HTTP/1.0 500 Internal Server Error');
            die();
        }
    } else {
        if (!unlink($realPath)) {
            header('HTTP/1.0 500 Internal Server Error');
            die();
        }
    }
}
