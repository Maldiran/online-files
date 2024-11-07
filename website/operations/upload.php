<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set(); // Verify session and CSRF token

// Check if the upload form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 400 Bad Request');
    die('Invalid request method');
}

// Check if files were uploaded
if (!isset($_FILES["files"])) {
    header('HTTP/1.0 400 Bad Request');
    die('No files uploaded');
}

// Check if the user has permission to write in the directory
if (!isset($_SESSION["is_dir_writable"]) || !$_SESSION["is_dir_writable"]) {
    header('HTTP/1.0 403 Forbidden');
    die('Permission denied');
}

$target_dir = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/";
$total_files = count($_FILES["files"]["name"]);

// Maximum allowed file size (20GB in bytes)
$max_file_size = 20 * 1024 * 1024 * 1024; // 20GB

for ($i = 0; $i < $total_files; $i++) {
    $file_name = $_FILES["files"]["name"][$i];

    // Validate the file name
    if (strpbrk($file_name, "\\/?%*:|\"<>") !== false || strpos($file_name, '..') !== false || $file_name === '') {
        header('HTTP/1.0 400 Bad Request');
        die('Invalid file name');
    }

    // Ensure the file name does not contain slashes or backslashes
    if (strpos($file_name, '/') !== false || strpos($file_name, '\\') !== false) {
        header('HTTP/1.0 400 Bad Request');
        die('Invalid file name');
    }

    // Use basename to get the base file name
    $file_name = basename($file_name);

    $target_file = $target_dir . $file_name;

    // Check if the target file path is within the user's allowed directory
    $realBase = realpath($_SESSION["user_root"] . $_SESSION["user_dir"]);
    $realTarget = realpath(dirname($target_file));

    if ($realTarget === false || strpos($realTarget, $realBase) !== 0) {
        header('HTTP/1.0 403 Forbidden');
        die('Invalid target path');
    }

    // Check if a file with the same name already exists
    if (file_exists($target_file)) {
        header('HTTP/1.1 409 Conflict');
        die('File already exists');
    }

    // Check file size (limit to 20GB)
    if ($_FILES["files"]["size"][$i] > $max_file_size) {
        header('HTTP/1.1 413 Payload Too Large');
        die('File size exceeds the maximum limit');
    }

    // Verify that the uploaded file is valid
    if (is_uploaded_file($_FILES["files"]["tmp_name"][$i])) {
        // Attempt to move the uploaded file to the target directory
        if (!move_uploaded_file($_FILES["files"]["tmp_name"][$i], $target_file)) {
            // Failed to move the uploaded file
            header('HTTP/1.1 500 Internal Server Error');
            die('Failed to move uploaded file');
        }
    } else {
        // Handle partial or invalid file upload
        if (file_exists($_FILES["files"]["tmp_name"][$i])) {
            unlink($_FILES["files"]["tmp_name"][$i]); // Delete the temp file
        }
        header('HTTP/1.1 400 Bad Request');
        die('Invalid file upload');
    }
}

header('HTTP/1.1 200 OK');
