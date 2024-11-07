<?php
include($_SERVER["DOCUMENT_ROOT"] . "/config/config.php");
include($_SERVER["DOCUMENT_ROOT"] . "/config/config_graphics.php");
include(HTLOCKED_PATH . "/initial_functions.php");
session_start();
is_session_set();

$file = get_file(); // Get the file parameter

// Determine the path based on the file parameter
if (is_int($file) && array_key_exists($file, $_SESSION["contents"])) {
    $filename = $_SESSION["contents"][$file];
    $path = $_SESSION["user_dir"] . "/" . $filename;
    $fullpath = $_SESSION["user_root"] . $path;
} elseif ($file == ".") {
    $path = $_SESSION["user_dir"];
} elseif ($file == "..") {
    $repeats = get_repeats(); // Get the number of repeats
    $strrpos = 0;
    $path = $_SESSION["user_dir"];
    for ($i = 0; $i < $repeats; $i++) {
        $strrpos = strrpos($path, "/");
        if ($strrpos === false) {
            $strrpos = 0;
            $i = $repeats; // Break loop after reaching the root
        }
        $path = substr($path, 0, $strrpos);
    }
} else {
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Verify that the path is a directory
if (is_dir($_SESSION["user_root"] . $path)) {
    $_SESSION["user_dir"] = $path;
    $page = 0;
    // Get the page parameter from $_POST
    if (isset($_POST["page"]) && ctype_digit($_POST["page"])) {
        $page = (int)$_POST["page"];
    } elseif (!isset($_POST["page"])) {
        $page = 0;
    } else {
        header('HTTP/1.0 400 Bad Request');
        die();
    }
    $main = "";
    if ($page === 0) {
        $_SESSION["contents"] = scandir_dir_first($_SESSION["user_root"] . $_SESSION["user_dir"]);
        $_SESSION["contents_length"] = count($_SESSION["contents"]);
        $_SESSION["is_dir_writable"] = is_writable($_SESSION["user_root"] . $_SESSION["user_dir"]) && $_SESSION["user_allow_modify"];
    }
    $nextpage = false;
    if (ITEMS_PER_PAGE * ($page + 1) <= $_SESSION["contents_length"]) {
        $nextpage = $page + 1;
    }
    // Loop through directory contents and build the file list
    for ($id = ITEMS_PER_PAGE * $page; $id < ITEMS_PER_PAGE * ($page + 1) && $id < $_SESSION["contents_length"]; $id++) {
        $fullpath = $_SESSION["user_root"] . $_SESSION["user_dir"] . '/' . $_SESSION["contents"][$id];
        $basename = basename($fullpath);
        $is_dir = is_dir($fullpath);
        $main .= display_file_icon($id, $basename, $is_dir, $fullpath);
    }
    $output = array(
        "main" => $main,
        "is_dir_writable" => $_SESSION["is_dir_writable"],
        "nextpage" => $nextpage,
        "dir_level" => substr_count($_SESSION["user_dir"], '/'),
        "user_dir" => $_SESSION["user_dir"],
    );
    echo json_encode($output);
} else {
    header('HTTP/1.0 403 Forbidden');
    die();
}

// Scan directory and list contents
function scandir_dir_first($path) {
    $raw_contents = array_diff(scandir($path), array('..', '.'));
    $dir_part = array();
    $file_part = array();
    foreach ($raw_contents as $element) {
        if (is_dir($path . DIRECTORY_SEPARATOR . $element))
            $dir_part[] = $element;
        else
            $file_part[] = $element;
    }
    return array_merge($dir_part, $file_part);
}

// Display file or directory icon
function display_file_icon($id, $basename, $is_dir, $fullpath) {
    $output = '<div class="file" id="' . $id . '" ondblclick="openClick(' . $id . ')" onmouseenter="fileselected(' . $id . ')" onmouseleave="filedeselected()"><div class="image">';
    if ($is_dir) {
        $output .= svg_thumbnail($id, $basename, DIR_ICON_DEFAULT, 'directory');
    } else {
        $mimetype = mime_content_type($fullpath);
        // If the file can have a thumbnail, try to display it
        if (in_array($mimetype, FILE_MIMETYPE_THUMBNAIL_POSSIBLE)) {
            $img = image_thumbnail($fullpath, $id, $basename);
            if ($img)
                $output .= $img;
            elseif (array_key_exists($mimetype, FILE_ICON_MIMETYPE))
                $output .= svg_thumbnail($id, $basename, FILE_ICON_MIMETYPE[$mimetype]);
            else
                $output .= svg_thumbnail($id, $basename, FILE_ICON_DEFAULT);
        } else {
            // Check for specific icon or use default one
            if (array_key_exists($mimetype, FILE_ICON_MIMETYPE))
                $output .= svg_thumbnail($id, $basename, FILE_ICON_MIMETYPE[$mimetype]);
            else
                $output .= svg_thumbnail($id, $basename, FILE_ICON_DEFAULT);
        }
    }
    $name = $basename;
    if (strlen($name) > 20)
        $name = substr($name, 0, 17) . '...';
    $output .= '</div><div class="description">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</div></div>';
    return $output;
}

// Get image thumbnail
function image_thumbnail($fullpath, $id, $basename) {
    $image = exif_thumbnail($fullpath, $width, $height, $type);
    if ($image) {
        return '<img id="file-' . $id . '" alt="' . htmlspecialchars($basename, ENT_QUOTES, 'UTF-8') . '" src="data:image/gif;base64,' . base64_encode($image) . '">';
    } else {
        return false;
    }
}

// Get SVG thumbnail
function svg_thumbnail($id, $basename, $path, $class = 'icon') {
    return '<svg id="file-' . $id . '" class="' . $class . '" aria-label="' . htmlspecialchars($basename, ENT_QUOTES, 'UTF-8') . '" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">' . file_get_contents($_SERVER["DOCUMENT_ROOT"] . $path) . '</svg>';
}
