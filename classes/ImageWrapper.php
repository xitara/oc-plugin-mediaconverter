<?php namespace Xitara\MediaConverter\Classes;

use Log;
use Xitara\MediaConverter\Classes\Convert;
use Xitara\MediaConverter\Classes\EroWrapper;
use Xitara\MediaConverter\Models\MediaList;

/**
 * summary
 */
class ImageWrapper
{
    /**
     * summary
     */
    public function __construct($id, $configId, $width, $hash = null)
    {

        /**
         * get media
         */
        $media = MediaList::where('media_id', $id)->first();

        /**
         * get config if exists
         */
        Log::info('Owner: ' . $media->owner_class);

        if ($configId > 0 && method_exists($media->owner_class, 'getConfig')) {
            $displayStatus = (($media->owner_class)::getConfig($id))[$configId]['display_status'];

            /**
             * show blank if hash is null and display = user
             */
            if ($hash === null && $displayStatus == 'user') {
                return;
            }
        }

        if ($configId == 0) {
            $displayStatus = 'guest';
            $configId = 1;
        }

        /**
         * check permission to image
         */
        $ecms = new EroWrapper();
        if ($hash !== null) {
            $userdata = $ecms->userdataFromSession(null, $hash);
        }

        $display = 'pixeled';
        if (isset($userdata) && $userdata !== false) {
            $display = 'scaled';
        }

        if (isset($displayStatus) && $displayStatus == 'guest') {
            $display = 'scaled';
        }

        /**
         * generate filepath
         */
        $file = snake_case(str_replace('\\', '', $media->owner_class)) . '/';
        $file .= $id . '/';
        $file .= $configId . '_';
        $file .= $display . '_';
        $file .= $width . '_';
        $file .= pathinfo($media->file_name, PATHINFO_BASENAME);

        $file = storage_path('app/' . Convert::$targetPath . '/' . $file);
        $mime = image_type_to_mime_type(exif_imagetype($file));

        Log::info('File: ' . $file);

        header("Content-type: " . $mime);
        readfile($file);

        // echo ($file);
    }
}
