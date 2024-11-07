<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");
session_start();
is_session_set();

if (isset($_POST["name"])) {
    $name = $_POST["name"];
} else {
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Ensure the directory is writable and user is allowed to modify
if ($_SESSION["is_dir_writable"]) {
    // Validate directory name
    if (strpbrk($name, "\\/?%*:|\"<>") !== false || strpos($name, '..') !== false || $name === '') {
        header('HTTP/1.0 400 Bad Request');
        die();
    }

    // Prevent directory traversal
    if (strpos($name, '/') !== false || strpos($name, '\\') !== false) {
        header('HTTP/1.0 400 Bad Request');
        die();
    }

    // Check if directory already exists
    $fullPath = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/" . $name;
    if (file_exists($fullPath)) {
        header('HTTP/1.1 409 Conflict');
        die();
    }

    // Create the directory
    if (mkdir($fullPath, 0770)) {
        header('HTTP/1.1 200 OK');
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        die('Failed to create directory');
    }
} else {
    header('HTTP/1.0 403 Forbidden');
    die();
}
