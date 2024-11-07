// List of MIME types that can be opened directly in the browser
const openableMimeTypes = [
    'text/plain',
    'text/html',
    'text/css',
    'application/javascript',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/svg+xml',
    'image/webp',
    'image/avif',
    'audio/mpeg',
    'audio/ogg',
    'audio/wav',
    'audio/webm',
    'audio/aac',
    'video/mp4',
    'video/webm',
    'video/ogg',
    'application/pdf',
];

// Retrieve CSRF token from meta tag
const csrfMetaTag = document.querySelector('meta[name="csrf-token"]');
const CSRF = csrfMetaTag ? csrfMetaTag.getAttribute('content') : null;

// Context menu elements
const contextmenu = {
    self: document.getElementById('contextMenu'),
    open: document.getElementById('open'),
    download: document.getElementById('download'),
    rename: document.getElementById('rename'),
    delete: document.getElementById('delete'),
    zip: document.getElementById('zip'),
    unzip: document.getElementById('unzip'),
    cut: document.getElementById('cut'),
    copy: document.getElementById('copy'),
    paste: document.getElementById('paste'),
    newdir: document.getElementById('newdir'),
    upload: document.getElementById('upload'),
    properties: document.getElementById('properties'),
};

// Mapping of context menu actions to functions
const contextmenuFunctions = {
    open: openClick,
    download: downloadClick,
    rename: renameClick,
    delete: deleteClick,
    zip: zipClick,
    unzip: unzipClick,
    cut: cutClick,
    copy: copyClick,
    paste: pasteClick,
    newdir: newdirClick,
    upload: uploadClick,
    properties: propertiesClick,
};

// DOM elements and variables
const loading = document.getElementById('loading');
const path = document.getElementById('path');
const main = document.querySelector('main');
const copyIndicator = document.getElementById('copyIndicator');
const overlay = {
    self: document.getElementById('overlay'),
    back: document.getElementById('back-file'),
    forward: document.getElementById('forward-file'),
    display: document.getElementById('display'),
};
const alertdiv = {
    self: document.getElementById('alert'),
    content: document.getElementById('content'),
    text: document.getElementById('content-text'),
    buttons: document.getElementById('content-buttons'),
};
const selectionBox = document.getElementById('selection-box');

var userName;
var fileId = false;
var canOpenFile = true;
var selectedDivs = [];
var selectedDivsJSON = '';
var lastSelectedIndex = null;
var isMouseUpTriggered = false;
var selectableDivs = [];
var startX = 0,
    startY = 0,
    isSelecting = false;
var isDirWritable = true; // Indicates if the current directory is writable

// Initialize the application with user data
function init(name, copyNumber) {
    userName = name;
    if (copyNumber > 0) copyIndicator.style.display = 'block';
}

// Utility functions to get viewport dimensions
function getWidth() {
    return Math.max(
        document.body.scrollWidth,
        document.documentElement.scrollWidth,
        document.body.offsetWidth,
        document.documentElement.offsetWidth,
        document.documentElement.clientWidth
    );
}

function getHeight() {
    return Math.max(
        document.body.scrollHeight,
        document.documentElement.scrollHeight,
        document.body.offsetHeight,
        document.documentElement.offsetHeight,
        document.documentElement.clientHeight
    );
}

// File selection handlers
function fileselected(id) {
    fileId = id;
}

function filedeselected() {
    fileId = false;
}

// Click event handler for file selection and context menu actions
document.addEventListener('click', (e) => {
    // Handle context menu item clicks
    Object.entries(contextmenuFunctions).forEach(([id, divFunction]) => {
        if (contextmenu[id].contains(e.target)) {
            selectedDivsJSON = JSON.stringify(selectedDivs);
            if (id === 'open') divFunction(false);
            else divFunction();
        }
    });

    contextmenu.self.style.display = 'none';

    if (isMouseUpTriggered) {
        isMouseUpTriggered = false;
        return;
    }

    if (fileId === false) {
        clearAllSelections();
    } else {
        const divId = fileId;
        const isCtrlPressed = e.ctrlKey || e.metaKey;
        const isShiftPressed = e.shiftKey;

        // Handle Shift-click: Select range
        if (isShiftPressed && lastSelectedIndex !== null) {
            let start = divId;
            let end = lastSelectedIndex;
            if (divId > lastSelectedIndex) {
                start = lastSelectedIndex;
                end = divId;
            }
            toggleSelectDiv(lastSelectedIndex);
            for (let i = start; i <= end; i++) {
                toggleSelectDiv(i);
            }
        }
        // Handle Ctrl/Cmd-click: Toggle selection of current div
        else if (isCtrlPressed) {
            toggleSelectDiv(divId);
        }
        // Handle simple click: Deselect all others and select only the clicked div
        else {
            clearAllSelections();
            toggleSelectDiv(divId);
        }

        // Update the last selected index
        lastSelectedIndex = divId;
    }
});

// Toggle selection of a file div
function toggleSelectDiv(div) {
    if (selectedDivs.includes(div)) {
        document.getElementById(div).classList.remove('fileselected');
        selectedDivs = selectedDivs.filter((d) => d !== div);
    } else {
        document.getElementById(div).classList.add('fileselected');
        selectedDivs.push(div);
    }
}

// Clear all selected files
function clearAllSelections() {
    selectedDivs.forEach((div) => document.getElementById(div).classList.remove('fileselected'));
    selectedDivs = [];
}

// Mouse down event to start the selection
document.addEventListener('mousedown', (e) => {
    if (alertdiv.self.style.display !== 'none') return;
    // Start tracking mouse position
    startX = e.pageX;
    startY = e.pageY;

    // Reset and show the selection box
    selectionBox.style.left = startX + 'px';
    selectionBox.style.top = startY + 'px';
    selectionBox.style.width = '0px';
    selectionBox.style.height = '0px';
    selectionBox.style.display = 'block';

    isSelecting = true;
});

// Mouse move event to update the selection box size
document.addEventListener('mousemove', (e) => {
    if (!isSelecting) return;

    let currentX = e.pageX;
    let currentY = e.pageY;
    if (currentX > window.innerWidth) return;
    selectionBox.style.width = Math.abs(currentX - startX) + 'px';
    selectionBox.style.height = Math.abs(currentY - startY) + 'px';
    selectionBox.style.left = Math.min(currentX, startX) + 'px';
    selectionBox.style.top = Math.min(currentY, startY) + 'px';
});

// Mouse up event to finalize the selection
document.addEventListener('mouseup', (e) => {
    if (!isSelecting) return;
    isSelecting = false;
    const boxRect = selectionBox.getBoundingClientRect();
    if (boxRect.width < 20 && boxRect.height < 20) {
        selectionBox.style.display = 'none';
        return;
    }
    isMouseUpTriggered = true;
    clearAllSelections();

    // Check which divs are inside the selection box
    selectableDivs.forEach((div) => {
        const divRect = div.getBoundingClientRect();

        // Check if the div is inside the selection box
        if (
            divRect.left <= boxRect.right &&
            divRect.right >= boxRect.left &&
            divRect.top <= boxRect.bottom &&
            divRect.bottom >= boxRect.top
        ) {
            let id = Number(div.id);
            toggleSelectDiv(id);
        }
    });

    selectionBox.style.display = 'none';
});

// Context menu event handler
document.addEventListener('contextmenu', (e) => {
    e.preventDefault();
    if (Number.isInteger(fileId)) {
        if (selectedDivs.indexOf(fileId) >= 0) {
            if (selectedDivs.length === 1) menuOneElement();
            else menuManyElements();
        } else {
            clearAllSelections();
            toggleSelectDiv(fileId);
            menuOneElement();
        }
    } else {
        // Did not click on any selected item, clear any selection
        clearAllSelections();
        if (isDirWritable === false) {
            contextmenu.self.style.display = 'none';
            return;
        }
        menuParentDirectory();
    }
    contextmenu.self.style.display = 'block';
    let leftPosition = e.pageX;
    if (e.pageX + contextmenu.self.offsetWidth > getWidth())
        // On the border of the website we want menu to face other side
        leftPosition -= contextmenu.self.offsetWidth;
    let topPosition = e.pageY;
    if (e.pageY + contextmenu.self.offsetHeight > getHeight())
        // On the border of the website we want menu to face other side
        topPosition -= contextmenu.self.offsetHeight;
    contextmenu.self.style.left = leftPosition + 'px';
    contextmenu.self.style.top = topPosition + 'px';
});

// Hide the context menu
function hideMenu() {
    contextmenu.self.style.display = 'none';
}

// Configure context menu for one selected element
function menuOneElement() {
    contextmenu.open.style.display = 'block';
    contextmenu.download.style.display = 'block';
    if (isDirWritable === false) {
        contextmenu.rename.style.display = 'none';
        contextmenu.delete.style.display = 'none';
        contextmenu.zip.style.display = 'none';
        contextmenu.unzip.style.display = 'none';
        contextmenu.cut.style.display = 'none';
        contextmenu.copy.style.display = 'none';
        contextmenu.paste.style.display = 'none';
        contextmenu.newdir.style.display = 'none';
        contextmenu.upload.style.display = 'none';
        contextmenu.properties.style.display = 'block';
    } else {
        contextmenu.rename.style.display = 'block';
        contextmenu.delete.style.display = 'block';
        contextmenu.zip.style.display = 'block';
        contextmenu.unzip.style.display = 'block';
        contextmenu.cut.style.display = 'block';
        contextmenu.copy.style.display = 'block';
        contextmenu.paste.style.display = 'block';
        contextmenu.newdir.style.display = 'block';
        contextmenu.upload.style.display = 'block';
        contextmenu.properties.style.display = 'block';
    }
}

// Configure context menu for multiple selected elements
function menuManyElements() {
    contextmenu.open.style.display = 'none';
    contextmenu.download.style.display = 'block';
    contextmenu.rename.style.display = 'none';
    if (isDirWritable === false) {
        contextmenu.delete.style.display = 'none';
        contextmenu.zip.style.display = 'none';
        contextmenu.unzip.style.display = 'none';
        contextmenu.cut.style.display = 'none';
        contextmenu.copy.style.display = 'none';
        contextmenu.paste.style.display = 'none';
        contextmenu.newdir.style.display = 'none';
        contextmenu.upload.style.display = 'none';
        contextmenu.properties.style.display = 'none';
    } else {
        contextmenu.delete.style.display = 'block';
        contextmenu.zip.style.display = 'block';
        contextmenu.unzip.style.display = 'block';
        contextmenu.cut.style.display = 'block';
        contextmenu.copy.style.display = 'block';
        contextmenu.paste.style.display = 'block';
        contextmenu.newdir.style.display = 'block';
        contextmenu.upload.style.display = 'block';
        contextmenu.properties.style.display = 'none';
    }
}

// Configure context menu for the parent directory
function menuParentDirectory() {
    contextmenu.open.style.display = 'none';
    contextmenu.download.style.display = 'none';
    contextmenu.zip.style.display = 'none';
    contextmenu.unzip.style.display = 'none';
    contextmenu.rename.style.display = 'none';
    contextmenu.delete.style.display = 'none';
    contextmenu.cut.style.display = 'none';
    contextmenu.copy.style.display = 'none';
    contextmenu.paste.style.display = 'block';
    contextmenu.newdir.style.display = 'block';
    contextmenu.upload.style.display = 'block';
    contextmenu.properties.style.display = 'none';
}

// Delay function
function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

// Open a file or directory
function openClick(file, canDir = true) {
    if (file === false) file = selectedDivs[0];
    let element = document.getElementById('file-' + file);
    if (element === null) {
        closeOverlay();
        return;
    }
    if (element.classList.contains('directory')) {
        if (canDir) openDir(file);
        else closeOverlay();
    } else openFile(file);
}

// Open a directory and load its contents
function openDir(file, repeats = 1, page = 0, recursion = { main: '' }) {
    if (page === 0) {
        main.style.display = 'none';
        path.style.display = 'none';
        loading.style.display = 'block';
    }
    selectedDivs = [];
    lastSelectedIndex = null;
    let filesJSON = JSON.stringify([file]);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/open_dir.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params =
        'files=' +
        encodeURIComponent(filesJSON) +
        '&repeats=' +
        repeats +
        '&page=' +
        page;

    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                let output = JSON.parse(xhr.responseText);
                recursion.main += output.main;
                if (output.nextpage === false) {
                    isDirWritable = output.is_dir_writable;
                    contextmenu.self.style.display = 'none';
                    loading.style.display = 'none';
                    path.innerHTML = generatePath(output.user_dir, output.dir_level);
                    path.style.display = 'block';
                    main.innerHTML = recursion.main;
                    main.style.display = 'flex';
                    selectableDivs = document.querySelectorAll('.file');
                } else {
                    openDir('.', 0, output.nextpage, recursion);
                }
            } catch (error) {
                console.log(error.message);
                openDir('..');
            }
        } else {
            console.log('Server responded with error code ' + xhr.status);
            openDir('..');
        }
    };
    xhr.onerror = function () {
        console.log('Request failed');
        openDir('..');
    };
    xhr.send(params);
}

// Generate breadcrumb path
function generatePath(rawPath, dirLevel) {
    let fullpath = userName + rawPath;
    let output = '';
    let parts = fullpath.split('/');
    for (let i = dirLevel; i > 0; i--) {
        output += '<span onclick="openDir(\'..\', ' + i + ')">' + parts.shift() + '</span>/';
    }
    output += '<span onclick="openDir(\'.\')">' + parts.shift() + '</span>';
    return output;
}

// Stop all loading in overlay
function abortOverlay() {
    // Select the video or image element within the overlay
    const video = overlay.display.querySelector('video');
    const img = overlay.display.querySelector('img');

    if (video) {
        video.pause(); // Stop video playback
        video.src = ''; // Clear the video source to stop streaming
        video.load(); // Reset the video element to stop buffering
    }

    if (img) {
        img.src = ''; // Clear the image source to stop loading
    }
}

// Close the overlay display
function closeOverlay() {
    abortOverlay();
    // Remove the overlay using requestAnimationFrame for smooth DOM updates
    requestAnimationFrame(() => {
        overlay.self.style.display = 'none';
        overlay.display.innerHTML = ''; // Clear the display content
    });
}

// Open a file for viewing or downloading
async function openFile(file) {
    let filesJSON = JSON.stringify([file]);
    let timestamp = new Date().getTime();

    let params = 'files=' + encodeURIComponent(filesJSON) + '&t=' + timestamp;
    let mimeType = await isMimeTypeOpenable('/operations/mimetype.php', params);

    if (mimeType !== false) {
        overlay.self.style.display = 'flex';

        overlay.back.onclick = function () {
            abortOverlay();
            openClick(file - 1, false);
        };

        overlay.forward.onclick = function () {
            abortOverlay();
            openClick(file + 1, false);
        };

        // Display media and handle its cleanup
        overlay.display.innerHTML = displayMimeType(
            mimeType,
            '/operations/open_file.php',
            params
        );
    } else {
        downloadClick([file]);
    }
}

// Function to download files
function downloadClick(files = selectedDivs) {
    location.href = '/operations/download.php?files=' + encodeURI(JSON.stringify(files));
}

// Display content based on MIME type
function displayMimeType(mimeType, url, params) {
    let fullUrl = `${url}?${params}`;

    if (mimeType.startsWith('audio/') || mimeType.startsWith('video/')) {
        return `<video class='source' autoplay controls preload="metadata" src='${fullUrl}'></video>`;
    }

    if (mimeType.startsWith('image/')) {
        return `<img class='source' src='${fullUrl}' loading='lazy'></img>`;
    }

    if (mimeType.startsWith('text/') || mimeType.startsWith('application/')) {
        return `<iframe class='source' src='${fullUrl}'></iframe>`;
    }

    return '';
}

// Check if MIME type is openable in the browser
async function isMimeTypeOpenable(url, params) {
    const controller = new AbortController();
    const signal = controller.signal;

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': CSRF,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            signal: signal,
            body: params,
        });

        const mimeType = await response.text();

        if (openableMimeTypes.includes(mimeType.toLowerCase())) {
            return mimeType;
        }

        if (mimeType.startsWith('audio/') || mimeType.startsWith('video/')) {
            return isMediaMimeTypeOpenable(mimeType) ? mimeType : false;
        }

        return false;
    } catch (error) {
        if (error.name === 'AbortError') {
            console.log('Fetch aborted');
        } else {
            console.error('Error fetching MIME type:', error);
        }

        return false;
    }
}

// Check if media MIME type is supported by the browser
function isMediaMimeTypeOpenable(mimeType) {
    const mediaElement = document.createElement('video');
    const canPlay = mediaElement.canPlayType(mimeType);
    return canPlay === 'probably' || canPlay === 'maybe';
}

// Close the alert dialog
function closeAlert() {
    alertdiv.self.style.display = 'none';
}

// Default error handler for AJAX requests
function defaultAJAXError() {
    console.log('Request failed');
    closeAlert();
    openDir('.');
}

// Delete selected files
function deleteClick() {
    let textHTML = 'Are you sure that you want to delete ';
    if (selectedDivs.length === 1)
        textHTML += "'" + getName(document.getElementById('file-' + selectedDivs[0])) + "'?<div class='margin'></div>";
    else textHTML += selectedDivs.length + ' files?<div class="margin"></div>';
    alertdiv.text.innerHTML = textHTML;
    alertdiv.buttons.innerHTML = "<button onclick='closeAlert()'>Abort</button><button id='enterclick' onclick='deleteAction()'>Yes</button>";
    alertdiv.self.style.display = 'block';
}

// Perform the delete action
function deleteAction() {
    closeAlert();
    main.style.display = 'none';
    loading.style.display = 'block';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/delete.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params = 'files=' + encodeURIComponent(selectedDivsJSON);

    xhr.onload = function () {
        main.style.display = 'flex';
        loading.style.display = 'none';
        if (xhr.status === 200) openDir('.');
        else console.log('Server responded with error code ' + this.status);
    };
    xhr.onerror = defaultAJAXError;
    xhr.send(params);
}

// Get the name of a file or directory
function getName(element) {
    if (typeof element.alt !== 'undefined') return element.alt;
    else return element.getAttribute('aria-label');
}

// Rename selected file
function renameClick() {
    if (selectedDivs.length !== 1) return;
    let name = getName(document.getElementById('file-' + selectedDivs[0]));
    alertdiv.text.innerHTML =
        "How would you like to rename '" +
        name +
        "?<br><input id='rename-name' type='text' value='" +
        name +
        "'></input><div class='margin'></div>";
    alertdiv.buttons.innerHTML = "<button onclick='closeAlert()'>Abort</button><button id='enterclick' onclick='renameAction()'>Rename</button>";
    alertdiv.self.style.display = 'block';

    let renameInput = document.getElementById('rename-name');
    renameInput.focus();
    renameInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Prevent default behavior (form submission, etc.)
            document.getElementById('enterclick').click(); // Trigger the "Rename" button click
        }
    });
}

// Perform the rename action
function renameAction() {
    let name = document.getElementById('rename-name').value;
    closeAlert();
    main.style.display = 'none';
    loading.style.display = 'block';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/rename.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params = 'files=' + encodeURIComponent(selectedDivsJSON) + '&name=' + encodeURIComponent(name);

    xhr.onload = function () {
        main.style.display = 'flex';
        loading.style.display = 'none';
        if (xhr.status === 200) openDir('.');
        else if (xhr.status === 409) {
            alertdiv.text.innerHTML = 'This name is taken';
            alertdiv.buttons.innerHTML = '';
            alertdiv.self.style.display = 'block';
            setTimeout(closeAlert, 2000);
            openDir('.');
        } else console.log('Server responded with error code ' + xhr.status);
    };
    xhr.onerror = defaultAJAXError;
    xhr.send(params);
}

// Create a new directory
function newdirClick() {
    alertdiv.text.innerHTML =
        "How would you like to name a new folder?<br><input id='newdir-name' type='text' value='New folder'></input><div class='margin'></div>";
    alertdiv.buttons.innerHTML = "<button onclick='closeAlert()'>Abort</button><button id='enterclick' onclick='newdirAction()'>Create</button>";
    alertdiv.self.style.display = 'block';

    let newdirInput = document.getElementById('newdir-name');
    newdirInput.focus();
    newdirInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Prevent default behavior (form submission, etc.)
            document.getElementById('enterclick').click();
        }
    });
}

// Perform the action to create a new directory
function newdirAction() {
    let name = document.getElementById('newdir-name').value;
    closeAlert();
    main.style.display = 'none';
    loading.style.display = 'block';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/newdir.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params = 'name=' + encodeURIComponent(name);

    xhr.onload = function () {
        main.style.display = 'flex';
        loading.style.display = 'none';
        if (xhr.status === 200) openDir('.');
        else if (xhr.status === 409) {
            alertdiv.text.innerHTML = 'This name is taken';
            alertdiv.buttons.innerHTML = '';
            alertdiv.self.style.display = 'block';
            setTimeout(closeAlert, 2000);
            openDir('.');
        } else console.log('Server responded with error code ' + xhr.status);
    };
    xhr.onerror = defaultAJAXError;
    xhr.send(params);
}

// Upload files
function uploadClick() {
    alertdiv.text.innerHTML = "Select files to upload<br><input type='file' name='files[]' id='files' multiple><div class='margin'></div>";
    alertdiv.buttons.innerHTML = "<button onclick='closeAlert()'>Abort</button><button id='enterclick' onclick='uploadAction()'>Upload</button>";
    alertdiv.self.style.display = 'block';
}

// Perform the upload action
function uploadAction() {
    let files = document.getElementById('files').files;
    let totalFiles = files.length;
    let uploadedFiles = 0;
    let isCanceled = false;
    let xhr = null;

    alertdiv.text.innerHTML = `0/${totalFiles} files uploaded<div class='margin'></div>`;
    alertdiv.buttons.innerHTML = "<button id='uploadAbortButton'>Abort</button>";

    document.getElementById('uploadAbortButton').onclick = function () {
        if (xhr) {
            xhr.abort(); // Abort the current request
            isCanceled = true; // Mark the upload as canceled
            alertdiv.text.innerHTML = 'Upload canceled';
            alertdiv.buttons.innerHTML = '';
            setTimeout(closeAlert, 2000);
            openDir('.');
        }
    };

    // Function to handle file upload
    function uploadFile(fileIndex) {
        if (isCanceled) return;

        let formData = new FormData();
        formData.append('files[]', files[fileIndex]); // Upload one file at a time

        xhr = new XMLHttpRequest();

        // When the file is uploaded, update the count and progress bar
        xhr.onload = function () {
            if (xhr.status == 200) {
                uploadedFiles++;
                alertdiv.text.innerHTML = `${uploadedFiles}/${totalFiles} files uploaded<div class='margin'></div>`;
                if (uploadedFiles === totalFiles) {
                    closeAlert();
                    openDir('.');
                } else uploadFile(fileIndex + 1);
            } else {
                alertdiv.text.innerHTML = 'Upload failed. Error: ' + xhr.status;
                console.log('Upload failed. Error: ' + xhr.status);
                setTimeout(closeAlert, 2000);
                openDir('.');
            }
        };
        xhr.onerror = function () {
            alertdiv.text.innerHTML = 'Request failed';
            console.log('Request failed');
            setTimeout(closeAlert, 2000);
            openDir('.');
        };
        xhr.open('POST', '/operations/upload.php', true);
        xhr.setRequestHeader('X-CSRF-Token', CSRF);
        xhr.send(formData);
    }

    // Start uploading the first file
    uploadFile(0);
}

// Cut selected files
function cutClick() {
    copyAction(true);
}

// Copy selected files
function copyClick() {
    copyAction(false);
}

// Perform copy or cut action
function copyAction(cut) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/copy.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params = 'files=' + encodeURIComponent(selectedDivsJSON) + '&cut=' + cut;

    xhr.onload = function () {
        if (xhr.status === 200) copyIndicator.style.display = 'block';
        else console.log('Server responded with error code ' + this.status);
    };
    xhr.onerror = function () {
        console.log('Request failed');
    };
    xhr.send(params);
}

// Paste copied or cut files
function pasteClick() {
    alertdiv.text.innerHTML = `Pasting...`;
    alertdiv.buttons.innerHTML = '';
    alertdiv.self.style.display = 'block';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/paste.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            if (xhr.responseText === 'true') copyIndicator.style.display = 'none';
            closeAlert();
        } else if (xhr.status === 409) {
            alertdiv.text.innerHTML = 'File with this name already exists';
            alertdiv.buttons.innerHTML = '';
            alertdiv.self.style.display = 'block';
            setTimeout(closeAlert, 2000);
        } else {
            console.log('Server responded with error code ' + this.status);
            closeAlert();
        }
        openDir('.');
    };
    xhr.onerror = defaultAJAXError;
    xhr.send();
}

// Zip selected files
function zipClick() {
    let textHTML = 'Are you sure that you want to zip ';
    if (selectedDivs.length === 1)
        textHTML += "'" + getName(document.getElementById('file-' + selectedDivs[0])) + "'?<div class='margin'></div>";
    else textHTML += selectedDivs.length + ' files?<div class="margin"></div>';
    alertdiv.text.innerHTML = textHTML;
    alertdiv.buttons.innerHTML = "<button onclick='closeAlert()'>Abort</button><button id='enterclick' onclick='zipAction()'>Yes</button>";
    alertdiv.self.style.display = 'block';
}

// Perform the zip action
function zipAction() {
    alertdiv.text.innerHTML = `Zipping...`;
    alertdiv.buttons.innerHTML = '';
    alertdiv.self.style.display = 'block';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/zip.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params = 'files=' + encodeURIComponent(selectedDivsJSON);

    xhr.onload = function () {
        main.style.display = 'flex';
        loading.style.display = 'none';
        if (xhr.status === 200) {
            openDir('.');
            closeAlert();
        } else if (xhr.status === 409) {
            alertdiv.text.innerHTML = 'File with this name already exists';
            alertdiv.buttons.innerHTML = '';
            alertdiv.self.style.display = 'block';
            setTimeout(closeAlert, 2000);
            openDir('.');
        } else {
            console.log('Server responded with error code ' + this.status);
            openDir('.');
            closeAlert();
        }
    };
    xhr.onerror = defaultAJAXError;
    xhr.send(params);
}

// Unzip selected files
function unzipClick() {
    alertdiv.text.innerHTML = `Extracting...`;
    alertdiv.buttons.innerHTML = '';
    alertdiv.self.style.display = 'block';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/unzip.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params = 'files=' + encodeURIComponent(selectedDivsJSON);

    xhr.onload = function () {
        if (xhr.status === 200) {
            openDir('.');
            closeAlert();
        } else if (xhr.status === 409) {
            alertdiv.text.innerHTML = 'File with this name already exists';
            alertdiv.buttons.innerHTML = '';
            alertdiv.self.style.display = 'block';
            setTimeout(closeAlert, 2000);
            openDir('.');
        } else {
            console.log('Server responded with error code ' + this.status);
            openDir('.');
            closeAlert();
        }
    };
    xhr.onerror = defaultAJAXError;
    xhr.send(params);
}

// Display properties of a selected file
function propertiesClick() {
    alertdiv.text.innerHTML = `Loading...`;
    alertdiv.buttons.innerHTML = '';
    alertdiv.self.style.display = 'block';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/operations/properties.php', true);
    xhr.setRequestHeader('X-CSRF-Token', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let params = 'files=' + encodeURIComponent(selectedDivsJSON);

    xhr.onload = function () {
        if (xhr.status === 200) {
            let data = JSON.parse(xhr.responseText);
            alertdiv.text.innerHTML =
                '<table>' +
                '<tr><td>Name:</td><td>' +
                data.name +
                '</td></tr>' +
                '<tr><td>Location:</td><td>' +
                (data.location === '' ? '/' : data.location) +
                '</td></tr>' +
                '<tr><td>Is directory:</td><td>' +
                (data.dir ? 'yes' : 'no') +
                '</td></tr>' +
                '<tr><td>Is readable:</td><td>' +
                (data.read ? 'yes' : 'no') +
                '</td></tr>' +
                '<tr><td>Is writable:</td><td>' +
                (data.write ? 'yes' : 'no') +
                '</td></tr>' +
                '<tr><td>Size:</td><td>' +
                humanFileSize(data.size) +
                '</td></tr>' +
                '<tr><td>Modification date:</td><td>' +
                humanDate(data.modification) +
                '</td></tr></table>' +
                '<br><div class="margin"></div>';
            alertdiv.buttons.innerHTML = "<button onclick='closeAlert()'>Close</button>";
        } else {
            console.log('Server responded with error code ' + xhr.status);
            closeAlert();
        }
    };
    xhr.onerror = defaultAJAXError;
    xhr.send(params);
}

// Convert file size to human-readable format
function humanFileSize(size) {
    var i = size == 0 ? 0 : Math.floor(Math.log(size) / Math.log(1024));
    return +((size / Math.pow(1024, i)).toFixed(2)) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
}

// Convert timestamp to human-readable date
function humanDate(timestamp) {
    let date = new Date(timestamp * 1000);
    return date.toUTCString();
}

// Keyboard event listener for shortcuts
document.addEventListener('keydown', function (event) {
    // Check if 'Delete' key was pressed
    if (event.key === 'Delete') {
        if (selectedDivs.length > 0 && alertdiv.self.style.display == 'none') {
            selectedDivsJSON = JSON.stringify(selectedDivs);
            deleteClick();
        }
    }

    // Check if 'F2' key was pressed for rename
    if (event.key === 'F2') {
        if (selectedDivs.length > 0 && alertdiv.self.style.display == 'none') {
            selectedDivsJSON = JSON.stringify(selectedDivs);
            renameClick();
        }
    }

    // Check if 'Ctrl + C' was pressed for copy
    if (event.ctrlKey && event.key === 'c') {
        if (selectedDivs.length > 0 && alertdiv.self.style.display == 'none') {
            selectedDivsJSON = JSON.stringify(selectedDivs);
            copyClick();
        }
    }

    // Check if 'Ctrl + X' was pressed for cut
    if (event.ctrlKey && event.key === 'x') {
        if (selectedDivs.length > 0 && alertdiv.self.style.display == 'none') {
            selectedDivsJSON = JSON.stringify(selectedDivs);
            cutClick();
        }
    }

    // Check if 'Ctrl + V' was pressed for paste
    if (event.ctrlKey && event.key === 'v') {
        // Paste should always be allowed even if selectedDivs is empty
        if (alertdiv.self.style.display == 'none') pasteClick();
    }

    // Handle 'Enter' key for submitting forms
    if (event.key === 'Enter') {
        let div = document.getElementById('enterclick');
        if (div) {
            event.preventDefault(); // Prevent default Enter behavior
            div.click(); // Trigger the button click
        }
    }
});
