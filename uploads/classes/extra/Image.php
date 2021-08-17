<?php namespace Diveramkt\Uploads\Classes\Extra;

// use ToughDeveloper\ImageResizer\Models\Settings;
use File as FileHelper;
use October\Rain\Database\Attach\File;
use Diveramkt\Uploads\Models\Settings;
use Http;
use Storage;
// use Tinify\Tinify;
// use Tinify\Source;
use Cache;

class Image
{
    /**
     * File path of image
     */
    protected $filePath;

    /**
     * Image Resizer Settings
     */
    protected $settings;

    /**
     * File Object
     */
    protected $file;

    /**
     * Options Array
     */
    protected $options;

    /**
     * Thumb filename
     */
    protected $thumbFilename;

    public function __construct($filePath = false)
    {
        $this->s3=$this->isS3();

        // Settings are needed often, so offset to variable
        $this->settings = Settings::instance();

        $this->settings->enable_tinypng=false;
        $this->settings->default_extension='auto';
        $this->settings->default_mode='auto';

        $this->settings->default_offset_x=0;
        $this->settings->default_offset_y=0;
        $this->settings->default_quality=95;
        $this->settings->default_sharpen=0;

        // Create a new file object
        $this->file = new File;
        if($this->s3) $this->filePath=$filePath;
        else{
            if ($filePath instanceof File) {
                $this->filePath = $filePath->getLocalPath();
                return;
            }

            $this->filePath = (file_exists($filePath))
            ? $filePath
            : $this->parseFileName($filePath);
        }
    }


    protected $s3=null;
    public function isS3(){
        if(config('cms.storage.uploads.disk') != 'local' || config('cms.storage.uploads.disk') == 's3') return true;
        else return false;
    }
    public function storage_path($path='', $public = false){
        // if($this->s3){
        if(config('cms.storage.uploads.disk') != 'local' || config('cms.storage.uploads.disk') == 's3'){
            if($path) $path='/'.$path;
            return str_replace(['uploads/uploads/'],['uploads/'],config('cms.storage.uploads.path').$path);
        }else{
            if ($public === true) return url('/storage/app/' . $path);
            return storage_path('app/'.$path);
        }
    }

    public function checkFile($path=false, $externo=0){
        if(!$path) $path=$this->filePath;
        if($this->s3 || $externo){
         $response = Http::get($path);
         if($response->code == 200) return true;
         else return false;
     }elseif (is_file($path)) return true;

     return false;
 }

    /**
     * Resizes an Image
     *
     * @param integer $width The target width
     * @param integer $height The target height
     * @param array   $options The options
     *
     * @return string
     */
    public function resize($width = false, $height = false, $options = [])
    {

        // Parse the default settings
        $this->options = $this->parseDefaultSettings($options);

        if(isset($this->settings->resize_max_width) && $this->settings->resize_max_width && $this->options['mode'] != 'crop'){
            $cache_resize='getimagesize_'.$this->filePath;
            if (!Cache::has($cache_resize)) {
                $imgsize=getimagesize(str_replace(' ', '%20', $this->filePath));
                Cache::put($cache_resize, serialize($imgsize), 999999999999999999);
            }else $imgsize=unserialize(Cache::get($cache_resize));

            if(isset($imgsize[0]) && $width > $imgsize[0]) $width=$imgsize[0];
            if(isset($imgsize[1]) && $height > $imgsize[1]) $height=$imgsize[1];
        }

        // Not a file? Display the not found image
        // if (!is_file($this->filePath)) {
        if(!$this->checkFile()){
            return $this->notFoundImage($width, $height);
        }

        // echo 'teste';
        // If extension is auto, set the actual extension
        if (strtolower($this->options['extension']) == 'auto') {
           $this->options['extension'] = pathinfo($this->filePath)['extension'];
       }

        // Set a disk name, this enables caching
       $this->file->disk_name = $this->diskName();

        // Set the thumbfilename to save passing variables to many functions
       $this->thumbFilename = $this->getThumbFilename($width, $height);

        // If the image is cached, don't try resized it.
       if (!$this->isImageCached()) {

            // Set the file to be created from another file
        if($this->s3) $this->file->fromUrl($this->filePath);
        else $this->file->fromFile($this->filePath);

            // Resize it
        $thumb = $this->file->getThumb($width, $height, $this->options);

            // Not a gif file? Compress with tinyPNG
            // if ($this->isCompressionEnabled()) {
            //     $this->compressWithTinyPng();
            // }

        if(!$this->s3 && !$this->isS3()){
            // Touch the cached image with the original mtime to align them
            touch($this->getCachedImagePath(), filemtime($this->filePath));
            $this->deleteTempFile();
            // touch($this->getCachedImagePath(), strtotime(date('Y-m-d H:i:s')));
        }
    }

        // Return the URL
    // if($path_rename) return $path_rename;
    // else return $this;
    return $this;
}

public function getDisk()
{
    return Storage::disk();
}
protected function copyLocalToStorage($localPath, $storagePath)
{
    return $this->getDisk()->put($storagePath, FileHelper::get($localPath), $this->isPublic() ? 'public' : null);
}

protected function getPathRename($base=true){

    $infos=pathinfo($this->thumbFilename);
    if(!$this->options['rename']) return false;

    $path_rename='storage/app/'.$this->file->getDiskPath($infos['filename']);
    if($this->s3){

    }else{

        if (
            !FileHelper::isDirectory($path_rename) &&
            !FileHelper::makeDirectory($path_rename, 0777, true, true) &&
            !FileHelper::isDirectory($path_rename)
        ) {
            trigger_error(error_get_last(), E_USER_WARNING);
        }

    }

    // $path_rename=url($path_rename)
    if($base) return url($path_rename).'/'.$this->options['rename'].'.'.pathinfo($this->filePath)['extension'];
    else return $path_rename.'/'.$this->options['rename'].'.'.pathinfo($this->filePath)['extension'];
}

protected function copyRename($public=false){
    $path_rename=$this->getPathRename(0);
    
    if($path_rename){

        if($this->s3){
            // $path1='storage/app/'.$this->file->getDiskPath($this->thumbFilename);
            // $filePath = $this->file->getStorageDirectory() . $this->getPartitionDirectory() . $this->thumbFilename;
            // $path1=$this->storage_path($filePath, $public);
            // print_r($path1);
            // print_r($path_rename);
            // $this->copyLocalToStorage($path1, $path_rename);
        }else{
            $path1='storage/app/'.$this->file->getDiskPath($this->thumbFilename);
            if(!FileHelper::exists($path_rename)){
                if(!FileHelper::exists($path1) || !FileHelper::copy($path1, $path_rename)) return false;
            }
        }

        if ($public === true) return url($path_rename);
        return storage_path(str_replace('storage/app/', 'app/', $path_rename));
    }
    return false;
}

    /**
     * Gets the path for the thumbnail
     * @return string
     */
    public function getCachedImagePath($public = false)
    {

        $filePath = $this->file->getStorageDirectory() . $this->getPartitionDirectory() . $this->thumbFilename;

        if($this->options['rename'] && !$this->s3){
            $img=$this->copyRename($public);
            if($img) return $img;
        }

        return $this->storage_path($filePath, $public);

        // if ($public === true) {
        //     return url('/storage/app/' . $filePath);
        // }

        // return storage_path('app/' . $filePath);
    }

    protected function deleteTempFile()
    {
        // $path = storage_path('app/' . $this->file->getStorageDirectory() . $this->getPartitionDirectory() . $this->file->disk_name);
        $path = $this->storage_path($this->file->getStorageDirectory() . $this->getPartitionDirectory() . $this->file->disk_name);
        // if (file_exists($path)) {
        if($this->checkFile($path)){
            unlink($path);
        }
    }

    /**
     * Parse the file name to get a relative path for the file
     * This is mostly required for scenarios where a twig filter, e.g. theme has been applied.
     * @return string
     */
    protected function parseFileName($filePath)
    {
        $path = urldecode(parse_url($filePath, PHP_URL_PATH));

        // Create array of commonly used folders
        // These will be used to try capture the actual file path to an image without the sub-directory path
        $folders = [
            config('cms.themesPath'),
            config('cms.pluginsPath'),
            config('cms.storage.uploads.path'),
            config('cms.storage.media.path')
        ];

        foreach($folders as $folder)
        {
            if (str_contains($path, $folder))
            {
                $paths = explode($folder, $path, 2);
                return base_path($folder . end($paths));
            }
        }

        return base_path($path);
    }

    /**
     * Works out the default settings
     * @return string
     */
    protected function parseDefaultSettings($options = [])
    {
        if (!isset($options['mode']) && $this->settings->default_mode) {
            $options['mode'] = $this->settings->default_mode;
        }
        if (!isset($options['offset'])) {
        // if (!isset($options['offset']) && is_int($this->settings->default_offset_x) && is_int($this->settings->default_offset_y)) {
            $options['offset'] = [$this->settings->default_offset_x, $this->settings->default_offset_y];
        }
        if (!isset($options['extension']) && $this->settings->default_extension) {
            $options['extension'] = $this->settings->default_extension;

            if(isset($this->settings->converter_webp) && $this->settings->converter_webp){
            // https://stackoverflow.com/questions/18164070/detect-if-browser-supports-webp-format-server-side
                if( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false ) $options['extension']='webp';
            }
        }
        if (!isset($options['quality']) && is_int($this->settings->default_quality)) {
            $options['quality'] = $this->settings->default_quality;
        }
        if (!isset($options['sharpen']) && is_int($this->settings->default_sharpen)) {
            $options['sharpen'] = $this->settings->default_sharpen;
        }
        if (!isset($options['compress'])) {
            $options['compress'] = true;
        }

        if (!isset($options['rename'])) $options['rename'] = false;

        return $options;
    }

    /**
     * Creates a unique disk name for an image
     * @return string
     */
    protected function diskName()
    {
        $diskName = $this->filePath;

        // Ensures a unique filepath when tinypng compression is enabled
        if ($this->isCompressionEnabled()) {
            $diskName .= 'tinypng';
        }

        return md5($diskName);
    }

    /**
     * Serves a not found image
     * @return string
     */
    protected function notFoundImage($width, $height)
    {
        // Have we got a custom not found image? If so, serve this.
        // if ($this->settings->not_found_image) {
        //     $imagePath = base_path() . config('cms.storage.media.path') . $this->settings->not_found_image;
        // }

        // If we do not have an existing custom not found image, use the default from this plugin
        // if (!isset($imagePath) || !file_exists($imagePath)) {
            // $imagePath = plugins_path('toughdeveloper/imageresizer/assets/default-not-found.jpg');
            // $imagePath = plugins_path('diveramkt/uploads/assets/images/no_image.jpg');
        $imagePath = 'plugins/diveramkt/uploads/assets/images/no_image.jpg';
        // }

        // Create a new Image object to resize
        $file = new Self($imagePath);
        $file->s3=false;

        // Return in the specified dimensions
        return $file->resize($width, $height, [
            'mode' => 'crop'
        ]);
    }

    /**
     * Compresses a png image using tinyPNG
     * @return string
     */
    protected function compressWithTinyPng()
    {
        // try {
        //     Tinify::setKey($this->settings->tinypng_developer_key);

        //     $filePath = $this->getCachedImagePath();
        //     $source = Source::fromFile($filePath);
        //     $source->toFile($filePath);
        // }
        // catch (\Exception $e) {
        //     // Log error - may help debug
        //     \Log::error('Tiny PNG compress failed', [
        //         'message'   => $e->getMessage(),
        //         'code'      => $e->getCode()
        //     ]);
        // }

    }

    /**
     * Checks if the requested resize/compressed image is already cached.
     * Removes the cached image if the original image has a different mtime.
     *
     * @return bool
     */
    protected function isImageCached()
    {

        if($this->isS3()){
            if($this->checkFile($this->getCachedImagePath(),1)) return true;
            else return false;
        }else{

        // if there is no cached image return false
            if (!is_file($cached_img = $this->getCachedImagePath())) {
                return false;
            }

        // if cached image mtime match, the image is already cached
            if (filemtime($this->filePath) === filemtime($cached_img)) {
                return true;
            }

        // delete older cached file
            unlink($cached_img);
        }

        // generate new cache file
        return false;
    }

    /**
     * Checks if image compression is enabled for this image.
     * @return bool
     */
    protected function isCompressionEnabled()
    {
        return ($this->options['extension'] != 'gif' && $this->settings->enable_tinypng && $this->options['compress']);
    }

    /**
    * Generates a partition for the file.
    * return /ABC/DE1/234 for an name of ABCDE1234.
    * @param Attachment $attachment
    * @param string $styleName
    * @return mixed
    */
    protected function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->diskName(), 3), 0, 3)) . '/';
    }

    /**
     * Generates a thumbnail filename.
     * @return string
     */
    protected function getThumbFilename($width, $height)
    {
        $width = (integer) $width;
        $height = (integer) $height;

        return 'thumb__' . $width . '_' . $height . '_' . $this->options['offset'][0] . '_' . $this->options['offset'][1] . '_' . $this->options['mode'] . '.' . $this->options['extension'];
    }

    /**
     * Render an image tag
     * @return string
     */
    public function render()
    {
        return '<img src="' . $this . '" />';
    }

    /**
     * Magic method to return the file path
     * @return string
     */
    public function __toString()
    {
        return $this->getCachedImagePath(true);
    }
}
