<?php
// Updates or creates authentication cookies with extended lifetime
function setcookie_new($name, $auth) {
    // Set cookie expiration time (current time + 400 days)
    $time = time() + 34560000; // 400 days

    // Set 'name' cookie
    setcookie('name', $name, [
        'expires'  => $time,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    // Set 'auth' cookie
    setcookie('auth', $auth, [
        'expires'  => $time,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
