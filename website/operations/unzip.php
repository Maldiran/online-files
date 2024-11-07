<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set();

$fullpaths = get_fullpaths();

if ($_SESSION["is_dir_writable"]) {
    foreach ($fullpaths as $fullpath) {
        // Check if the file exists and is readable
        if (!file_exists($fullpath) || !is_readable($fullpath)) {
            header("HTTP/1.1 404 Not Found");
            die();
        }

        // Ensure the file is a valid zip file
        $zip = new ZipArchive();
        if ($zip->open($fullpath) !== TRUE) {
            header("HTTP/1.1 500 Internal Server Error");
            die();
        }

        // Generate a safe directory name for extraction
        $name = pathinfo($fullpath, PATHINFO_FILENAME);

        // Validate the directory name
        if (strpbrk($name, "\\/?%*:|\"<>") !== false || strpos($name, '..') !== false || $name === '') {
            $zip->close();
            header('HTTP/1.0 400 Bad Request');
            die();
        }

        $destpath = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/" . $name;

        // Check if the destination directory already exists
        if (file_exists($destpath)) {
            $zip->close();
            header('HTTP/1.1 409 Conflict');
            die();
        }

        // Ensure the destination path is within the allowed directory
        $realBase = realpath($_SESSION["user_root"] . $_SESSION["user_dir"]);
        $realDest = realpath(dirname($destpath));

        if ($realDest === false || strpos($realDest, $realBase) !== 0) {
            $zip->close();
            header('HTTP/1.0 403 Forbidden');
            die();
        }

        // Create the destination directory
        if (!mkdir($destpath, 0770, true)) {
            $zip->close();
            header("HTTP/1.1 500 Internal Server Error");
            die();
        }

        // Extract the zip archive to the destination directory
        if ($zip->extractTo($destpath) !== TRUE) {
            $zip->close();
            header("HTTP/1.1 500 Internal Server Error");
            die();
        }
        $zip->close();
    }
    header("HTTP/1.1 200 OK");
} else {
    header('HTTP/1.0 403 Forbidden');
    die();
}
