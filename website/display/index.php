<!DOCTYPE html>
<html lang="en">
<head>
    <title>Maldiran files</title>
    <meta name="description" content="Maldiran file website">
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="author" content="Maldiran">
    <meta name="Reply-to" content="maldiran@maldiran.com">
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <link rel="icon" type="image/x-icon" href="/favicon.png">
    <link rel="apple-touch-icon" href="/favicon.png">
    <link rel="stylesheet" type="text/css" href="/core/main.css">
    <style>
        :root {
            <?php
            echo '--thumbnail-width:', THUMBNAIL_WIDTH, 'px;';
            echo '--thumbnail-height:', THUMBNAIL_HEIGHT, 'px;';
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
<body id="body">
    <div id="selection-box"></div>
    <div id="alert" style="display: none;">
        <div id="content">
            <div id="content-text"></div>
            <div id="content-buttons"></div>
        </div>
    </div>
    <div id="contextMenu" class="context-menu" style="display:none">
        <ul>
            <li id="open"><div class="action">Open</div></li>
            <li id="download"><div class="action">Download</div></li>
            <li id="rename"><div class="action">Rename</div></li>
            <li id="delete"><div class="action">Delete</div></li>
            <li id="zip"><div class="action">Zip</div></li>
            <li id="unzip"><div class="action">Unzip</div></li>
            <li id="cut"><div class="action">Cut</div></li>
            <li id="copy"><div class="action">Copy</div></li>
            <li id="paste"><div class="action">Paste</div></li>
            <li id="newdir"><div class="action">New folder</div></li>
            <li id="upload"><div class="action">Upload</div></li>
            <li id="properties"><div class="action">Properties</div></li>
        </ul>
    </div>
    <header>
        <div id="back" onclick="openDir('..')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
                <?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . ARROW_LEFT); ?>
            </svg>
        </div>
        <div id="path"></div>
        <div id="copyIndicator">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
                <?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . COPY); ?>
            </svg>
        </div>
    </header>
    <main></main>
    <div id="loading">Loading...</div>
    <div id="overlay">
        <div id="close" onclick="closeOverlay()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
                <?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . XMARK); ?>
            </svg>
        </div>
        <div id="back-file">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
                <?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . ARROW_LEFT); ?>
            </svg>
        </div>
        <div id="forward-file">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
                <?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . ARROW_RIGHT); ?>
            </svg>
        </div>
        <div id="display"></div>
    </div>
    <script type="text/javascript" src="/core/main.js"></script>
    <script>
        // Initialize the application with user data
        init(
            <?php
            echo json_encode($_SESSION['user_name']), ', ';
            if (isset($_SESSION['copy_fullpaths']) && is_array($_SESSION['copy_fullpaths'])) {
                echo json_encode(count($_SESSION['copy_fullpaths']));
            } else {
                echo '0';
            }
            ?>
        );
        openDir('.');
    </script>
</body>
</html>
