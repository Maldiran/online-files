<?php
// Include configuration files
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require_once HTLOCKED_PATH . '/setcookie.php';

// Start the session
session_start();

// Define maximum input lengths if not already defined
if (!defined('MAX_USERNAME_LENGTH')) {
    define('MAX_USERNAME_LENGTH', 50);
}
if (!defined('MAX_PASSWORD_LENGTH')) {
    define('MAX_PASSWORD_LENGTH', 255);
}

// Check CSRF token
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log('CSRF tokens do not match');
    session_destroy();
    die('Error');
}

// Check if login and password are provided
if (empty($_POST['login']) || empty($_POST['password'])) {
    error_log('Login or password not provided');
    session_destroy();
    die('Error');
}

// Get and sanitize user inputs
$username = trim($_POST['login']);
$password = trim($_POST['password']);

// Check input lengths
if (strlen($username) > MAX_USERNAME_LENGTH) {
    error_log('Username too long');
    session_destroy();
    die('Error');
}
if (strlen($password) > MAX_PASSWORD_LENGTH) {
    error_log('Password too long');
    session_destroy();
    die('Error');
}

// Store username in session
$_SESSION['user_name'] = $username;

try {
    // Connect to the database
    $conn = mysqli_connect(MYSQL['server'], MYSQL['user'], MYSQL['password'], MYSQL['dbname']);
    if (!$conn) {
        error_log('Database connection failed: ' . mysqli_connect_error());
        session_destroy();
        die('Error');
    }

    // Prepare and execute the query to get user data
    $stmt = $conn->prepare('SELECT hash, privileges, cookie, rootdir FROM users WHERE login = ?');
    $stmt->bind_param('s', $_SESSION['user_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();

    // Verify user exists
    if (!$row) {
        error_log('Authentication failed for user: ' . $_SESSION['user_name'] . ' (login not found)');
        session_destroy();
        die('<p><a href="/index.php">Return</a></p>');
    }

    // Verify the password
    if (!password_verify($password, $row['hash'])) {
        error_log('Authentication failed for user: ' . $_SESSION['user_name'] . ' (password mismatch)');
        session_destroy();
        die('<p><a href="/index.php">Return</a></p>');
    }

    // Authentication successful
    setcookie_new($_SESSION['user_name'], $row['cookie']);
    $_SESSION['is_set']            = true;
    $_SESSION['user_dir']          = '';
    $_SESSION['user_root']         = $row['rootdir'];
    $_SESSION['user_allow_modify'] = ($row['privileges'] == 1);

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Redirect to the main page
    header('Location: /index.php');
    exit();

} catch (Exception $e) {
    error_log($e->getMessage());
    session_destroy();
    die('<p><a href="/index.php">Return</a></p>');
}
