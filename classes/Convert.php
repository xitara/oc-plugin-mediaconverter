<?php namespace Xitara\MediaConverter\Classes;

use Log;
use Storage;
use Xitara\MediaConverter\Models\MediaList;

/**
 * summary
 */
class Convert
{
    public static $targetPath = 'mediaconverter';

    /**
     * @todo put in config
     */
    private $sizes = [
        '100',
        '200',
        '300',
        '400',
        '500',
        '600',
        '700',
        '800',
        '900',
        '1000',
        'full',
    ];

    private $pixelSize = 25;

    /**
     * config
     * todo: put it in database
     */
    public function __construct()
    {
        // $this->targetPath = 'mediaconverter';
        $this->videoWidth = 1024;
        $this->imageWidth = 1024;
        $this->imageHeight = 1024;
        $this->faststartBin = '/usr/bin/qt-faststart';
        $this->ffmpegBin = '/usr/bin/ffmpeg';

        // var_dump(debug_backtrace());

        Log::debug(__METHOD__);
    }

    /**
     * summary
     */
    public static function register(String $owner, \System\Models\File $media)
    {
        Log::debug(__METHOD__);
        $converter = MediaList::where('file_name', $media->getLocalPath())->first();

        Log::info('Id: ' . $media->id);
        Log::info('File: ' . $media->getLocalPath());

        if ($converter === null) {
            Log::debug('Instantiate new MediaList');
            $converter = new MediaList;

        }

        if ($converter->status == 'completet' || $converter->status == 'error') {
            Log::debug('IsCompletet or IsError. JumpBack');
            return $converter;
        }

        $converter->media_id = $media->id;
        $converter->file_name = $media->getLocalPath();
        $converter->disk_name = $media->getFilename();
        $converter->content_type = $media->getContentType();
        $converter->owner_class = $owner;
        $converter->save();

        return $converter;
    }

    public function convert()
    {
        self::delete();

        Log::debug(__METHOD__);

        if (!Storage::exists(self::$targetPath)) {
            Log::info('mkdir: ' . self::$targetPath);
            Storage::makeDirectory(self::$targetPath, 0777);
        }
        self::$targetPath = storage_path('app/' . self::$targetPath);

        $medias = MediaList::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($medias as $media) {
            /**
             * generate target-folder
             */
            $this->owner = snake_case(str_replace('\\', '', $media->owner_class));

            $c = explode('/', $media->content_type);

            $media->status = 'progress';
            $media->save();

            switch ($c[0]) {
                case 'image':
                    $this->convertImage($media);
                    break;
                case 'video':
                    $this->convertVideo($media);
                    break;
                default:
                    break;
            }
        }
    }

    public static function delete()
    {
        Log::debug(__METHOD__);
        // self::__construct();

        // $targetPath = storage_path('app/' . self::$targetPath);
        $medias = MediaList::where('owner_id', null)->get();

        // var_dump($medias);

        foreach ($medias as $media) {
            /**
             * generate target-folder
             */
            $owner = snake_case(str_replace('\\', '', $media->owner_class));

            Log::info('DirToDelete:' . self::$targetPath . '/' . $owner . '/' . $media->media_id);

            // $path = pathinfo($media->file_name);
            // $path = storage_path('app/' . self::$targetPath) . '/' . $owner . '/' . $path['filename'];
            // $path = storage_path('app/' . self::$targetPath) . '/' . $owner . '/' . $media->media_id;

            $path = self::$targetPath . '/' . $owner . '/' . $media->media_id;
            Log::info('FilePath: ' . $path);
            if (Storage::exists($path)) {
                Storage::deleteDirectory($path);
            }

            // $files = glob($path . '.*');

            // foreach ($files as $file) {
            // Log::info('FileToDelete:' . $file);

            // $success = unlink($file);

            // if ($success === false) {
            // Log::error('FileDeleteError:' . $file);
            // }

            // }

            /**
             * check if dir is empty an delete emtpy dir
             */

        }

        // exit;
        MediaList::where('owner_id', null)->delete();
    }

    private function convertImage(MediaList $image)
    {
        Log::debug(__METHOD__);
        // Log::debug($image->owner_class);
        // exit;
        $class = '\\' . $image->owner_class;
        // Log::info('Class: ' . $class);
        // Log::info('Owner: ' . $image->owner_id);

        $object = $class::find($image->owner_id);

        if ($object !== null) {
            if (method_exists($object, 'getConfig')) {
                $datas = $object::getConfig($object);
            } else {
                $datas[2] = ['pixel_size' => $this->pixelSize];
            }

            foreach ($datas as $key => $data) {
                $converted[$key] = $this->pixelateImage($image->media_id, $key, $image->file_name, $data);
            }
        }

        if (!empty($converted)) {
            $image->converted = json_encode((object) $converted);
        }

        $image->status = 'completed';
        $image->error = null;

        $image->save();
        // exit;

        $success = MediaList::where('file_name', $image->file_name)
            ->update([
                'status' => 'completed',
            ]);

    }

    private function convertVideo(MediaList $video)
    {
        Log::debug(__METHOD__);
        // return;

        $targetPath = str_replace(storage_path() . '/app/', '', self::$targetPath);
        // Log::info('targetPath: ' . $targetPath);

        $targetPath .= '/' . $this->owner . '/' . $video->media_id;
        Log::info('targetPath: ' . $targetPath);

        if (!Storage::exists($targetPath)) {
            Log::info('mkdir: ' . $targetPath);
            Storage::makeDirectory($targetPath, 0777);
        }

        /**
         * add folder to path
         */
        // self::$targetPath .= '/' . $video->id;

        $success = [];

        Log::info('FileName: ' . $video->file_name);
        $path = pathinfo($video->file_name);
        $filename = $path['filename'];
        $extension = $path['extension'];
        $targetFile = storage_path('app/' . $targetPath . '/' . $filename); // without extension

        unset($path['extension']);
        $file_name = $path['dirname'] . '/' . $path['filename'];
        Log::info('FileName: ' . $file_name);
        Log::info('TargetName: ' . $targetFile);
        // exit;

        /**
         * convert to mp4
         */
        $command = $this->ffmpegBin . ' -y -i ' . $video->file_name;
        $command .= ' -b:v 1500k -vf "scale=' . $this->videoWidth;
        $command .= ':-2" -vcodec libx264 -preset slow -g 30 ' . $targetFile . '.mp4';
        Log::debug('Exec: ' . $command);
        exec($command, $output['mp4'], $success['mp4']);

        /**
         * qt-faststart on mp4
         */
        rename($targetFile . '.mp4', $targetFile . '.temp.mp4');
        $command = $this->faststartBin . ' ' . $targetFile . '.temp.mp4';
        $command .= ' ' . $targetFile . '.mp4';
        Log::debug('Exec: ' . $command);
        exec($command, $output['qtfs'], $success['qtfs']);
        unlink($targetFile . '.temp.mp4');

        /**
         * convert to ogv
         */
        $command = $this->ffmpegBin . ' -y -i ' . $targetFile . '.mp4';
        $command .= ' -q:v 10 -c:v libtheora -c:a libvorbis -threads 16 -speed 4 ' . $targetFile . '.ogv';
        Log::debug('Exec: ' . $command);
        exec($command, $output['ogv'], $success['ogg']);

        /**
         * convert to webm
         */
        $command = $this->ffmpegBin . ' -y -i ' . $targetFile . '.mp4';
        $command .= ' -c:v libvpx -crf 10 -b:v 1M -c:a libvorbis -threads 16 -speed 4 ' . $targetFile . '.webm';
        Log::debug('Exec: ' . $command);
        exec($command, $output['webm'], $success['webm']);

        foreach ($success as $key => $item) {
            Log::info('Success::' . $key . ' => ' . $item);

            if ($item == 1) {
                $error[$key] = 'error';
            } elseif ($key != 'qtfs') {
                $converted['video/' . $key] = str_replace(storage_path() . '/', '', $targetFile) . '.' . $key;
            }
        }

        // var_dump($output);
        // exit;

        if (isset($error)) {
            // $success = MediaList::where('file_name', $video->file_name . '.' . $extension)
            // ->update([
            // 'status' => 'error',
            // 'error' => json_encode((object) $error),
            // ]);
            $video->status = 'error';
            $video->error = json_encode((object) $error);
            $video->save();

            return false;
        }

        $video->status = 'completed';
        $video->error = null;

        if (!empty($converted)) {
            $video->converted = json_encode((object) $converted);
        }

        $video->save();

        $success = MediaList::where('file_name', $video->file_name . '.' . $extension)
            ->update([
                'status' => 'completed',
            ]);
    }

    private function pixelateImage(Int $id, Int $configId, String $filePath, array $data): Object
    {
        $targetPath = str_replace(storage_path() . '/app/', '', self::$targetPath);
        Log::info('targetPath: ' . $targetPath);

        $targetPath .= '/' . $this->owner . '/' . $id;
        Log::info('targetPath: ' . $targetPath);

        if (!Storage::exists($targetPath)) {
            Log::info('mkdir: ' . $targetPath);
            Storage::makeDirectory($targetPath, 0777);
        }

        $im = new \Imagick($filePath);
        $orientation = $im->getImageOrientation();

        switch ($orientation) {
            case \Imagick::ORIENTATION_BOTTOMRIGHT:
                $im->rotateimage("#000", 180); // rotate 180 degrees
                break;

            case \Imagick::ORIENTATION_RIGHTTOP:
                $im->rotateimage("#000", 90); // rotate 90 degrees CW
                break;

            case \Imagick::ORIENTATION_LEFTBOTTOM:
                $im->rotateimage("#000", -90); // rotate 90 degrees CCW
                break;
        }

        $im->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);

        /**
         * get filename
         * @var String
         */
        $fileName = pathinfo($filePath, PATHINFO_BASENAME);

        /**
         * add folder to path
         */
        // self::$targetPath .= '/' . $id;

        /**
         * create scaled images
         */
        $converted = [];
        foreach ($this->sizes as $size) {
            $im2 = clone $im;

            /**
             * scale image if not full
             */
            if ($size != 'full') {
                $im2->scaleImage($size, 0);
            }

            /**
             * save unpixeled image
             */
            $file = 'app/' . $targetPath . '/' . $configId . '_scaled_' . $size . '_' . $fileName;
            $converted[] = $file;
            Log::info('File: ' . storage_path($file));
            $im2->writeImage(storage_path($file));

            /**
             * pixelate image
             */
            $width = $im2->getImageWidth();
            // $pixel = $width / $data['pixel_size'];
            $pixel = 100 / (100 / $data['pixel_size']);

            $im2->scaleImage($pixel, 0);
            $im2->scaleImage($width, 0);

            $file = 'app/' . $targetPath . '/' . $configId . '_pixeled_' . $size . '_' . $fileName;
            $converted[] = $file;
            Log::info('File: ' . storage_path($file));
            $im2->writeImage(storage_path($file));

        }

        return (object) $converted;
    }
}
