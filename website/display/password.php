<!DOCTYPE html>
<html lang="en">
<head>
    <title>Maldiran files</title>
    <meta name="description" content="Maldiran files website">
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="author" content="Maldiran">
    <meta name="Reply-to" content="maldiran@maldiran.com">
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <link rel="icon" type="image/x-icon" href="/favicon.png">
    <link rel="apple-touch-icon" href="/favicon.png">
    <link rel="stylesheet" type="text/css" href="/core/password.css">
    <style>
        :root {
            <?php
            foreach (LIGHT_MODE as $key => $value) {
                echo $key, ':', $value, ';';
            }
            ?>
        }
        @media (prefers-color-scheme: dark) {
            :root {
                <?php
                foreach (DARK_MODE as $key => $value) {
                    echo $key, ':', $value, ';';
                }
                ?>
            }
        }
    </style>
</head>
<body>
    <form action="/password.php" method="post">
        <label for="login">Login:</label>
        <input type="text" name="login" id="login" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" name="submit" value="Zaloguj się">Log in</button>
    </form>
</body>
</html>
