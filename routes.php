<?php

use Xitara\MediaConverter\Classes\ImageWrapper;
use Xitara\MediaConverter\Classes\VideoWrapper;

/**
 * image wrapper
 *
 * parameters:
 * media => media-path, base64 encoded
 * hash (optional) => user_session|user_id
 * size (optional) => width|[height]|[0|1 keep ratio]
 */
Route::get('/xmc-image/{id}/{config}/{width}/{hash?}', function ($id, $config, $width, $hash = null) {
    new ImageWrapper($id, $config, $width, $hash);
});

Route::get('/xmc-video/{id}/{config}/{width}/{hash?}', function ($id, $config, $width, $hash = null) {
    new VideoWrapper($id, $config, $width, $hash);
});
