<?php
namespace Diveramkt\Uploads;

use System\Classes\PluginBase;
use Event;
use Db;
use Stdclass;
use Input;
use Auth;
use Backend\Models\User;

use Diveramkt\Uploads\Models\Settings;
use Diveramkt\Uploads\Models\Informacoes;

use Diveramkt\Uploads\Classes\Libtiny\Tinify\Exception;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\ResultMeta;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Result;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Source;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Cliente;
use Diveramkt\Uploads\Classes\Libtiny\Tinify;

use System\Classes\MediaLibrary;

use Backend\Facades\BackendAuth;
use Diveramkt\Uploads\Classes\OtimizarImage;
use October\Rain\Database\Attach\File;
use File as FileHelper;
use Diveramkt\Uploads\Classes\Extra\Fileuploads;
use Diveramkt\Uploads\Classes\Functions3;

use Storage;
use Artisan;
use Request;
use System\Classes\PluginManager;
use Http;
use str;
use Config;

// use Arcane\Seo\Models\Settings SettingsArcane;

class Plugin extends PluginBase
{
	public function registerComponents()
	{
		return [
			'Diveramkt\Uploads\Components\Fileupload' => 'Fileupload'
		];
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
				'permissions' => ['manage_upload'],
			]
		];
	}


	public function veri_extension_image($ext){
		$ext=mb_strtolower($ext, 'UTF-8');
		if($ext == 'jpeg' or $ext == 'jpg' or $ext == 'png' or $ext == 'webp') return true;
		else false;
	}

	public $configs;

	// public function base_link($url, $folder='media'){
	// 	if(config('cms.storage.'.$folder.'.disk') == 'local') return urldecode(parse_url($url, PHP_URL_PATH));
	// 	else{
	// 		$folder=config('cms.storage.'.$folder.'.folder');
	// 		$url=urldecode(parse_url($url, PHP_URL_PATH));
	// 		$url=explode($folder.'/', $url);
	// 		unset($url[0]);
	// 		$url='/'.$folder.'/'.implode($folder.'/', $url);
	// 		return $url;
	// 	}
	// }

	public $settings=false;
	public function getSettings(){
		if(!$this->settings) $this->settings=Functions3::getSettings();
		return $this->settings;
	}

	function removerAcentos($string){
		return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
	}

	public function boot(){
		$settings=$this->getSettings();
		// $extension_files=explode(',', $settings->allowed_files);
		if($settings->allowed_files and $settings->allowed_images){
			$filesAllow=[
				'defaultExtensions' => explode(',', $settings->allowed_files),
				'imageExtensions' => explode(',', $settings->allowed_images),
			];
		// $extension_image=['svg','jpg', 'jpeg', 'bmp', 'png', 'webp', 'gif'];
			Config::set('cms.fileDefinitions', $filesAllow);
		}


		// $image=new \Gregwar\Image\Image($src);
		// $image->save($des,'jpg',80);

		// $image = new \Gumlet\ImageResize($des);
		// // $image->scale(50);
		// $image->quality_jpg=80;
		// $image->save($des);
		// $image = new \ImageResize($src);

		$class=get_declared_classes();

		Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
			$class=get_declared_classes();
        // $controller->addJs('/path/to/your/javascript/file.js');
			$user = BackendAuth::getUser();
			if(isset($user->id) && $user->id && (in_array('Arcane\Seo\Models\Settings', $class) || in_array('Arcane\Seo\Plugin', $class))){
				$infosArcane = \Arcane\Seo\Models\Settings::instance();
				if($infosArcane->minify_html) \Storage::deleteDirectory('arcane/seo/minify');
			}
			if(isset($user->id) && in_array('SerenityNow\Cacheroute\Plugin', $class)) Artisan::call('cache:clear');
		// if(in_array('Arcane\Seo\Models\Settings', $class) || in_array('Arcane\Seo\Plugin', $class)){}
		});

		if(in_array('RainLab\Translate\Plugin', $class) && in_array('SerenityNow\Cacheroute\Plugin', $class)){
			Event::listen('translate.localePicker.translateQuery', function($page, $params, $oldLocale, $newLocale) {
				// if(in_array('SerenityNow\Cacheroute\Plugin', $class)) 
				Artisan::call('cache:clear');
			});
		}

		// //////////////GERENCIAMENTO NAS IMAGENS E ARQUIVOS NO MEDIA
		\Backend\Widgets\MediaManager::extend(function ($widget) {
		// \Diveramkt\Uploads\Backend\Widgets\MediaManagerExtend::extend(function ($widget) {

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

		// //////////////////// OTIMIZANDO IMAGENS NO FILE SYSTEM
		\System\Models\File::extend(function($model) {
			$model->bindEvent('model.afterCreate', function() use ($model) {
		// $model->bindEvent('model.afterUpdate', function() use ($model) {
				$settings=Functions3::getSettings();
				if((isset($settings['disabled']) and $settings['disabled']) || !strpos("[".$model->path."]", ".") || !$this->veri_extension_image($model->extension)) return;

				if(!isset($model->id)) return;
				$veri=Db::table('system_files')
				->where('id', $model->id)
				->first();

				if($veri->attachment_type == 'Cms\Models\ThemeData') return;

				if(strpos("[".$veri->attachment_type."]", "Diveramkt") && strpos("[".$veri->attachment_type."]", "Uploads") && strpos("[".$veri->attachment_type."]", "Settings")){
					$settings_upload_atualizacao = \Diveramkt\Uploads\Models\Settings::instance();
					$settings_upload_atualizacao->atualizacao_marca=date('YmdHis');
					$settings_upload_atualizacao->save();
				}

				if($model->file_size <= '10000') $config['compression']=100;

				$config['name_arq']=true;
				$config['rename']=false;
				$image=new OtimizarImage($config);

				$link=explode(config('cms.storage.uploads.path'), $model->path);
				$link='/uploads'.end($link);
			// if(config('cms.storage.uploads.disk') == 'local') $link=storage_path('app'.$link);
				if(config('cms.storage.uploads.disk') == 'local') $link='storage/app'.$link;

				$retorno=$image->otimizar($model->path,$link,'uploads');
				// $retorno=$retorno['infos_results'];

				if($retorno){
					$infodb=pathinfo($model->file_name);
					$ext=$infodb['extension'];

					$model->disk_name=str_replace('.'.$ext,'.'.$retorno['extension'], $model->disk_name);
					$model->file_name=str_replace('.'.$ext,'.'.$retorno['extension'], $model->file_name);

					if(isset($retorno['mime_type'])) $model->content_type=$retorno['mime_type'];

					$filesize=false;
					if(isset($retorno['filesize']) && $retorno['filesize']) $filesize=$retorno['filesize'];

					$up=[
						'disk_name' => $model->disk_name,
						'file_name' => $model->file_name,
						'content_type' => $model->content_type
					];
					if($filesize) $up['file_size']=$filesize;

					if($up){
						Db::table('system_files')
						->where('id', $model->id)
						->update($up);
					}
				}

			});

		});
		// //////////////////// OTIMIZANDO IMAGENS NO FILE SYSTEM

		// //////////////////// OTIMIZANDO IMAGENS NO MEDIA
		Event::listen( 'media.file.upload', function ( $widget, $filePath, $uploadedFile ) {
			// //////////////UPLOAD DE IMAGENS NO CAMPO DE TEXTO
			if(strpos("[".$filePath."]", "uploaded-files/")){
				// if($info['basename'] != str::slug($info['basename'])) return $filePath;
			}else{

			// //////////////UPLOAD DE IMAGENS NO CAMPO DE TEXTO
				if($this->removerAcentos(mb_strtolower(str_replace('_','-',$filePath), 'UTF-8')) != $filePath){
				// $string='/media';
				// if(strpos("[".$string."]", "$filePath")) rename('storage/app'.$filePath, 'storage/app'.$this->removerAcentos(mb_strtolower(str_replace('_','-',$filePath), 'UTF-8')));
				// else rename('storage/app/media/'.$filePath, 'storage/app/media/'.$this->removerAcentos(mb_strtolower(str_replace('_','-',$filePath), 'UTF-8')));
					rename('storage/app'.$filePath, 'storage/app'.$this->removerAcentos(mb_strtolower(str_replace('_','-',$filePath), 'UTF-8')));
					$filePath=$this->removerAcentos(mb_strtolower(str_replace('_','-',$filePath), 'UTF-8'));
				}
			}

			$info=pathinfo($filePath);

			$ext=$info['extension'];
			$settings=Functions3::getSettings();
			if((isset($settings['disabled']) and $settings['disabled']) || !$this->veri_extension_image($ext)) return;

			$medialib=MediaLibrary::instance();
			if(Str::slug($info['filename']) != $info['filename']){
				$stop=1;
				$check='';
				for ($i=0; $i < $stop; $i++) {
					if($i) $newPath=$info['dirname'].'/'.Str::slug($info['filename']).'-'.$i.'.'.$info['extension'];
					else $newPath=$info['dirname'].'/'.Str::slug($info['filename']).'.'.$info['extension'];
					$check=str_replace(['.jpeg',' /',' '], ['.jpg','',''], ' '.$medialib->url($newPath));
					if(file_exists($check)) $stop++;
				}

				$medialib->moveFile( $filePath, $newPath );
				$filePath=$newPath;
			}

			// $url=$filePath=$medialib->url($filePath);
			if(!strpos("[".$filePath."]", "/storage/app")) $url=$filePath='/storage/app'.$filePath;
			// else $url=$filePath='/storage/app'.$filePath;
			
			if(!strpos("[".$url."]", url('/'))) $url=url($url);
			$realPath = empty(trim($uploadedFile->getRealPath()))
			? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
			: $uploadedFile->getRealPath();


			$image=new OtimizarImage();
			$retorno=$image->otimizar($url, $filePath,'media',$realPath);

			// $ext=$info['extension'];
			// $settings=$this->getSettings();
			// if((isset($settings['disabled']) and $settings['disabled']) || !$this->veri_extension_image($ext)) return;
			// $filePath=implode('/',array_filter(explode('/', $filePath)));

			// // //////////////REMOVER ESPAÇOS E ACENTOS DO NOME DA IMAGEM
			// $original_name  = $uploadedFile->getClientOriginalName();
			// $ext     = pathinfo( $original_name, PATHINFO_EXTENSION );
			// $original_name_no_ext = pathinfo( $original_name, PATHINFO_FILENAME );
			// if($ext == 'jpeg') $ext='jpg';
			// $new_name = str_slug( $original_name_no_ext, '-' ) . '.' . $ext;
			// // $new_name_noext=str_slug( $original_name_no_ext, '-' );
			// $medialib=MediaLibrary::instance();

			// if ( $new_name != $original_name ) {
			// 	$stop=1;
			// 	for ($i=0; $i < $stop; $i++) { 
			// 		if($i) $new_name = str_slug( $original_name_no_ext, '-' ).'-'.$i.'.' . $ext;					
			// 		$newPath = str_replace( $original_name, $new_name, $filePath );
			// 		if($medialib->exists($newPath)) $stop++;
			// 	}
			// 	$medialib->moveFile( $filePath, $newPath );
			// 	$filePath=$newPath;
			// }
			// // //////////////REMOVER ESPAÇOS E ACENTOS DO NOME DA IMAGEM

			// // $realPath = empty(trim($uploadedFile->getRealPath()))
			// // ? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
			// // : $uploadedFile->getRealPath();

			// $url=$filePath=$medialib->url($filePath);
			// // $filePath='media/'.$filePath;
			// // if(config('cms.storage.media.disk') == 'local') $filePath='storage/app/'.$filePath;

			// if(!strpos("[".$url."]", url('/'))) $url=url($url);
			// $realPath = empty(trim($uploadedFile->getRealPath()))
			// ? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
			// : $uploadedFile->getRealPath();

			// $image=new OtimizarImage();
			// $retorno=$image->otimizar($url, $filePath,'media',$realPath);
		});
		// //////////////////// OTIMIZANDO IMAGENS NO MEDIA

}

// public $s3=null;
public function isS3(){
	if(config('cms.storage.uploads.disk') != 'local') return true;
	else return false;
}

public function checkFile($path=false){
	if(!$path) return;
	if(config('cms.storage.uploads.disk') == 's3'){
		$response = Http::get($path);
		if($response->code == 200) return true;
		else return false;
	}elseif (is_file($path)) return true;

	return false;
}

private function getPhpFunctions()
{
	return [

		'copyname' => function($path=false, $nome=false){
			return $path;
		},
			// 'watermark' => function($path=false, $pasta_interna=false){},
		'marcaDagua' => function($path=false, $pasta_interna=false){
				//utilizar: resize|marcaDagua
				// $settings=Settings::instance();
			$settings=Functions3::getSettings();
			if(!$settings->enabled_marca || !$settings->imagem_marca->path) return $path;

			$infos=pathinfo($path);
			$destinationPath=str_replace([url('/').'/',url('/')], ['',''], $infos['dirname'].'/'.Str::slug($settings->imagem_marca->file_name));

			// || !filesize($destinationPath.'/'.$infos['basename'])
			if(!@filemtime($destinationPath.'/'.$infos['basename'])){
				if (
					$destinationPath && 
					!FileHelper::isDirectory($destinationPath) &&
					!FileHelper::makeDirectory($destinationPath, 0777, true, true) &&
					!FileHelper::isDirectory($destinationPath)
				) {
					return $path;
						// trigger_error(error_get_last(), E_USER_WARNING);
				}
				$stop=1;
				for ($i=0; $i < $stop; $i++) { 

					$copy=str_replace(url('/').'/', '', $path);
					if(FileHelper::copy($copy, $destinationPath.'/'.$infos['basename'])){
						$path_new=$destinationPath.'/'.$infos['basename'];
						$image=new OtimizarImage();
						$path_new=url($image->marca_dagua($path_new));
					}

					if(!filesize($destinationPath.'/'.$infos['basename'])) $stop++;
					else $path=$path_new;
				}
			}else $path=url($destinationPath.'/'.$infos['basename']);
			return $path;
		},
		'flip_image' => function($path, $horizontal=false, $vertical=false){
			return $path;
			$path_new=explode('/storage/', $path); $http=$path_new[0]; $path_new=end($path_new); $path_new='storage/'.$path_new;
			$image=new OtimizarImage();
			$path_new=$image->flip($path_new, $horizontal, $vertical);
			return $http.'/'.$path_new;
		},
		'resize' => function($file_path, $width = false, $height = false, $options = []) {
			$file_path=trim(str_replace(' /', '', ' '.$file_path));
				// if(!strpos("[".$file_path."]", url('/'))){
				// 	if(!file_exists($file_path)) return $file_path;
				// }
				// $infos=pathinfo($file_path);
			$settings=Functions3::getSettings();
				// if(isset($infos['extension']) && (($infos['extension'] == 'webp' && !$settings->converter_webp) || $infos['extension'] == 'svg')){
				// if(isset($infos['extension']) && ($infos['extension'] == 'svg')){
			if(strpos("[".$file_path." ]", ".svg ")){
				if(!strpos("[".url('/')."]",$file_path)) return url($file_path);
				else return $file_path;
			}
			if(!$this->image_resize){
				$this->image_resize=new \Diveramkt\Uploads\Classes\Image($file_path);
			}else $this->image_resize->setFilepath($file_path);

			return $this->image_resize->resize($width, $height, $options);
		},
	];
}
public $image_resize=null;

public function gerar_pastas_image($path, $path_new){

	$exp=explode('/', $path_new);
	$cam='';
	foreach ($exp as $key => $value) {
		if($value == end($exp)) continue;
		$cam.=$value.'/';
		if(!file_exists($cam)) mkdir($cam, 0777);
	}

	return $path_new;
		// if(!file_exists($path_new)) copy($path, $path_new);
		// return $http.'/'.$path_new;
}

public function delTree($dir=false) { 
	$files = array_diff(scandir($dir), array('.','..')); 
	foreach ($files as $file) { 
		(is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file"); 
	} 
	return rmdir($dir); 
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

	// private function addPositionedFormFields($form, $config, $where = null)
	// {
	// 	$beforeFields   = [];
	// 	$afterFields    = [];
	// 	$sectionDetails = false;

	// 	$first = array_first($config, function () {
	// 		return true;
	// 	});

	// 	$beforeField = is_array($first) ? array_get($first, 'before') : null;
	// 	$afterField  = is_array($first) ? array_get($first, 'after') : null;

	// 	$fields = $form->fields;
	// 	if ($where == 'primary') {
	// 		$fields = $form->tabs["fields"];
	// 	}
	// 	if ($where == 'secondary') {
	// 		$fields = $form->secondaryTabs["fields"];
	// 	}

	// 	foreach ($fields as $field => $value) {
	// 		$item      = $form->getField($field);
	// 		$itemName  = $item->fieldName;

	// 		if ($itemName == $afterField or  $itemName == $beforeField or $sectionDetails) {
	// 			if ($itemName == $afterField and !$sectionDetails) {
	// 				$sectionDetails = true;
	// 			} else {
	// 				$afterFields[$itemName] = $item->config;
	// 				$sectionDetails         = true;
	// 				$form->removeField($field);
	// 			}
	// 		}
	// 	}

	// 	switch ($where) {
	// 		case 'primary':
	// 		$form->addTabFields($config, $where);
	// 		$form->addTabFields($afterFields, $where);
	// 		break;
	// 		case 'secondary':
	// 		$form->addSecondaryTabFields($config, $where);
	// 		$form->addSecondaryTabFields($afterFields, $where);
	// 		break;
	// 		default:
	// 		$form->addFields($config, $where);
	// 		$form->addFields($afterFields, $where);
	// 	}
	// }

}
