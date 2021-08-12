<?php
namespace Diveramkt\Uploads;

use System\Classes\PluginBase;
use Event;
use Db;
// use Response;
// use imagejpg;
use Stdclass;
use Input;
use Auth;
// use BackendAuth;
use Backend\Models\User;
// use October\Rain\Auth\AuthException;
// use Backend\Facades\BackendAuth;

use Diveramkt\Uploads\Models\Settings;
use Diveramkt\Uploads\Models\Informacoes;

use Diveramkt\Uploads\Classes\Libtiny\Tinify\Exception;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\ResultMeta;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Result;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Source;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Cliente;
use Diveramkt\Uploads\Classes\Libtiny\Tinify;

// use Diveramkt\Uploads\Classes\Wideimage\WideImage;

// https://phpimageworkshop.com/
// https://phpimageworkshop.com/quickstart.html
// use Diveramkt\Uploads\Classes\PHPImageWorkshop\ImageWorkshop;

use System\Classes\MediaLibrary;
// use Diveramkt\Uploads\Formwidgets\FileUpload;

// use Backend\FormWidgets\FileUpload;
// use Diveramkt\Uploads\FormWidgets\FileUpload as FileUploadExtend;
// use Diveramkt\Uploads\FormWidgets\FileUpload;

use Backend\Facades\BackendAuth;
use Diveramkt\Uploads\Classes\OtimizarImage;
// use October\Rain\Database\Attach\Resizer;
use October\Rain\Database\Attach\File;
use File as FileHelper;
use Diveramkt\Uploads\Classes\Extra\Fileuploads;

// Resizer::open(Input::file('field_name'))->resize(800, 600, 'crop')->save('path/to/file.jpg', 100);
use Storage;
use Artisan;
use Request;
use System\Classes\PluginManager;
use Http;
use str;

// use Arcane\Seo\Models\Settings SettingsArcane;

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

	public $configs;

	public function base_link($url, $folder='media'){
		if(config('cms.storage.'.$folder.'.disk') == 'local') return urldecode(parse_url($url, PHP_URL_PATH));
		else{
			$folder=config('cms.storage.'.$folder.'.folder');
			$url=urldecode(parse_url($url, PHP_URL_PATH));
			$url=explode($folder.'/', $url);
			unset($url[0]);
			$url='/'.$folder.'/'.implode($folder.'/', $url);
			return $url;
		}
	}

	public function boot(){

		// $image=url('/').'/themes/simple/assets/images/1-safol-Missao.jpg';

		// $image=url('/').'/teste/teste2.jpg';
		// $file = new Fileuploads;
		// $file->deleteThumbs($image);
		
		// $file->setFolder('teste');
		// $file->setRename('teste2');
		// if($this->isExterno()) $file->fromUrl($url);
		// else $file->fromFile($url);
		// $file->fromFile($image);


		// $file = new Fileuploads;
		// $file = new File;
		// $image='themes/simple/assets/images/1-safol-Missao.jpg';
		// $file->fromFile($image);

		// $image='http://safol2018.october/storage/app/media/teste/floresta1.jpg';
		// $image='https://safol.divera.com.br/storage/app/media/201412181055290.COMPRASSINPENSAR1812.jpg';
		// $image='https://s3-sa-east-1.amazonaws.com/safol/media/teste/floresta1.jpg';
		// $image='http://safol2018.october/storage/app/uploads/public/60d/e15/f46/60de15f469ae6325165174.jpg';

		// $veri=pathinfo($this->base_link($image));
		// // $veri=pathinfo('arquivo_renomeado.jpg');
		// echo '<pre>';
		// print_r($veri);
		// echo '</pre>';

		// echo '<pre>';
		// print_r(urldecode(parse_url($image, PHP_URL_PATH)));
		// echo '</pre>';


		// $image2='http://safol2018.october/storage/app/uploads/public/60e/84a/173/thumb__100_0_0_0_auto.jpg';
		// $file->setFolder('storage/app/media/teste');

		// $file->setFolder('teste');
		// $file->setRename('arquivo_renomeado.png');
		// // $file->fromUrl($image);
		// $file->fromFile($image);

		// echo '<pre>';
		// print_r($file->getResult());
		// echo '</pre>';

		// $image3='https://safol.divera.com.br/storage/app/uploads/public/61f/455/c87/thumb__312_0_0_0_auto.jpg';

		// $veri=pathinfo($image3);

		// echo '<pre>';
		// print_r($veri);
		// echo '</pre>';

		// $image=base_path($image);
		// echo $image;

		// $options=[];
		// $width=300;
		// $height='auto';
		// $thumb = $file->getThumb($width, $height, $options);
		// echo $thumb;
		// FileHelper::put();

		// FileHelper::copy($image2, 'teste.jpg');

		// // $directory=$file->getStorageDirectory();
		// // $name=$file->disk_name;

		// // $url='http://localhost/uploads/public/60e/847/aac/thumb__100_0_0_0_auto.jpg';
		// // echo storage_path('app/'.'teste');

		// // echo config('cms.storage.uploads.path');
		// $thumb=explode('uploads/', $thumb);
		// $thumb=end($thumb);
		// $url=config('cms.storage.uploads.path').'/'.$thumb;

		// $url=url('/').'/storage/app/uploads/public/60e/84a/173/thumb__100_0_0_0_auto.jpg';
		// echo $url;
		// echo '<img src="'.$url.'" />';


		// echo '<br/>';
		// echo '<br/>';
		// echo '<br/>';
		// echo '<br/>';


		$class=get_declared_classes();
		
	// 	$settings_upload = \Diveramkt\Uploads\Models\Settings::instance();
	// 	$this->config=$settings_upload;
		
	// 	$veri='';
	// 	if(Request::server('DOCUMENT_ROOT')) $veri.=' '.Request::server('DOCUMENT_ROOT').' ';
	// 	if(Request::server('CONTEXT_DOCUMENT_ROOT')) $veri.=' '.Request::server('CONTEXT_DOCUMENT_ROOT').' ';

	// 	if(isset($settings_upload['redirect_www']) && $settings_upload['redirect_www']
	// 		&& (!strpos("[".$veri."]", "C:/") || !strpos("[".$veri."]", "xampp/") || !strpos("[".$veri."]", ".october") || !strpos("[".$veri."]", "public_html"))
	// 	){

	// 		$red=$settings_upload['redirect_www'];

	// 	$config['base_url'] = str_replace('\/','/','http' . ( Request::server('HTTPS') == 'on' ? 's' : '') . '://' . Request::server('HTTP_HOST') . str_replace('//', '/', dirname(Request::server('SCRIPT_NAME')) . '/'));

	// 	$pos = strpos($config['base_url'], 'www');
	// 	if ($pos === false) {

	// 		$redirecionar=true;
	// 		if(str_replace(' ','',$settings_upload['sub_dominios']) != ''){

	// 			$subs = preg_replace('/[\n|\r|\n\r|\r\n]{2,}/',',', $settings_upload['sub_dominios']);
	// 			$subs = preg_replace("/\r?\n/",',', $subs);
	// 			$subs=explode(',', str_replace(';', ',', $subs));

	// 			if(count($subs) > 0){
	// 				foreach ($subs as $key => $sub) {
	// 					if(strpos("[".$config['base_url']."]", "http://".$sub) || strpos("[".$config['base_url']."]", "https://".$sub)) $redirecionar=false; 
	// 				}
	// 			}

	// 		}

	// 		if($redirecionar){
	// 			$url=(@Request::server('HTTPS') == 'on' ? 'https://' : 'http://').'www.'.Request::server('SERVER_NAME').Request::server('REQUEST_URI');

	// 			header("HTTP/1.1 ".$red." Moved Permanently");
	// 			header("Location:".$url);
	// 			exit();
	// 		}

	// 	}

	// }


	Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
		$class=get_declared_classes();
        // $controller->addJs('/path/to/your/javascript/file.js');
		$user = BackendAuth::getUser();
		if(isset($user->id) && $user->id && (in_array('Arcane\Seo\Models\Settings', $class) || in_array('Arcane\Seo\Plugin', $class))){
			$infosArcane = \Arcane\Seo\Models\Settings::instance();
			if($infosArcane->minify_html) \Storage::deleteDirectory('arcane/seo/minify');
		}
		if($user->id && in_array('SerenityNow\Cacheroute\Plugin', $class)) Artisan::call('cache:clear');
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

	\System\Models\File::extend(function($model) {


		$model->bindEvent('model.afterCreate', function() use ($model) {
		// $model->bindEvent('model.afterUpdate', function() use ($model) {
			if((isset($this->config['disabled']) and $this->config['disabled']) || !strpos("[".$model->path."]", ".") || !$this->veri_extension_image($model->extension)) return;

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
			$retorno=$retorno['infos_results'];

			if($retorno){
				$infodb=pathinfo($model->file_name);
				$ext=$infodb['extension'];

				$model->disk_name=str_replace('.'.$ext,'.'.$retorno['extension'], $model->disk_name);
				$model->file_name=str_replace('.'.$ext,'.'.$retorno['extension'], $model->file_name);

				$model->content_type=$retorno['mime_type'];

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

	Event::listen( 'media.file.upload', function ( $widget, $filePath, $uploadedFile ) {
		$info=pathinfo($filePath);
		$ext=$info['extension'];
		if((isset($this->config['disabled']) and $this->config['disabled']) || !$this->veri_extension_image($ext)) return;

		$realPath = empty(trim($uploadedFile->getRealPath()))
		? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
		: $uploadedFile->getRealPath();

		$config=array();

		$url=MediaLibrary::url($filePath);
		$filePath='/media'.$filePath;
		if(config('cms.storage.media.disk') == 'local'){
			$url=url('/').$url;
			// $filePath=storage_path('app'.$filePath);
			$filePath='storage/app'.$filePath;
		}

		$image=new OtimizarImage($config);
		$retorno=$image->otimizar($url, $filePath,'media');

	});

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
		'marcaDagua' => function($path=false, $pasta_interna=false){
			return $path;
			// |marcaDagua()
			if(!strpos("[".$path."]", "/storage/")) return $path;
			$exp=explode('/', $path);
			$arquivo=end($exp);


			$settings_upload = \Diveramkt\Uploads\Models\Settings::instance();
			if(!$settings_upload->enabled_marca) return $path;
				// opacity_marca
				// proporcao_marca
				// espacamento_marca
				// imagem_marca

			$exp=explode('.', $arquivo);
			$name=$exp[0];
			$ext=end($exp);
			$new_name=$name.'.'.$ext;

			if(!$pasta_interna) $pasta='marcaDagua';
			else $pasta=$pasta_interna;
			$path_new=str_replace($arquivo, $pasta, $path);
			$path_new=explode('/storage/', $path_new); $http=$path_new[0]; $path_new=end($path_new); $path_new='storage/'.$path_new;

				// $options=$settings_upload->posicao_horizonal.$settings_upload->posicao_vertical.$settings_upload->opacity_marca.$settings_upload->proporcao_marca.$settings_upload->espacamento_marca;
			$options=$settings_upload->atualizacao_marca;

			if(!file_exists($path_new.'/'.$options)) if(file_exists($path_new)) $this->delTree($path_new);
			$path_new.='/'.$options;
			$path_new.='/'.$new_name;

				// $path_new=str_replace($arquivo, $pasta.'/'.$new_name, $path);

			if(file_exists($path_new)) return $http.'/'.$path_new;

			$path_new=$this->gerar_pastas_image($path, $path_new);

			$marcar=false;
			if(!file_exists($path_new)){
				$marcar=true;
				copy($path, $path_new);
			}


			if($marcar){
				$image=new OtimizarImage();
				$path_new=$image->marca_dagua($path_new);
			}

				// echo '<img src="'.$http.'/'.$path_new.'" width="100" />';
			return $http.'/'.$path_new;

				// return $settings_upload->imagem_marca->path;
				// return $settings_upload->posicao_horizonal.$settings_upload->posicao_vertical.$settings_upload->opacity_marca.$settings_upload->proporcao_marca.$settings_upload->espacamento_marca;
		},
		'flip_image' => function($path, $horizontal=false, $vertical=false){
			return $path;
			$path_new=explode('/storage/', $path); $http=$path_new[0]; $path_new=end($path_new); $path_new='storage/'.$path_new;
			$image=new OtimizarImage();
			$path_new=$image->flip($path_new, $horizontal, $vertical);
			return $http.'/'.$path_new;
		},
		'resize' => function($file_path, $width = false, $height = false, $options = []) {
			$image = new \Diveramkt\Uploads\Classes\Extra\Image($file_path);
			return $image->resize($width, $height, $options);
		},
	];
}
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

	// public function limparDiretorio($pasta=false){
	// 	 if(is_dir($pasta)) {
	// 		  $diretorio = dir($pasta);

	// 		  while($arquivo = $diretorio->read())
	// 		  {
	// 			   if(($arquivo != '.') && ($arquivo != '..'))
	// 			   {
	// 				//     unlink($pasta.$arquivo);
	// 				    echo 'Arquivo '.$arquivo.' foi apagado com sucesso. <br />';
	// 			   }
	// 		  }

	// 		  $diretorio->close();
	// 	//  }
	// 	//  else
	// 	//  {
	// 		//   echo 'A pasta não existe.';
	// 	 }
	// }

public function registerMarkupTags()
{
	$filters = [];
        // add PHP functions
	$filters += $this->getPhpFunctions();

	return [
		'filters'   => $filters,
	];
}

private function addPositionedFormFields($form, $config, $where = null)
{
	$beforeFields   = [];
	$afterFields    = [];
	$sectionDetails = false;

	$first = array_first($config, function () {
		return true;
	});

	$beforeField = is_array($first) ? array_get($first, 'before') : null;
	$afterField  = is_array($first) ? array_get($first, 'after') : null;

	$fields = $form->fields;
	if ($where == 'primary') {
		$fields = $form->tabs["fields"];
	}
	if ($where == 'secondary') {
		$fields = $form->secondaryTabs["fields"];
	}

	foreach ($fields as $field => $value) {
		$item      = $form->getField($field);
		$itemName  = $item->fieldName;

		if ($itemName == $afterField or  $itemName == $beforeField or $sectionDetails) {
			if ($itemName == $afterField and !$sectionDetails) {
				$sectionDetails = true;
			} else {
				$afterFields[$itemName] = $item->config;
				$sectionDetails         = true;
				$form->removeField($field);
			}
		}
	}

	switch ($where) {
		case 'primary':
		$form->addTabFields($config, $where);
		$form->addTabFields($afterFields, $where);
		break;
		case 'secondary':
		$form->addSecondaryTabFields($config, $where);
		$form->addSecondaryTabFields($afterFields, $where);
		break;
		default:
		$form->addFields($config, $where);
		$form->addFields($afterFields, $where);
	}
}

}
