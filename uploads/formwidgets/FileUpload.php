<?php 
// namespace Cydrick\KidlatArchive\FormWidgets;
namespace Diveramkt\Uploads\FormWidgets;
// https://octobercms.com/forum/post/how-to-extend-systemmodelsfile

// use Backend\FormWidgets\FileUpload as FileUploadBase;
 // use Str; use Input; use Validator;

// use System\Models\File; use System\Classes\SystemException; 
// use Backend\Classes\FormField; 
// use Backend\Classes\FormWidgetBase; use October\Rain\Support\ValidationException; use Exception;


use Backend\FormWidgets\FileUpload as FileUploadBase;

use Str; use Input; use Validator; use System\Models\File; use System\Classes\SystemException; use Backend\Classes\FormField; use Backend\Classes\FormWidgetBase; use October\Rain\Support\ValidationException; use Exception;

class FileUpload extends FileUploadBase {

    // public static $teste_='hehe';

   // public static function teste() {
   //      // echo $this->useCaption;
   //      // echo self::$veri_;
   // }


    // public $teste='teste extend';
    public function onUpload(){
        return;
    }

}