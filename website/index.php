<?php
// Redirect to HTTPS if the connection is not secure
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirect);
    exit();
}

// Start the session
session_start();

// Include configuration files
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/config_graphics.php';
require_once HTLOCKED_PATH . '/setcookie.php';

if (!isset($_SESSION['is_set']) && isset($_COOKIE['auth']) && isset($_COOKIE['name'])) {
    // Sanitize the username from the cookie
    $_SESSION['user_name'] = preg_replace('/[^a-zA-Z0-9]+/', '', substr($_COOKIE['name'], 0, 50));

    // Connect to the database
    $conn = mysqli_connect(MYSQL['server'], MYSQL['user'], MYSQL['password'], MYSQL['dbname']);
    if (!$conn) {
        error_log('Database connection failed');
        session_destroy();
        die('Error');
    }

    // Prepare and execute the query to get user data
    $stmt = $conn->prepare('SELECT privileges, cookie, rootdir FROM users WHERE login = ?');
    $stmt->bind_param('s', $_SESSION['user_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();

    // Verify user exists
    if (!$row) {
        error_log('Authentication failed for user: ' . $_SESSION['user_name'] . ' (login not found)');
        session_destroy();
        die('Error');
    }

    // Verify the authentication cookie matches
    if ($_COOKIE['auth'] !== $row['cookie']) {
        error_log('Authentication failed for user: ' . $_SESSION['user_name'] . ' (cookie does not match)');
        session_destroy();
        die('Error');
    }

    // Set a new authentication cookie
    setcookie_new($_SESSION['user_name'], $row['cookie']);

    // Initialize session variables
    $_SESSION['is_set']            = true;
    $_SESSION['user_root']         = $row['rootdir'];
    $_SESSION['user_dir']          = '';
    $_SESSION['user_allow_modify'] = ($row['privileges'] == 1);

    // Close the database connection
    mysqli_close($conn);

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Redirect to the main page
    header('Location: /index.php');
    exit();
} elseif (!isset($_SESSION['is_set'])) {
    // Generate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Include the password input page
    include $_SERVER['DOCUMENT_ROOT'] . '/display/password.php';
} else {
    // Generate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Include the main display page
    include $_SERVER['DOCUMENT_ROOT'] . '/display/index.php';
}
