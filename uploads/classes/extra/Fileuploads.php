<?php namespace Diveramkt\Uploads\Classes\Extra;
// Vendor\Cctober\Rain\Database\Attach\File;

use Cache;
use Storage;
use File as FileHelper;
use October\Rain\Network\Http;
use October\Rain\Database\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as FileObj;
use Exception;
use October\Rain\Database\Attach\Resizer;
use Gregwar\Image\Image;
use System\Classes\MediaLibrary;

/**
 * File attachment model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Fileuploads extends Model
{
    // use \October\Rain\Database\Traits\Sortable;

    /**
     * @var string The table associated with the model.
     */
    protected $table = 'files';

    /**
     * Relations
     */
    public $morphTo = [
        'attachment' => []
    ];
    
    /**
     * @var array The attributes that are mass assignable.
     */
    protected $fillable = [
        'file_name',
        'title',
        'description',
        'field',
        'attachment_id',
        'attachment_type',
        'is_public',
        'sort_order',
        'data',
    ];

    protected $filesave='', $folder='', $link_image=null, $file_name='', $disk_name='', $success=false, $file_insert=false, $infos_results=[];
    public $resize_width='auto', $max_width=null, $max_height=null, $resize_height='auto', $resize_options=[];

    /**
     * @var array The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * @var array Known image extensions.
     */
    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @var array Hidden fields from array/json access
     */
    protected $hidden = ['attachment_type', 'attachment_id', 'is_public'];

    /**
     * @var array Add fields to array/json access
     */
    protected $appends = ['path', 'extension'];

    /**
     * @var mixed A local file name or an instance of an uploaded file,
     * objects of the \Symfony\Component\HttpFoundation\File\UploadedFile class.
     */
    public $data = null;

    /**
     * @var array Mime types
     */
    protected $autoMimeTypes = [
        'docx' => 'application/msword',
        'xlsx' => 'application/excel',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        'svg'  => 'image/svg+xml',
    ];

    //
    // Constructors
    //


    public function setFolder($folder=false, $link_image=false){
        if($folder) $this->folder=trim(str_replace(' /','',' '.$folder));
        if($link_image) $this->link_image=$link_image;
    }
    public function setRename($file_name=false){ if($file_name) $this->file_name=$file_name; }

    public function getFolder(){ return $this->folder; }
    public function getFilesave(){ return $this->filesave; }
    public function success(){ return $this->success; }
    public function getResult(){
        $return=[
            'success' => $this->success,
            'filesave' => $this->filesave,
            'file_name' => $this->file_name,
            'folder' => $this->folder,
            'infos_results' => $this->infos_results,
        ];
        return $return;
    }

    static public function isExterno(){
        if(config('cms.storage.uploads.disk') != 'local') return true;
        else return false;
    }
    public function checkFile($path=false, $externo=0){

        if(!$path) return;
        $path=trim($path);
        if (!$this->isLocalStorage() || $externo) {
        // if(self::isExterno() || $externo){

            $response = Http::get($path);
            if($response->code == 200) return true;
            else return false;
        }else{
            $path=trim(str_replace([url('/'),' /','%20'], ['','',' '], ' '.$path));
            if(is_file($path)) return true;
        }

        return false;
    }

    /**
     * Creates a file object from a file an uploaded file.
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
     */
    public function fromPost($uploadedFile)
    {
        if(!$this->file_insert) $this->file_insert=$uploadedFile;
        if ($uploadedFile === null) {
            return;
        }

        $this->file_name = $uploadedFile->getClientOriginalName();
        $this->file_size = $uploadedFile->getClientSize();
        $this->content_type = $uploadedFile->getMimeType();
        $this->disk_name = $this->getDiskName();

        /*
         * getRealPath() can be empty for some environments (IIS)
         */
        $realPath = empty(trim($uploadedFile->getRealPath()))
        ? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
        : $uploadedFile->getRealPath();

        $this->putFile($realPath, $this->disk_name);

        return $this;
    }

    /**
     * Creates a file object from a file on the disk.
     */
    public function fromFile($filePath)
    {
        if(!$this->file_insert) $this->file_insert=$filePath;
        if ($filePath === null) {
            return;
        }

        return $this->putFile($filePath);
    }

    /**
     * Creates a file object from raw data.
     *
     * @param $data string Raw data
     * @param $filename string Filename
     *
     * @return $this
     */
    public function fromData($data, $filename)
    {
        if ($data === null) {
            return;
        }

        $tempPath = temp_path($filename);
        FileHelper::put($tempPath, $data);

        $file = $this->fromFile($tempPath);
        FileHelper::delete($tempPath);

        return $file;
    }

    /**
     * Creates a file object from url
     * @param $url string URL
     * @param $filename string Filename
     * @return $this
     */
    public function fromUrl($url, $filename = null)
    {

        $this->file_insert=$url;
        $data = Http::get($url);

        if ($data->code != 200) {
            throw new Exception(sprintf('Error getting file "%s", error code: %d', $data->url, $data->code));
        }

        if (empty($filename)) {
            // Parse the URL to get the path info
            $filePath = parse_url($data->url, PHP_URL_PATH);

            // Get the filename from the path
            $filename = pathinfo($filePath)['filename'];

            // Attempt to detect the extension from the reported Content-Type, fall back to the original path extension if not able to guess
            $mimesToExt = array_flip($this->autoMimeTypes);
            if (!empty($data->headers['Content-Type']) && isset($mimesToExt[$data->headers['Content-Type']])) {
                $ext = $mimesToExt[$data->headers['Content-Type']];
            } else {
                $ext = pathinfo($filePath)['extension'];
            }

            // Generate the filename
            $filename = "{$filename}.{$ext}";
        }

        return $this->fromData($data, $filename);
    }

    //
    // Attribute mutators
    //

    /**
     * Helper attribute for getPath.
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->getPath();
    }

    /**
     * Helper attribute for getExtension.
     * @return string
     */
    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }

    /**
     * Used only when filling attributes.
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->data = $value;
    }

    /**
     * Helper attribute for get image width.
     * @return string
     */
    public function getWidthAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[0];
        }
    }

    /**
     * Helper attribute for get image height.
     * @return string
     */
    public function getHeightAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[1];
        }
    }

    /**
     * Helper attribute for file size in human format.
     * @return string
     */
    public function getSizeAttribute()
    {
        return $this->sizeToString();
    }

    //
    // Raw output
    //

    /**
     * Outputs the raw file contents.
     *
     * @param string $disposition The Content-Disposition to set, defaults to inline
     * @param bool $returnResponse Defaults to false, returns a Response object instead of directly outputting to the browser
     * @return Response | void
     */
    public function output($disposition = 'inline', $returnResponse = false)
    {
        $response = response($this->getContents())->withHeaders([
            'Content-type'        => $this->getContentType(),
            'Content-Disposition' => $disposition . '; filename="' . $this->file_name . '"',
            'Cache-Control'       => 'private, no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Accept-Ranges'       => 'bytes',
            'Content-Length'      => $this->file_size,
        ]);

        if ($returnResponse) {
            return $response;
        } else {
            $response->sendHeaders();
            $response->sendContent();
        }
    }

    /**
     * Outputs the raw thumbfile contents.
     *
     * @param integer $width
     * @param integer $height
     * @param array $options [
     *                  'mode'      => 'auto',
     *                  'offset'    => [0, 0],
     *                  'quality'   => 90,
     *                  'sharpen'   => 0,
     *                  'interlace' => false,
     *                  'extension' => 'auto',
     *                  'disposition' => 'inline',
     *              ]
     * @param bool $returnResponse Defaults to false, returns a Response object instead of directly outputting to the browser
     * @return Response | void
     */
    public function outputThumb($width, $height, $options = [], $returnResponse = false)
    {
        $disposition = array_get($options, 'disposition', 'inline');
        $options = $this->getDefaultThumbOptions($options);
        $this->getThumb($width, $height, $options);
        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $contents = $this->getContents($thumbFile);

        $response = response($contents)->withHeaders([
            'Content-type'        => $this->getContentType(),
            'Content-Disposition' => $disposition . '; filename="' . basename($thumbFile) . '"',
            'Cache-Control'       => 'private, no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Accept-Ranges'       => 'bytes',
            'Content-Length'      => mb_strlen($contents, '8bit'),
        ]);

        if ($returnResponse) {
            return $response;
        } else {
            $response->sendHeaders();
            $response->sendContent();
        }
    }

    //
    // Getters
    //

    /**
     * Returns the cache key used for the hasFile method
     *
     * @param string $path The path to get the cache key for
     * @return string
     */
    public function getCacheKey($path = null)
    {
        if (empty($path)) {
            $path = $this->getDiskPath();
        }

        return 'file_exists::' . $path;
    }

    /**
     * Returns the file name without path
     */
    public function getFilename()
    {
        return $this->file_name;
    }

    /**
     * Returns the file extension.
     */
    public function getExtension()
    {
        return FileHelper::extension($this->file_name);
    }

    /**
     * Returns the last modification date as a UNIX timestamp.
     * @return int
     */
    public function getLastModified($fileName = null)
    {
        return $this->storageCmd('lastModified', $this->getDiskPath($fileName));
    }

    /**
     * Returns the file content type.
     */
    public function getContentType()
    {
        if ($this->content_type !== null) {
            return $this->content_type;
        }

        $ext = $this->getExtension();
        if (isset($this->autoMimeTypes[$ext])) {
            return $this->content_type = $this->autoMimeTypes[$ext];
        }

        return null;
    }

    /**
     * Get file contents from storage device.
     */
    public function getContents($fileName = null)
    {
        return $this->storageCmd('get', $this->getDiskPath($fileName));
    }

    /**
     * Returns the public address to access the file.
     */
    public function getPath($fileName = null)
    {
        if (empty($fileName)) {
            $fileName = $this->disk_name;
        }
        return $this->getPublicPath() . $this->getPartitionDirectory() . $fileName;
    }

    /**
     * Returns a local path to this file. If the file is stored remotely,
     * it will be downloaded to a temporary directory.
     */
    public function getLocalPath()
    {
        if ($this->isLocalStorage()) {
            return $this->getLocalRootPath() . '/' . $this->getDiskPath();
        }

        $itemSignature = md5($this->getPath()) . $this->getLastModified();

        $cachePath = $this->getLocalTempPath($itemSignature . '.' . $this->getExtension());

        if (!FileHelper::exists($cachePath)) {
            $this->copyStorageToLocal($this->getDiskPath(), $cachePath);
        }

        return $cachePath;
    }

    /**
     * Returns the path to the file, relative to the storage disk.
     * @return string
     */
    public function getDiskPath($fileName = null)
    {
        if (empty($fileName)) {
            $fileName = $this->disk_name;
        }
        return $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName;
    }

    /**
     * Determines if the file is flagged "public" or not.
     */
    public function isPublic()
    {
        if (array_key_exists('is_public', $this->attributes)) {
            return $this->attributes['is_public'];
        }

        if (isset($this->is_public)) {
            return $this->is_public;
        }

        return true;
    }

    /**
     * Returns the file size as string.
     * @return string Returns the size as string.
     */
    public function sizeToString()
    {
        return FileHelper::sizeToString($this->file_size);
    }

    //
    // Events
    //

    /**
     * Before the model is saved
     * - check if new file data has been supplied, eg: $model->data = Input::file('something');
     */
    public function beforeSave()
    {
        /*
         * Process the data property
         */
        if ($this->data !== null) {
            if ($this->data instanceof UploadedFile) {
                $this->fromPost($this->data);
            }
            else {
                $this->fromFile($this->data);
            }

            $this->data = null;
        }
    }

    /**
     * After model is deleted
     * - clean up it's thumbnails
     */
    public function afterDelete()
    {
        try {
            $this->deleteThumbs();
            $this->deleteFile();
        }
        catch (Exception $ex) {
        }
    }

    //
    // Image handling
    //

    /**
     * Checks if the file extension is an image and returns true or false.
     */
    public function isImage()
    {
        return in_array(strtolower($this->getExtension()), static::$imageExtensions);
    }

    /**
     * Get image dimensions
     * @return array|bool
     */
    protected function getImageDimensions()
    {
        return getimagesize($this->getLocalPath());
    }

    /**
     * Generates and returns a thumbnail path.
     *
     * @param integer $width
     * @param integer $height
     * @param array $options [
     *                  'mode'      => 'auto',
     *                  'offset'    => [0, 0],
     *                  'quality'   => 90,
     *                  'sharpen'   => 0,
     *                  'interlace' => false,
     *                  'extension' => 'auto',
     *              ]
     * @return string The URL to the generated thumbnail
     */
    public function getThumb($width, $height, $options = [])
    {
        if (!$this->isImage()) {
            return $this->getPath();
        }

        $width = (int) $width;
        $height = (int) $height;

        $options = $this->getDefaultThumbOptions($options);

        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $thumbPath = $this->getDiskPath($thumbFile);
        $thumbPublic = $this->getPath($thumbFile);

        if (!$this->hasFile($thumbFile)) {
            if ($this->isLocalStorage()) {
                $this->makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options);
            }
            else {
                $this->makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options);
            }
        }

        return $thumbPublic;
    }

    /**
     * Generates a thumbnail filename.
     * @return string
     */
    public function getThumbFilename($width, $height, $options)
    {
        $options = $this->getDefaultThumbOptions($options);
        return 'thumb_' . $this->id . '_' . $width . '_' . $height . '_' . $options['offset'][0] . '_' . $options['offset'][1] . '_' . $options['mode'] . '.' . $options['extension'];
    }

    /**
     * Returns the default thumbnail options.
     * @return array
     */
    protected function getDefaultThumbOptions($overrideOptions = [])
    {
        $defaultOptions = [
            'mode'      => 'auto',
            'offset'    => [0, 0],
            'quality'   => 90,
            'sharpen'   => 0,
            'interlace' => false,
            'extension' => 'auto',
        ];

        if (!is_array($overrideOptions)) {
            $overrideOptions = ['mode' => $overrideOptions];
        }

        $options = array_merge($defaultOptions, $overrideOptions);

        $options['mode'] = strtolower($options['mode']);

        if (strtolower($options['extension']) == 'auto') {
            $options['extension'] = strtolower($this->getExtension());
        }

        return $options;
    }

    /**
     * Generate the thumbnail based on the local file system. This step is necessary
     * to simplify things and ensure the correct file permissions are given
     * to the local files.
     */
    protected function makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options)
    {
        $rootPath = $this->getLocalRootPath();
        $filePath = $rootPath.'/'.$this->getDiskPath();
        $thumbPath = $rootPath.'/'.$thumbPath;

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($thumbPath);
        }
        /*
         * Generate thumbnail
         */
        else {
            try {
                Resizer::open($filePath)
                ->resize($width, $height, $options)
                ->save($thumbPath)
                ;
            }
            catch (Exception $ex) {
                BrokenImage::copyTo($thumbPath);
            }
        }

        FileHelper::chmod($thumbPath);
    }

    /**
     * Generate the thumbnail based on a remote storage engine.
     */
    protected function makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options)
    {
        $tempFile = $this->getLocalTempPath();
        $tempThumb = $this->getLocalTempPath($thumbFile);

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($tempThumb);
        }
        /*
         * Generate thumbnail
         */
        else {
            $this->copyStorageToLocal($this->getDiskPath(), $tempFile);

            try {
                Resizer::open($tempFile)
                ->resize($width, $height, $options)
                ->save($tempThumb)
                ;
            }
            catch (Exception $ex) {
                BrokenImage::copyTo($tempThumb);
            }

            FileHelper::delete($tempFile);
        }

        /*
         * Publish to storage and clean up
         */
        $this->copyLocalToStorage($tempThumb, $thumbPath);
        FileHelper::delete($tempThumb);
    }

    /*
     * Delete all thumbnails for this file.
     */
    public function deleteThumbs()
    {
        $pattern = 'thumb_'.$this->id.'_';

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $allFiles = $this->storageCmd('files', $directory);
        $collection = [];
        foreach ($allFiles as $file) {
            if (starts_with(basename($file), $pattern)) {
                $collection[] = $file;
            }
        }

        /*
         * Delete the collection of files
         */
        if (!empty($collection)) {
            if ($this->isLocalStorage()) {
                FileHelper::delete($collection);
            }
            else {
                $this->getDisk()->delete($collection);
            }
        }
    }


    public function deleteFileUrl($image=false, $local='uploads')
    {
        /*
         * Delete the collection of files
         */

        $infos=pathinfo($this->base_link_file($image, $local));
        $url=trim(str_replace([' /','%20'], ['',' '], ' '.$infos['dirname'].'/'.$infos['basename']));

        // $image=str_replace([url('/').'/','%20'], ['',' '], $image);
        if ($image && !empty($image)) {
            if ($this->isLocalStorage()) FileHelper::delete($url);
            else $this->getDisk()->delete($url);
        }
    }

    public function base_link_file($url, $folder='uploads'){
        if(config('cms.storage.'.$folder.'.disk') == 'local') return urldecode(parse_url($url, PHP_URL_PATH));
        else{
            $folder=config('cms.storage.'.$folder.'.folder');
            $url=urldecode(parse_url($url, PHP_URL_PATH));
            $url=explode($folder.'/', $url);
            unset($url[0]);
            $url='/'.$folder.'/'.implode($folder.'/', $url);
            $url=str_replace(['\/',' '], ['/','%20'], trim($url));
            return $url;
        }
    }

    // public function base_link($url, $folder='uploads'){
    //     if(config('cms.storage.'.$folder.'.disk') == 'local') return urldecode(parse_url($url, PHP_URL_PATH));
    //     else{
    //         $folder=config('cms.storage.'.$folder.'.folder');
    //         $url=urldecode(parse_url($url, PHP_URL_PATH));
    //         $url=explode($folder.'/', $url);
    //         unset($url[0]);
    //         $url='/'.$folder.'/'.implode($folder.'/', $url);
    //         $url=str_replace(['\/',' '], ['/','%20'], trim($url));
    //         return $url;
    //     }
    // }

    //
    // File handling
    //

    /**
     * Generates a disk name from the supplied file name.
     */
    protected function getDiskName()
    {
        if ($this->disk_name !== null) {
            return $this->disk_name;
        }

        $ext = strtolower($this->getExtension());

        // If file was uploaded without extension, attempt to guess it
        if (!$ext && $this->data instanceof UploadedFile) {
            $ext = $this->data->guessExtension();
        }

        $name = str_replace('.', '', uniqid(null, true));

        return $this->disk_name = !empty($ext) ? $name.'.'.$ext : $name;
    }

    /**
     * Returns a temporary local path to work from.
     */
    protected function getLocalTempPath($path = null)
    {
        if (!$path) {
            return $this->getTempPath() . '/' . md5($this->getDiskPath()) . '.' . $this->getExtension();
        }

        return $this->getTempPath() . '/' . $path;
    }


    protected function check_name($name, $ext, $pathinfo, $num=0){
        if($num) $return=$name.'-'.$num.'.'.$ext;
        else $return=$name.'.'.$ext;

        if(!$this->isLocalStorage()){

            $base=trim(str_replace('/'.config('cms.storage.media.folder').' ','',config('cms.storage.media.path').' '));
            $image_url=$base.'/'.$this->folder.'/'.$return;

            if($this->checkFile($image_url)){
                $num++;
                return $this->check_name($name, $ext, $pathinfo, $num);
            }

        // if($this->checkFile($pathinfo['dirname'].'/'.$return)){
        }elseif($this->checkFile($this->folder.'/'.$return)){
            $num++;
            return $this->check_name($name, $ext, $pathinfo, $num);
        }

        return $return;
    }


    /**
     * Saves a file
     * @param string $sourcePath An absolute local path to a file name to read from.
     * @param string $destinationFileName A storage file name to save to.
     */
    protected function putFile($sourcePath, $destinationFileName = null, $destinationPath=null)
    {

        if(!$destinationFileName) $destinationFileName=$this->file_name;
        if(!$destinationPath) $destinationPath=$this->folder;

        $infos=pathinfo($sourcePath);
        $ext=$infos['extension']; if(isset($this->resize_options['extension'])) $ext=$this->resize_options['extension'];
        $ext=str_replace('jpeg', 'jpg', $ext);
        $this->infos_results['extension']=$ext;

        if (!$destinationFileName) $destinationFileName=$infos['filename'];
        else{
            $destinationFileName=pathinfo($destinationFileName);
            $destinationFileName=$destinationFileName['filename'];
        }

        $destinationFileName=str_replace(['%20',' '], ['-','-'], $destinationFileName);

        // && strpos("[".$infos['dirname']."]", "storage/app/media/")
        if($destinationFileName.'.'.$ext != str_replace('jpeg', 'jpg', $infos['basename'])){
            $destinationFileName=$this->check_name($destinationFileName, $ext, $infos);
        }else $destinationFileName.='.'.$ext;

        // $destinationFileName=str_replace(['%20',' ','.jpeg'], ['-','-','.jpg'], $destinationFileName);

        $this->filesave='';
        if($destinationPath){
            $this->filesave=$destinationPath.'/';
            // $name_cod=$destinationPath.'/'.$name_cod;
        }
        $this->filesave.=$destinationFileName;

        if (!$this->isLocalStorage()) {

            // return $this->copyLocalToStorage($sourcePath, $this->filesave);

            $sourcePath=$this->resizeActive($sourcePath);
            $info1=pathinfo($sourcePath); $info2=pathinfo($this->filesave);
            
            // if($info2['basename'] != $info1['basename']) $this->filesave=str_replace($info2['basename'], $info1['basename'], $this->filesave);

            if($this->copyLocalToStorage($sourcePath, $this->filesave)){
                $this->success=true;
                return true;
            }else{
                return false;
            }

        }


        // $options=$this->resize_options;

        // FileHelper::copy($sourcePath, $this->filesave)

        /*
         * Verify the directory exists, if not try to create it. If creation fails
         * because the directory was created by a concurrent process then proceed,
         * otherwise trigger the error.
         */
        if (
            $destinationPath && 
            !FileHelper::isDirectory($destinationPath) &&
            !FileHelper::makeDirectory($destinationPath, 0777, true, true) &&
            !FileHelper::isDirectory($destinationPath)
        ) {
            trigger_error(error_get_last(), E_USER_WARNING);
        }


        if(str_replace(url('/').'/', '', $sourcePath) != $this->filesave){
            // MediaLibrary::instance()->moveFile( str_replace('%20', ' ', $sourcePath), $this->filesave );

            // $texto=$sourcePath.' - '.$this->filesave;
            // $arquivo = "meu_arquivo.txt";
            // $fp = fopen($arquivo, "w+");
            // fwrite($fp, $texto);
            // fclose($fp);

            $ori=str_replace(array('/http','%20',url('/').'/'), array('http',' ',''), $sourcePath);
            if(FileHelper::copy($sourcePath, $this->filesave)) FileHelper::delete($ori);
            else rename($ori, $this->filesave);
            // Storage::move($ori, $this->filesave);

            // if(strpos("[".$sourcePath."]", " ")){
            //     $sourcePath=str_replace('/http', 'http', $sourcePath);
            //     MediaLibrary::instance()->moveFile( $sourcePath, $this->filesave );
            // }else{
            // if(FileHelper::copy($sourcePath, $this->filesave)) FileHelper::delete(str_replace(url('/').'/', '', $sourcePath));
            //     elseif(@!rename($sourcePath, $this->filesave)) return false;
            // }
        }
        $this->filesave=$this->resizeActive($this->filesave);
        $this->success=true;
        return true;
    }


    // public function checkNameFile($path, $externo){
    //     if(checkFile($path, $externo)){

    //     }
    // }


    public function maxWidth($width=null){ $this->max_width=$width; }
    public function maxHeight($height=null){ $this->max_height=$height; }
    public function resizeOptions($width='auto', $height='auto', $options=[]){
        $this->resize_width=$width;
        $this->resize_height=$height;
        $this->resize_options=$options;
    }
    protected function resizeActive($file){
        $options=$this->resize_options;
        $width=$this->resize_width;
        $height=$this->resize_height;

        $size = getimagesize($file);
        if($this->max_width && $size[0] > $this->max_width && $size[0] > $size[1]) $width=$this->max_width;
        if($this->max_height && $size[1] > $this->max_height) $height=$this->max_height;
        
        if(isset($size[0]) && $width>$size[0]) $width=$size[0];
        if(isset($size[1]) && $height>$size[1]) $height=$size[1];

        return $this->resize($file, $width, $height, $options);
    }

    public function resize($file=false, $width='auto', $height='auto', $options=[]){
        // $options['quality']=80; // $options['compress']=true; // $options['extension']='jpg';
        if(!isset($options['quality'])) $options['quality']=90;

        if($file && ($width != 'auto' || $height != 'auto' || count($options) > 0)){

            $info=pathinfo($file);
            if(isset($options['extension'])){
                $new=$info['dirname'].'/'.$info['filename'].'.'.$options['extension'];
            }else $new=$file;
            $new=str_replace(['%20',' ','.jpeg'], ['-','-','.jpg'], $new);

            if($height == 'auto') $height=false;
            if($width == 'auto') $width=false;
            $image=new Image($file);

            if($width || $height) $image->resize($width,$height,'transparent');

            $ext=$info['extension'];
            if(isset($options['extension'])) $ext=$options['extension'];

            $rotate=$this->correctOrientation($file);
            if($rotate) $image->rotate($rotate);
            $image->save($new,$ext,$options['quality']);

            // Resizer::open($file)
            // ->resize($width, $height, $options)
            // ->save($new);

            if($file != $new) FileHelper::delete($file);
            $this->setInfosResults($new, $image);
            return $new;
        }else $this->setInfosResults($file);
    }

    public function correctOrientation($imagem=false){
        // https://www.php.net/manual/pt_BR/function.exif-read-data.php
        $veri=@exif_read_data($imagem, 0, true);
        $orientation=false;
        if(isset($veri['IFD0']) && isset($veri['IFD0']['Orientation'])){
            $orientation=$veri['IFD0']['Orientation'];
        }elseif(isset($veri['THUMBNAIL']) && isset($veri['THUMBNAIL']['Orientation'])){
            $orientation=$veri['THUMBNAIL']['Orientation'];
        }
        switch ($orientation) {
            case 8:
            return 90;
            break;
            case 3:
            return 180;
            break;
            case 6:
            return -90;
            break;
            default:
            return 0;
        }
    }

    protected function setInfosResults($new=false, $image=null){
        if(!$new) return;
        if(!$image) $image=$new;
        $add=[
            'filesize' => filesize($image),
            'mime_type' => mime_content_type($new),
        ];
        $this->infos_results=array_merge($this->infos_results, $add);
    }

    /**
     * Delete file contents from storage device.
     * @return void
     */
    protected function deleteFile($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $filePath = $directory . $fileName;

        if ($this->storageCmd('exists', $filePath)) {
            $this->storageCmd('delete', $filePath);
        }

        Cache::forget($this->getCacheKey($filePath));
        $this->deleteEmptyDirectory($directory);
    }

    /**
     * Check file exists on storage device.
     * @return void
     */
    protected function hasFile($fileName = null)
    {
        $filePath = $this->getDiskPath($fileName);

        $result = Cache::rememberForever($this->getCacheKey($filePath), function () use ($filePath) {
            return $this->storageCmd('exists', $filePath);
        });

        // Forget negative results
        if (!$result) {
            Cache::forget($this->getCacheKey($filePath));
        }

        return $result;
    }

    /**
     * Checks if directory is empty then deletes it,
     * three levels up to match the partition directory.
     * @return void
     */
    protected function deleteEmptyDirectory($dir = null)
    {
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);
    }

    /**
     * Returns true if a directory contains no files.
     * @return void
     */
    protected function isDirectoryEmpty($dir)
    {
        if (!$dir) {
            return null;
        }

        return count($this->storageCmd('allFiles', $dir)) === 0;
    }

    //
    // Storage interface
    //

    /**
     * Calls a method against File or Storage depending on local storage.
     * This allows local storage outside the storage/app folder and is
     * also good for performance. For local storage, *every* argument
     * is prefixed with the local root path. Props to Laravel for
     * the unified interface.
     * @return mixed
     */
    protected function storageCmd()
    {
        $args = func_get_args();
        $command = array_shift($args);
        $result = null;

        if ($this->isLocalStorage()) {
            $interface = 'File';
            $path = $this->getLocalRootPath();
            $args = array_map(function ($value) use ($path) {
                return $path . '/' . $value;
            }, $args);

            $result = forward_static_call_array([$interface, $command], $args);
        }
        else {
            $result = call_user_func_array([$this->getDisk(), $command], $args);
        }

        return $result;
    }

    /**
     * Copy the Storage to local file
     */
    protected function copyStorageToLocal($storagePath, $localPath)
    {
        return FileHelper::put($localPath, $this->getDisk()->get($storagePath));
    }

    /**
     * Copy the local file to Storage
     */
    protected function copyLocalToStorage($localPath, $storagePath)
    {
        return $this->getDisk()->put($storagePath, FileHelper::get($localPath), $this->isPublic() ? 'public' : null);
    }

    //
    // Configuration
    //

    /**
     * Returns the maximum size of an uploaded file as configured in php.ini
     * @return int The maximum size of an uploaded file in kilobytes
     */
    public static function getMaxFilesize()
    {
        return round(UploadedFile::getMaxFilesize() / 1024);
    }

    /**
     * Define the internal storage path, override this method to define.
     */
    public function getStorageDirectory()
    {
        if ($this->isPublic()) {
            return 'uploads/public/';
        }

        return 'uploads/protected/';
    }

    /**
     * Define the public address for the storage path.
     */
    public function getPublicPath()
    {
        if ($this->isPublic()) {
            return 'http://localhost/uploads/public/';
        }

        return 'http://localhost/uploads/protected/';
    }

    /**
     * Define the internal working path, override this method to define.
     */
    public function getTempPath()
    {
        $path = temp_path() . '/uploads';

        if (!FileHelper::isDirectory($path)) {
            FileHelper::makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    /**
     * Returns the storage disk the file is stored on
     * @return FilesystemAdapter
     */
    public function getDisk()
    {
        return Storage::disk();
    }

    /**
     * Returns true if the storage engine is local.
     * @return bool
     */
    protected function isLocalStorage()
    {
        return Storage::getDefaultDriver() == 'local';
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
        return implode('/', array_slice(str_split($this->disk_name, 3), 0, 3)) . '/';
    }

    /**
     * If working with local storage, determine the absolute local path.
     * @return string
     */
    protected function getLocalRootPath()
    {
        return storage_path().'/app';
    }
}
