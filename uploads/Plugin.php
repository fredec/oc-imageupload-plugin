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

use October\Rain\Database\Attach\Resizer;
// use October\Rain\Database\Attach\File;

// Resizer::open(Input::file('field_name'))->resize(800, 600, 'crop')->save('path/to/file.jpg', 100);
use Storage;

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

	public function veri_extension_image($ext){
		$ext=mb_strtolower($ext, 'UTF-8');
		if($ext == 'jpeg' or $ext == 'jpg' or $ext == 'png') return true;
		else false;
	}

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

				// $texto=$model->extension;
				// $arquivo = "meu_arquivo.txt";
				// $fp = fopen($arquivo, "w+");
				// fwrite($fp, $texto);
				// fclose($fp);

				// return;

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
				if(
					!strpos("[".$model->path."]", ".") || 
					// !exif_imagetype($model->path)
					!$this->veri_extension_image($model->extension)
				) return;
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
				// return;
				if($model->extension == 'ico' || $model->extension == 'json') return;
				// if(!exif_imagetype($model->path)) return;
				if(!strpos("[".$model->path."]", ".") || 
					// !exif_imagetype($model->path)
					!$this->veri_extension_image($model->extension)
				) return;

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
			// if(!exif_imagetype($arquivo)) return;

			$config=array();
			if(strpos("[".$filePath."]", "uploaded-files/")){
				$config['converter_jpg']=false;
				$config['rename']=false;
			}

			$image=new OtimizarImage($config);
			$retorno=$image->otimizar($arquivo,'midias');

		} );

	}

	private function getPhpFunctions()
	{
		return [
			// file_name
			'copyname' => function($path=false, $nome=false, $pasta=false){
				if(!strpos("[".$path."]", "/storage/") or !$nome) return $path;
				$exp=explode('/', $path);
				$arquivo=end($exp);

				$exp=explode('.', $arquivo);
				$name=$exp[0];
				$ext=end($exp);

				if(isset($nome['file_name'])){
					if($nome['title']) $nome=$this->create_slug($nome['title']).'.'.$nome['extension'];
					else $nome=$nome['file_name'];
				}
				
				$new_name=explode('/', $nome);
				$new_name=end($new_name);

				$veri=explode('.', $new_name);
				$veri[0]=$this->create_slug($veri[0]);
				$new_name=implode('.', $veri);

				if($pasta) $path_new=str_replace($arquivo, $pasta.'/'.$new_name, $path);
				else $path_new=str_replace($arquivo, $new_name, $path);

				$path_new=explode('/storage/', $path_new);
				$http=$path_new[0];
				$path_new=end($path_new);
				$path_new='storage/'.$path_new;

				$path_interno=str_replace($new_name, '', $path_new);
				if(!file_exists($path_interno)) mkdir($path_interno, 0777);

				if(!file_exists($path_new)) copy($path, $path_new);
				return $http.'/'.$path_new;
			},
		];
	}

	public function create_slug($string) {
		$table = array(
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
			'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', '/' => '-', ' ' => '-'
		);
		$stripped = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $string);
		return strtolower(strtr($string, $table));
	}

	public function registerMarkupTags()
	{
		$filters = [];
        // add PHP functions
		$filters += $this->getPhpFunctions();

		return [
			'filters'   => $filters,
		];
	}

}
