<?php
require $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php';

use ZipStream\ZipStream;
use ZipStream\OperationMode;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include(HTLOCKED_PATH . "/initial_functions.php");

session_start();
is_session_set(false); // CSRF verification is skipped for file downloads

$fullpaths = get_fullpaths('GET'); // Retrieve the full paths of requested files

$elements_count = count($fullpaths);

if ($elements_count == 1) {
    $fullpath = current($fullpaths);
    if (is_dir($fullpath)) {
        zipStreamDirectory($fullpath, basename($fullpath));
    } else {
        downloadFile($fullpath, basename($fullpath));
    }
} elseif ($elements_count > 1) {
    zipStreamMultiple($fullpaths, 'download');
} else {
    header('HTTP/1.0 400 Bad Request');
    die('No files specified');
}

// Function to create a zip stream for a directory
function zipStreamDirectory($directoryPath, $filename) {
    $zip = new ZipStream(
        outputName: $filename . ".zip",
        sendHttpHeaders: true,
    );

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        $filePath = $file->getPathname();
        $relativePath = substr($filePath, strlen(realpath($directoryPath)) + 1);

        if (is_file($filePath))
            $zip->addFileFromPath($relativePath, $filePath);
    }

    $zip->finish();
}

// Function to create a zip stream for multiple files/directories
function zipStreamMultiple($fullpaths, $filename) {
    $zip = new ZipStream(
        outputName: $filename . ".zip",
        sendHttpHeaders: true,
    );

    foreach($fullpaths as $fullpath) {
        if(is_dir($fullpath)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullpath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $filePath = $file->getPathname();
                $relativePath = basename($fullpath) . "/" . substr($filePath, strlen(realpath($fullpath)) + 1);

                if (is_file($filePath))
                    $zip->addFileFromPath($relativePath, $filePath);
            }
        }
        else if (is_file($fullpath)) {
            $zip->addFileFromPath(basename($fullpath), $fullpath);
        }
        else {
            header('HTTP/1.0 400 Bad Request');
            die();
        }
    }

    $zip->finish();
}

// Function to handle file downloads
function downloadFile($fullpath, $filename) {
    if (!file_exists($fullpath) || !is_readable($fullpath)) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    $fileSize = filesize($fullpath);

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . $fileSize);
    header('Accept-Ranges: bytes');

    // Handle range requests for resuming downloads
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        list($rangeUnit, $rangeValue) = explode('=', $range, 2);
        if ($rangeUnit == 'bytes') {
            list($start, $end) = explode('-', $rangeValue);

            $start = intval($start);
            $end = ($end === '') ? ($fileSize - 1) : intval($end);
            $length = $end - $start + 1;

            if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes */$fileSize");
                exit;
            }

            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$fileSize");
            header("Content-Length: $length");

            $file = fopen($fullpath, 'rb');
            fseek($file, $start);

            $bufferSize = 8192;
            while (!feof($file) && ($start <= $end)) {
                $bytesToRead = min($bufferSize, $end - $start + 1);
                $buffer = fread($file, $bytesToRead);
                echo $buffer;
                flush();
                $start += $bytesToRead;
            }

            fclose($file);
        }
    } else {
        // No range requested, serve the entire file
        header('Content-Length: ' . $fileSize);

        $file = fopen($fullpath, 'rb');
        while (!feof($file)) {
            echo fread($file, 8192);
            flush();
        }
        fclose($file);
    }
}
