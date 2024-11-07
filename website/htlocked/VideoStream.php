<?php
class VideoStream
{
    private $path = "";
    private $stream = "";
    private $buffer = 1024000; // Buffer size (1 MB)
    private $start  = -1;
    private $end    = -1;
    private $size   = 0;

    function __construct($filePath)
    {
        $this->path = $filePath;
    }

    // Open the video file for streaming
    private function open()
    {
        if (!($this->stream = fopen($this->path, 'rb'))) {
            header('HTTP/1.1 500 Internal Server Error');
            die();
        }
    }

    // Set headers for streaming video content
    private function setHeader($mimeType)
    {
        if (ob_get_level()) {
            ob_end_clean(); // Clean output buffering
        }

        header("Content-Type: $mimeType");
        header("Cache-Control: max-age=2592000, public");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT');

        $this->start = 0;
        $this->size  = filesize($this->path);
        $this->end   = $this->size - 1;
        header("Accept-Ranges: bytes");

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            list(, $range) = explode('=', $range, 2);

            // Multiple ranges not supported
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes */$this->size");
                exit;
            }

            $range = explode('-', $range);
            $c_start = isset($range[0]) && ctype_digit($range[0]) ? intval($range[0]) : 0;
            $c_end = isset($range[1]) && ctype_digit($range[1]) ? intval($range[1]) : $this->end;

            $c_end = min($c_end, $this->end);
            if ($c_start > $c_end || $c_start > $this->size - 1) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes */$this->size");
                exit;
            }

            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;
            fseek($this->stream, $this->start);

            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: $length");
            header("Content-Range: bytes $this->start-$this->end/$this->size");
        } else {
            header("Content-Length: " . $this->size);
        }
    }

    // Close the stream
    private function end()
    {
        fclose($this->stream);
        exit;
    }

    // Stream the video content to the client
    private function stream()
    {
        $i = $this->start;
        set_time_limit(0);

        while (!feof($this->stream) && $i <= $this->end) {
            $bytesToRead = $this->buffer;
            if (($i + $bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }
            $data = fread($this->stream, $bytesToRead);
            echo $data;
            flush();
            $i += $bytesToRead;
        }
    }

    // Start streaming the video content
    function start($mimeType)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $this->open();
        $this->setHeader($mimeType);
        $this->stream();
        $this->end();
    }
}
