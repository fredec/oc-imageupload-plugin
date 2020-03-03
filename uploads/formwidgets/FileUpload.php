<?php 
// namespace Cydrick\KidlatArchive\FormWidgets;
namespace Diveramkt\Uploads\FormWidgets;
// https://octobercms.com/forum/post/how-to-extend-systemmodelsfile

use Backend\FormWidgets\FileUpload as FileUploadBase; use Str; use Input; use Validator;

// use System\Models\File; use System\Classes\SystemException; 
// use Backend\Classes\FormField; 
// use Backend\Classes\FormWidgetBase; use October\Rain\Support\ValidationException; use Exception;

class FileUpload extends FileUploadBase {

    public $teste2='';
    public $teste_var = true;

    // public function teste(){
    //     return 'teste';
    // }

    // public function onUpload()
    // {

    //     // return;
    //     // $texto='teste subinterno';
    //     // $arquivo = "meu_arquivo.txt";
    //     // $fp = fopen($arquivo, "w+");
    //     // fwrite($fp, $texto);
    //     // fclose($fp);

    //     try {

    //         if (!Input::hasFile('file_data')) {
    //             throw new ApplicationException('File missing from request');
    //         }

    //         $fileModel = $this->getRelationModel();
    //         $uploadedFile = Input::file('file_data');

    //         $validationRules = ['max:'.$fileModel::getMaxFilesize()];
    //         if ($fileTypes = $this->getAcceptedFileTypes()) {
    //             $validationRules[] = 'extensions:'.$fileTypes;
    //         }

    //         if ($this->mimeTypes) {
    //             $validationRules[] = 'mimes:'.$this->mimeTypes;
    //         }

    //         $validation = Validator::make(
    //             ['file_data' => $uploadedFile],
    //             ['file_data' => $validationRules]
    //         );

    //         if ($validation->fails()) {
    //             throw new ValidationException($validation);
    //         }

    //         if (!$uploadedFile->isValid()) {
    //             throw new ApplicationException('File is not valid');
    //         }

    //         $fileRelation = $this->getRelationObject();

    //         $file = $fileModel;
    //         $file->data = $uploadedFile;
    //         $file->is_public = $fileRelation->isPublic();
    //         $file->save();

    //         /**
    //          * Attach directly to the parent model if it exists and attachOnUpload has been set to true
    //          * else attach via deferred binding
    //          */
    //         $parent = $fileRelation->getParent();
    //         if ($this->attachOnUpload && $parent && $parent->exists) {
    //             $fileRelation->add($file);
    //         }
    //         else {
    //             $fileRelation->add($file, $this->sessionKey);
    //         }

    //         $file = $this->decorateFileAttributes($file);

    //         // $vals=array() $file;
    //         // $texto='testando upload';
    //         // $arquivo = "nome_arquivo.txt";
    //         // $fp = fopen($arquivo, "w+");
    //         // fwrite($fp, $file->pathUrl);
    //         // fclose($fp);

    //         $result = [
    //             'id' => $file->id,
    //             'thumb' => $file->thumbUrl,
    //             'path' => $file->pathUrl
    //         ];


    //         $response = Response::make($result, 200);
    //     }
    //     catch (Exception $ex) {
    //         $response = Response::make($ex->getMessage(), 400);
    //     }

    //     return $response;
    // }







// public function getRelatedModel(){
//     list($model,$attribute) = $this->resolveModelAttribute($this->valueFrom);
//     return $model->{$this->getRelationType()}[$this->fieldName][0];
// }
// public function onRemoveAttachment()
// {
//     $classname = $this->getRelatedModel();
//     if (($file_id = post('file_id')) && ($file = $classname::find($file_id))) {
//         $this->getRelationObject()->remove($file, $this->sessionKey);
//     }
// }

// public function onSortAttachments()
// {
//     if ($sortData = post('sortOrder')) {
//         $ids = array_keys($sortData);
//         $orders = array_values($sortData);

//         $classname = $this->getRelatedModel();
//         $file = new $classname;

//         $file->setSortableOrder($ids, $orders);
//     }
// }

// public function onLoadAttachmentConfig()
// {
//     $classname = $this->getRelatedModel();
//     if (($file_id = post('file_id')) && ($file = $classname::find($file_id))) {
//         $this->vars['file'] = $file;
//         return $this->makePartial('config_form');
//     }

//     throw new SystemException('Unable to find file, it may no longer exist');
// }

// public function onSaveAttachmentConfig()
// {
//     $classname = $this->getRelatedModel();
//     try {
//         if (($file_id = post('file_id')) && ($file = $classname::find($file_id))) {
//             $file->title = post('title');
//             $file->description = post('description');
//             $file->save();

//             $file->thumb = $file->getThumb($this->imageWidth, $this->imageHeight, ['mode' => 'crop']);
//             return ['item' => $file->toArray()];
//         }

//         throw new SystemException('Unable to find file, it may no longer exist');
//     }
//     catch (Exception $ex) {
//         return json_encode(['error' => $ex->getMessage()]);
//     }
// }
// protected function checkUploadPostback()
// {
//     $classname = $this->getRelatedModel();
//     if (!($uniqueId = post('X_OCTOBER_FILEUPLOAD')) || $uniqueId != $this->getId()) {
//         return;
//     }

//     try {
//         $uploadedFile = Input::file('file_data');

//         $isImage = starts_with($this->getDisplayMode(), 'image');
//         //echo $classname;
//         $validationRules = ['max:'.$classname::getMaxFilesize()];
//         if ($isImage) {
//             $validationRules[] = 'mimes:jpg,jpeg,bmp,png,gif,svg';
//         }

//         $validation = Validator::make(
//             ['file_data' => $uploadedFile],
//             ['file_data' => $validationRules]
//         );

//         if ($validation->fails()) {
//             throw new ValidationException($validation);
//         }

//         if (!$uploadedFile->isValid()) {
//             throw new SystemException('File is not valid');
//         }

//         $fileRelation = $this->getRelationObject();

//         $file = new $classname;
//         $file->data = $uploadedFile;
//         $file->is_public = $fileRelation->isPublic();
//         $file->save();

//         $fileRelation->add($file, $this->sessionKey);

//         $file->thumb = $file->getThumb($this->imageWidth, $this->imageHeight, ['mode' => 'crop']);
//         $result = $file;

//     }
//     catch (Exception $ex) {
//         $result = json_encode(['error' => $ex->getMessage()]);
//     }

//     header('Content-Type: application/json');
//     die($result);
// }
}