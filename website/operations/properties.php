<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set();

$file = get_file(); // Get the file parameter from POST data

// Verify that the file exists in the session contents
if (is_int($file) && array_key_exists($file, $_SESSION["contents"])) {
    $fullpath = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/" . $_SESSION["contents"][$file];
} else {
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Ensure the file or directory exists and is accessible
if (!file_exists($fullpath)) {
    header('HTTP/1.0 404 Not Found');
    die();
}

// Collect properties
$properties = array();
$properties["name"] = basename($fullpath);
$properties["location"] = $_SESSION["user_dir"];
$properties["dir"] = is_dir($fullpath);
$properties["read"] = is_readable($fullpath);
$properties["write"] = is_writable($fullpath) && $_SESSION["user_allow_modify"];

// Calculate size
if ($properties["dir"]) {
    $properties["size"] = GetDirectorySize($fullpath);
} else {
    $properties["size"] = filesize($fullpath);
}

// Get modification time
$properties["modification"] = filemtime($fullpath);

// Sanitize output to prevent XSS attacks
$properties = array_map(function($value) {
    if (is_bool($value) || is_numeric($value)) {
        return $value;
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}, $properties);

echo json_encode($properties);

// Function to calculate directory size
function GetDirectorySize($path) {
    $bytestotal = 0;
    $path = realpath($path);
    if ($path !== false && $path != '' && file_exists($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $bytestotal += $object->getSize();
        }
    }
    return $bytestotal;
}
