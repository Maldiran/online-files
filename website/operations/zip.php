<?php
require $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php'; 
use ZipStream\ZipStream;
use ZipStream\OperationMode;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set(); // Verify session and CSRF token

$fullpaths = get_fullpaths(); // Retrieve full paths from POST data

$elements_count = count($fullpaths);

// Determine the filename for the zip archive
if ($elements_count == 1) {
    $filename = basename(current($fullpaths));
} elseif ($elements_count > 1) {
    $filename = basename($_SESSION["user_dir"]);
} else {
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Validate the filename to prevent security issues
if (strpbrk($filename, "\\/?%*:|\"<>") !== false || strpos($filename, '..') !== false || $filename === '') {
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Check if the directory is writable
if (!isset($_SESSION["is_dir_writable"]) || !$_SESSION["is_dir_writable"]) {
    header('HTTP/1.0 403 Forbidden');
    die();
}

$zip_file_path = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/" . $filename . ".zip";

// Check if a file with the same name already exists
if (file_exists($zip_file_path)) {
    header('HTTP/1.1 409 Conflict');
    die();
}

// Open the zip file for writing
$zip_handler = fopen($zip_file_path, 'w');
if ($zip_handler === false) {
    header('HTTP/1.1 500 Internal Server Error');
    die();
}

$zip = new ZipStream(
    outputStream: $zip_handler,
);

foreach ($fullpaths as $fullpath) {
    // Check if the file exists and is readable
    if (!file_exists($fullpath) || !is_readable($fullpath)) {
        fclose($zip_handler);
        header('HTTP/1.1 404 Not Found');
        die();
    }

    if (is_dir($fullpath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullpath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            if ($elements_count !== 1) {
                $relativePath = basename($fullpath) . "/" . substr($filePath, strlen(realpath($fullpath)) + 1);
            } else {
                $relativePath = substr($filePath, strlen(realpath($fullpath)) + 1);
            }

            if (is_file($filePath)) {
                $zip->addFileFromPath($relativePath, $filePath);
            }
        }
    } elseif (is_file($fullpath)) {
        $zip->addFileFromPath(basename($fullpath), $fullpath);
    } else {
        fclose($zip_handler);
        header('HTTP/1.0 400 Bad Request');
        die();
    }
}

$zip->finish();
fclose($zip_handler);

header('HTTP/1.1 200 OK');
