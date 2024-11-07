<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");
session_start();
is_session_set(false); // Start session without CSRF verification
$file = get_file('GET'); // Get the file parameter from GET data

// Verify that the file exists in the session contents
if (is_int($file) && array_key_exists($file, $_SESSION["contents"])) {
    $filename = $_SESSION["contents"][$file];
    $path = $_SESSION["user_dir"] . "/" . $filename;
    $fullpath = $_SESSION["user_root"] . $path;
} else {
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Check if the path is a file and not a directory
if (!is_dir($fullpath)) {
    $mimetype = mime_content_type($fullpath);
    $parts = explode('/', $mimetype);
    // Handle video or audio streaming
    if ($parts[0] === 'video' || $parts[0] === 'audio') {
        include(HTLOCKED_PATH . "/VideoStream.php");
        $stream = new VideoStream($fullpath);
        $stream->start($mimetype);
    }
    // Handle image files
    elseif ($parts[0] === 'image') {
        header('Content-Type: ' . $mimetype);
        header('Cache-Control: public, max-age=86400');
        header('Content-Disposition: inline; filename="' . basename($fullpath) . '"');
        header('Content-Length: ' . filesize($fullpath));
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
        readfile($fullpath);
    }
    // Handle other file types
    else {
        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: inline; filename="' . basename($fullpath) . '"');
        header('Content-Length: ' . filesize($fullpath));
        header('Pragma: no-cache');
        header('Expires: 0');
        ob_clean();
        flush();
        readfile($fullpath);
    }
} else {
    header('HTTP/1.0 403 Forbidden');
    die();
}
