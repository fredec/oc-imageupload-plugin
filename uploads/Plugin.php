<?php
namespace Diveramkt\Uploads;

use System\Classes\PluginBase;
use Event;
use Db;
// use Response;
// use imagejpg;
use Stdclass;
use Input;

use Diveramkt\Uploads\Models\Settings;
use Diveramkt\Uploads\Models\Informacoes;

use Diveramkt\Uploads\Classes\Libtiny\Tinify\Exception;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\ResultMeta;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Result;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Source;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Cliente;
use Diveramkt\Uploads\Classes\Libtiny\Tinify;

// https://github.com/Gregwar/Image
use Diveramkt\Uploads\Classes\Gregwar\Image\Image;
// use Gregwar\Image\Image;

// use Diveramkt\Uploads\Classes\Wideimage\WideImage;

// https://phpimageworkshop.com/
// https://phpimageworkshop.com/quickstart.html
// use Diveramkt\Uploads\Classes\PHPImageWorkshop\ImageWorkshop;

use System\Classes\MediaLibrary;
// use Diveramkt\Uploads\Formwidgets\FileUpload;

use Backend\FormWidgets\FileUpload;
// use Diveramkt\Uploads\FormWidgets\FileUpload as FileUploadExtend;
// use Diveramkt\Uploads\FormWidgets\FileUpload;

use Backend\Facades\BackendAuth;

use Diveramkt\Uploads\Classes\OtimizarImage;

class Plugin extends PluginBase
{
	public function registerComponents()
	{
		// return [
		// 	'Diveramkt\Uploads\Components\Teste' => 'Teste'
		// ];
	}

	public function registerSettings()
	{
		return [
			'settings' => [
				'label'       => 'Uploads',
				'description' => 'Configurações para otimizar o upload das imagens.',
				'category'    => 'DiveraMkt',
				'icon'        => 'icon-upload',
				'class'       => 'DiveraMkt\Uploads\Models\Settings',
				'order'       => 500,
				'keywords'    => 'uploads imagens diveramkt',
				'permissions' => ['Uploads.manage_upload'],
			]
		];
	}


	// "Method Diveramkt\Uploads\Classes\Gregwar\Image\Image::__toString() must not throw an exception, caught Error:
	// Class 'Diveramkt\Uploads\Classes\Gregwar\Cache\Cache' not found" on line 118 of /var/www/jadapaxcuidar.october/public_html/plugins/diveramkt/uploads/classes/OtimizarImage.php



	// public function registerFormWidgets() {
	// 	// return [ 'Creator\Client\FormWidgets\FileUploader' => [ 'label' => 'FileUploader', 'code' => 'FileUploader' ], ];
	// 	return [ 'Diveramkt\Uploads\FormWidgets\FileUpload' => [ 'label' => "FileUpload", "alias" => "ckafileupload" ], ];
	// }

	public function boot(){
		include 'plugins/diveramkt/uploads/classes/Gregwar/Image/Image.php';


		// //////////////GERENCIAMENTO NAS IMAGENS E ARQUIVOS NO MEDIA
		\Backend\Widgets\MediaManager::extend(function ($widget) {
		// \Diveramkt\Uploads\Backend\Widgets\MediaManagerExtend::extend(function ($widget) {

			// $texto='testes '.time();
			// $arquivo = "meu_arquivo.txt";
			// $fp = fopen($arquivo, "w+");
			// fwrite($fp, $texto);
			// fclose($fp);

			// const FOLDER_ROOT = '/obituarios/2020/';
			// $widget->vars['isRootFolder']='/obituarios/2020/';
			
			$user = BackendAuth::getUser();


			$permissoes=(array) $user->permissions;
			if($user->hasAccess('delete_media') or $user->role_id == 2 or (isset($permissoes['delete_media']) && $permissoes['delete_media'] == 1) ) $widget->addDynamicProperty('deletar', true);
			else $widget->addDynamicProperty('deletar', false);

			if(isset($permissoes['readOnly_media']) && $permissoes['readOnly_media'] == 1) $widget->readOnly=true;
			$widget->addViewPath(plugins_path().'/diveramkt/uploads/backend/widgets/mediamanager/partials/');

		});
		// //////////////GERENCIAMENTO NAS IMAGENS E ARQUIVOS NO MEDIA

		// $model->bindEvent('model.beforeCreate', function () use (\System\Models\File $model) {
		// 	if (!$model->isValid()) {
		// 		throw new \Exception("Invalid Model!");
		// 	}
		// });

		\System\Models\File::extend(function($model) {


			// return;
			// return;
			$model->bindEvent('model.beforeCreate', function() use ($model) {

				// return;
			// $model->bindEvent('model.afterUpdate', function($veri=false) use ($model) {
			// $model->bindEvent('model.beforeUpdate', function($veri) use ($model) {

			// $model->bindEvent('model.afterUpdate', function($veri) use ($model) {
				// if (!$model->isValid()) {
				// 	throw new \Exception("Invalid Model!");
				// }

				// // $texto=$arquivo;
				// return;
				if($model->extension == 'ico' || $model->extension == 'json') return;
				// if(!exif_imagetype($model->path)) return;
				if(!strpos("[".$model->path."]", ".")  || !exif_imagetype($model->path)) return;
				if($model->file_size <= '10000') $config['compression']=100;

				$config['name_arq']=true;
				$config['rename']=false;
				$image=new OtimizarImage($config);

				$retorno=$image->otimizar($model->path,'system_files');

				if($retorno){
					$exp=explode('.', $model->file_name); $exp=end($exp);

					$model->disk_name=str_replace('.'.$exp,'.'.$retorno['ext'], $model->disk_name);
					$model->file_name=str_replace('.'.$exp,'.'.$retorno['ext'], $model->file_name);

					$model->content_type=$retorno['mime_type'];
					// $model->file_size=$retorno['filesize'];
				}
			});

			$model->bindEvent('model.afterCreate', function() use ($model) {
				// return;
				if($model->extension == 'ico' || $model->extension == 'json') return;
				// if(!exif_imagetype($model->path)) return;
				if(!strpos("[".$model->path."]", ".") || !exif_imagetype($model->path)) return;

				// return;
				// if (!$model->isValid()) {
				// 	throw new \Exception("Invalid Model!");
				// }

				$base = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['SCRIPT_NAME']) . '/');
				$base=str_replace('\/','/',$base);
				if(file_exists(str_replace($base,'',$model->path))){
					// $texto=filesize(str_replace($base,'',$model->path));

				// $veri = Db::table('system_files')->where('file_name',$model->file_name)->where('disk_name',$model->disk_name)->first();

					Db::table('system_files')
					->where('id', $model->id)
					->update([
						'file_size' => filesize(str_replace($base,'',$model->path)),
					]);
				}

			});

		});

		Event::listen( 'media.file.upload', function ( $widget, $filePath, $uploadedFile ) {
			$ext=explode('.', $filePath); $ext=end($ext);
			if($ext == 'ico' || $ext == 'json') return;
			$base='storage/app/media';
			$arquivo=str_replace('//','/',$base.$filePath);
			if(!exif_imagetype($arquivo)) return;

			$config=array();
			if(strpos("[".$filePath."]", "uploaded-files/")){
				$config['converter_jpg']=false;
				$config['rename']=false;
			}

			$image=new OtimizarImage($config);
			$retorno=$image->otimizar($arquivo,'midias');

		} );

	}

}
