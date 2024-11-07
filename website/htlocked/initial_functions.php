<?php
function is_session_set($csrf_verification = true) {
    if(!isset($_SESSION["is_set"])) {
        header('HTTP/1.0 403 Forbidden');
        die();
    }
    if ($csrf_verification) {
        $headers = getallheaders();
        $csrfToken = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : '';
        if(!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            header('HTTP/1.0 403 Forbidden');
            die();
        }
    }
}

function get_repeats() {
    $repeats = 1;
    if (isset($_POST["repeats"]) && ctype_digit($_POST["repeats"]))
        $repeats = (int)$_POST["repeats"];
    return $repeats;
}

function get_files($request = 'POST') {
    if (isset($_POST["files"])) {
        $files = json_decode(urldecode($_POST["files"]), true);
        if(is_null($files)) {
            header('HTTP/1.0 400 Bad Request');
            die();
        }
        else
            return $files;
    }
    else if ($request === 'GET' && isset($_GET["files"])) {
        $files = json_decode(urldecode($_GET["files"]), true);
        if(is_null($files)) {
            header('HTTP/1.0 400 Bad Request');
            die();
        }
        else
            return $files;       
    }
    else {
        header('HTTP/1.0 400 Bad Request');
        die();
    }
}

function get_file($request = 'POST') {
    $files = get_files($request);
    if (count($files) === 1)
        $file = current($files);
    else {
        header('HTTP/1.0 400 Bad Request');
        die();
    }
    return $file;
}

// Requires is_session_set()
function get_fullpaths($request = 'POST') {
    $files = get_files($request);
    $fullpaths = array();
    foreach($files as $file) {
        if (is_int($file) && array_key_exists($file, $_SESSION["contents"])) {
            $fullpaths[$file] = $_SESSION["user_root"] . $_SESSION["user_dir"] . "/" . $_SESSION["contents"][$file];
        }
        else {
            header('HTTP/1.0 400 Bad Request');
            die();
        }
    }
    return $fullpaths;
}
