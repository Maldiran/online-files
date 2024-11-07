<?php
// dimmensions of thumbnail/icon
const THUMBNAIL_HEIGHT = 150;
const THUMBNAIL_WIDTH  = 150;

// number of files displayed per one request, higher number means less big requests.
const ITEMS_PER_PAGE   = 200;

// CSS properties for light mode and dark mode
const LIGHT_MODE = array(
    '--color-1'       => '#ffffff',
    '--color-2'       => '#aaaaaa',
    '--color-3'       => '#555555',
    '--color-4'       => '#000000',
    '--color-5'       => '#ffffff',
    '--transparent-1' => 'rgba(0, 0, 0, 0.7)',
    '--transparent-2' => 'rgba(0, 0, 0, 0.3)',
);

const DARK_MODE = array(
    '--color-1'       => '#000000',
    '--color-2'       => '#333333',
    '--color-3'       => '#aaaaaa',
    '--color-4'       => '#aaaaaa',
    '--color-5'       => '#ffffff',
    '--transparent-1' => 'rgba(0, 0, 0, 0.7)',
    '--transparent-2' => 'rgba(0, 0, 0, 0.3)',
);

// various svg icons, read /core/icons/README.md if you want to create your own
const DIR_ICON_DEFAULT  = "/core/icons/folder.svg_path";
const FILE_ICON_DEFAULT = '/core/icons/file.svg_path';
const XMARK             = '/core/icons/xmark.svg_path';
const COPY              = '/core/icons/copy.svg_path';
const ARROW_RIGHT       = '/core/icons/arrow-right.svg_path';
const ARROW_LEFT        = '/core/icons/arrow-left.svg_path';

// recognizable mime types and their icon, read /core/icons/README.md if you want to create your own
const FILE_ICON_MIMETYPE = array(
    'text/csv'                                                            => '/core/icons/file-csv.svg_path',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'   => '/core/icons/file-excel.svg_path',
    'application/vnd.ms-excel'                                            => '/core/icons/file-excel.svg_path',
    'image/gif'                                                           => '/core/icons/file-gif.svg_path',
    'image/bmp'                                                           => '/core/icons/file-image.svg_path',
    'image/avif'                                                          => '/core/icons/file-image.svg_path',
    'image/webp'                                                          => '/core/icons/file-image.svg_path',
    'image/vnd.microsoft.icon'                                            => '/core/icons/file-image.svg_path',
    'image/x-icon'                                                        => '/core/icons/file-image.svg_path',
    'image/tiff'                                                          => '/core/icons/file-image.svg_path',
    'image/jpeg'                                                          => '/core/icons/file-jpg.svg_path',
    'image/pjpeg'                                                         => '/core/icons/file-jpg.svg_path',
    'video/quicktime'                                                     => '/core/icons/file-mov.svg_path',
    'audio/mpeg'                                                          => '/core/icons/file-mp3.svg_path',
    'video/mp4'                                                           => '/core/icons/file-mp4.svg_path',
    'audio/wav'                                                           => '/core/icons/file-music.svg_path',
    'audio/x-wav'                                                         => '/core/icons/file-music.svg_path',
    'audio/ogg'                                                           => '/core/icons/file-music.svg_path',
    'application/ogg'                                                     => '/core/icons/file-music.svg_path',
    'application/pdf'                                                     => '/core/icons/file-pdf.svg_path',
    'image/apng'                                                          => '/core/icons/file-png.svg_path',
    'image/png'                                                           => '/core/icons/file-png.svg_path',
    'application/vnd.ms-powerpoint'                                       => '/core/icons/file-powerpoint.svg_path',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '/core/icons/file-powerpoint.svg_path',
    'image/svg+xml'                                                       => '/core/icons/file-svg.svg_path',
    'video/x-ms-wmv'                                                      => '/core/icons/file-video.svg_path',
    'video/x-flv'                                                         => '/core/icons/file-video.svg_path',
    'video/x-msvideo'                                                     => '/core/icons/file-video.svg_path',
    'video/avchd'                                                         => '/core/icons/file-video.svg_path',
    'video/webm'                                                          => '/core/icons/file-video.svg_path',
    'video/x-matroska'                                                    => '/core/icons/file-video.svg_path',
    'application/msword'                                                  => '/core/icons/file-word.svg_path',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '/core/icons/file-word.svg_path',
    'application/xml'                                                     => '/core/icons/file-xml.svg_path',
    'text/xml'                                                            => '/core/icons/file-xml.svg_path',
    'application/zip'                                                     => '/core/icons/file-zip.svg_path',
    'application/x-7z-compressed'                                         => '/core/icons/file-zipper.svg_path',
    'application/vnd.rar'                                                 => '/core/icons/file-zipper.svg_path',
    'application/java-archive'                                            => '/core/icons/file-zipper.svg_path',
    'application/x-tar'                                                   => '/core/icons/file-zipper.svg_path',
    'application/gzip'                                                    => '/core/icons/file-zipper.svg_path',
    'application/x-gzip'                                                  => '/core/icons/file-zipper.svg_path',
    'application/x-bzip2'                                                 => '/core/icons/file-zipper.svg_path',
    'application/x-lzip'                                                  => '/core/icons/file-zipper.svg_path',
    'application/x-xz'                                                    => '/core/icons/file-zipper.svg_path',
    'application/zstd'                                                    => '/core/icons/file-zipper.svg_path',
    'application/x-compress'                                              => '/core/icons/file-zipper.svg_path',
    'text/plain'                                                          => '/core/icons/file-lines.svg_path',
);

// mime types that have embedeed thumbnail, array can be left empty to disable thumbnails entirely
const FILE_MIMETYPE_THUMBNAIL_POSSIBLE = array(
    'image/jpeg',
);
