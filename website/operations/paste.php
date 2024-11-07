<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set();

if (!isset($_SESSION["copy_fullpaths"]) || !isset($_SESSION["copy_cut"])) {
    header('HTTP/1.0 400 Bad Request');
    die('');
}

if (!$_SESSION["is_dir_writable"]) {
    header('HTTP/1.0 403 Forbidden');
    die('');
}

foreach ($_SESSION["copy_fullpaths"] as $fullpath) {
    $basename = basename($fullpath);
    $newpath = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/" . $basename;

    if (!file_exists($fullpath)) {
        header('HTTP/1.0 404 Not Found');
        die('');
    }

    if (file_exists($newpath)) {
        header('HTTP/1.1 409 Conflict');
        die('');
    }

    // Ensure that newpath is within the user's directory
    $realBase = realpath($_SESSION["user_root"] . $_SESSION["user_dir"]);
    $realNewPath = realpath(dirname($newpath));

    if ($realNewPath === false || strpos($realNewPath, $realBase) !== 0) {
        header('HTTP/1.0 403 Forbidden');
        die('');
    }

    if ($_SESSION["copy_cut"]) {
        // Move the file or directory
        if (!rename($fullpath, $newpath)) {
            header('HTTP/1.0 500 Internal Server Error');
            die('');
        }
    } else {
        if (is_dir($fullpath)) {
            if (!rcopy($fullpath, $newpath)) {
                header('HTTP/1.0 500 Internal Server Error');
                die('');
            }
        } else {
            if (!copy($fullpath, $newpath)) {
                header('HTTP/1.0 500 Internal Server Error');
                die('');
            }
        }
    }
}

if ($_SESSION["copy_cut"]) {
    // Clear the copy/cut session variables
    unset($_SESSION["copy_fullpaths"]);
    unset($_SESSION["copy_cut"]);
    echo "true";
} else {
    echo "false";
}

// Recursive function to copy directories
function rcopy($source, $dest) {
    $dir = opendir($source);
    if (!@mkdir($dest)) {
        return false;
    }

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $srcPath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $dest . DIRECTORY_SEPARATOR . $file;

            if (is_dir($srcPath)) {
                if (!rcopy($srcPath, $destPath)) {
                    closedir($dir);
                    return false;
                }
            } else {
                if (!copy($srcPath, $destPath)) {
                    closedir($dir);
                    return false;
                }
            }
        }
    }
    closedir($dir);
    return true;
}
