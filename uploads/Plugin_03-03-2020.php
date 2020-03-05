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

	// public function registerFormWidgets() {
	// 	// return [ 'Creator\Client\FormWidgets\FileUploader' => [ 'label' => 'FileUploader', 'code' => 'FileUploader' ], ];
	// 	return [ 'Diveramkt\Uploads\FormWidgets\FileUpload' => [ 'label' => "FileUpload", "alias" => "ckafileupload" ], ];
	// }

	public function boot(){
		include 'plugins/diveramkt/uploads/classes/Gregwar/Image/Image.php';
		// include 'plugins/diveramkt/uploads/formwidgets/FileUpload.php';


		// Validator::extend('required_ifcnpj', function($attribute, $value, $parameters) {
        //     return $value == 'required_ifcnpj';
        //     // return false;
        // });

        // $veri=new FileUpload();

        // echo '<pre>';
        // print_r($veri);
        // echo '</pre>';

		// FileUpload::extend('onUpload', function($var=false, $var2=false) {
			// return;
		// });


		// $veri=new FileUpload();
		// $texto=json_encode($veri);

		// $arquivo = "meu_arquivo.txt";
		// $fp = fopen($arquivo, "w+");
		// fwrite($fp, $texto);
		// fclose($fp);

		// Event::listen( 'media.file.upload', function ( $widget, $filePath, $uploadedFile ) {
		// Event::listen( 'FileUpload.onUpload', function ( $widget=false, $filePath=false, $uploadedFile=false ) {

		// 	$texto='veri onupload';
		// 	$arquivo = "meu_arquivo.txt";
		// 	$fp = fopen($arquivo, "w+");
		// 	fwrite($fp, $texto);
		// 	fclose($fp);

		// });


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
			if($user->role_id == 2 or (isset($permissoes['delete_media']) && $permissoes['delete_media'] == 1) ) $widget->addDynamicProperty('deletar', true);
			else $widget->addDynamicProperty('deletar', false);

			if(isset($permissoes['readOnly_media']) && $permissoes['readOnly_media'] == 1) $widget->readOnly=true;
			$widget->addViewPath(plugins_path().'/diveramkt/uploads/backend/widgets/mediamanager/partials/');

		});
		// //////////////GERENCIAMENTO NAS IMAGENS E ARQUIVOS NO MEDIA

		// echo \Diveramkt\Uploads\Formwidgets\FileUpload::teste();
		// echo 'teste 2';


		// $veri=new FileUpload();
		// print_r(json_encode($veri));
		// $veri=new FileUploadExtend();

		// FileUpload::teste();

		// FileUpload::extend(function($model) {
		// 	$model->bindEvent('model.afterCreate', function() use ($model) {

		// 	});
		// });

		// $file = new System\Models\File;

		\System\Models\File::extend(function($model) {

			// $texto=' criado nova imagem '.time();
			// $arquivo = "meu_arquivo.txt";
			// $fp = fopen($arquivo, "w+");
			// fwrite($fp, $texto);
			// fclose($fp);
			$model->bindEvent('model.afterCreate', function() use ($model) {


				// $texto=' criado nova imagem '.time();
				$texto=json_encode($model);
				$arquivo = "meu_arquivo.txt";
				$fp = fopen($arquivo, "w+");
				fwrite($fp, $texto);
				fclose($fp);
			});

		});

		FileUpload::extend(function($model) {

			// $model->bindEvent('model.afterCreate', function () use (\October\Rain\Database\Model $model) {
			// // \Log::info("{$model->name} was created!");

			// 	$texto=' criado nova imagem '.time();
			// 	$arquivo = "meu_arquivo.txt";
			// 	$fp = fopen($arquivo, "w+");
			// 	fwrite($fp, $texto);
			// 	fclose($fp);
			// });

			// $model->bindEvent('model.afterUpdate', function () use (\October\Rain\Database\Model $model) {
			// // \Log::info("{$model->name} was created!");

			// 	$texto=' atualizado algum item '.time();
			// 	$arquivo = "meu_arquivo.txt";
			// 	$fp = fopen($arquivo, "w+");
			// 	fwrite($fp, $texto);
			// 	fclose($fp);
			// });

		// FileUploadExtend::extend(function($model) {
		// \Diveramkt\Uploads\Formwidgets\FileUpload::extend(function($model) {

			// self::

			$escrever=false;
			$texto=' texto padrao '.time();
			// if (Input::hasFile('file_data')) {
			// 	$uploadedFile = Input::file('file_data');
			// 	$texto=json_encode($uploadedFile);
			// 	// $texto=serialize($uploadedFile);
			// }

			// $model->importFile->getPath()
			$texto=json_encode($model->importFile);

			// print_r($veri);
			// $texto=json_encode($model);

			// $model->addDynamicMethod('teste', function($query=false) {

			// 	$texto='function teste externo ';
			// 	$arquivo = "meu_arquivo.txt";
			// 	$fp = fopen($arquivo, "w+");
			// 	fwrite($fp, $texto);
			// 	fclose($fp);

			// });
			// $veri=$model->teste();

			$model->addDynamicProperty('onUpload', function($query) {
				return;
			});
			
			// return;

			// $model->bindEvent('model.getAttribute', function($attribute, $value) {
   //              if ($attribute == 'foo') {
   //                  return 'bar';
   //              }
   //          });
			$model->addDynamicMethod('onUpload', function($query=false) {

				// $texto='onUpload externo';
				// $arquivo = "meu_arquivo.txt";
				// $fp = fopen($arquivo, "w+");
				// fwrite($fp, $texto);
				// fclose($fp);
				// return 'teste function';

				// try {
				// 	if (!Input::hasFile('file_data')) {
				// 		throw new ApplicationException('File missing from request');
				// 	}
				// }
				// catch (Exception $ex) {
				// 	$response = Response::make($ex->getMessage(), 400);
				// }
				// return $response;
			});

			// $model->onUpload=function(){
			// 	$texto='onUpload externo 2';
			// 	$arquivo = "meu_arquivo.txt";
			// 	$fp = fopen($arquivo, "w+");
			// 	fwrite($fp, $texto);
			// 	fclose($fp);

			// }
			// $model->onUpload();

			// $model->extendClassWith('Backend\FormWidgets\FileUpload', function($query=false) {
				// return 'teste function';
			// });

			// $texto=json_encode($model->getClassMethods());
			
			// $texto=json_encode($model->getClassExtension('onUpload'));

			// $model->addDynamicProperty('teste', function($query) {
			// 	// $texto='veri onupload';
			// 	// $arquivo = "meu_arquivo.txt";
			// 	// $fp = fopen($arquivo, "w+");
			// 	// fwrite($fp, $texto);
			// 	// fclose($fp);
			// 	return 'teste function';
			// });

			// $model->teste= function(){
			// }
			// $texto=$model->teste();

			// $texto_array='';
			// $texto_object='';

			// $texto=$model->teste(function(){
			// 	return 'teste';
			// });

			// $texto=' teste ';
			// $veri=$model->getConfigFormWidget();

			// $veri=$model->getConfigFormWidget();
			// $texto=$model->teste();

			// $model->bindEvent('model.getAttribute', function($attribute, $value) {
			// 	if ($attribute === 'foo') {
			// 		return 'bar';
			// 	}
			// });

			// $model->bindEvent('model.getAttribute', function($attribute, $value) {
			// 	// if ($attribute === 'foo') {
			// 	// 	return 'bar';
			// 	// }

			// 	$texto=$value;
			// 	$arquivo = "meu_arquivo.txt";
			// 	$fp = fopen($arquivo, "w+");
			// 	fwrite($fp, $texto);
			// 	fclose($fp);
			// });


			// $model->addDynamicMethod('teste_function', function($query) {
			// 	return 'teste 2';
			// });
			// $model->teste_function(function(){})

			// $model->teste_var=true;
			// $texto=json_encode($model);
			// $texto=$model->teste_function();

			// $texto=$model->testeca();
			// $texto=$model->teste();
			// $texto=json_encode($model->getConfigFormWidget());

			// $texto = json_encode(Input::all());
			// $texto=json_encode($model);
			// $texto=json_encode($this);
			// $texto=json_encode($model->getSaveValue(false));

			// foreach ($model as $key => $value) {
			// 	// $texto.=' '.$key.' ';
			// 	if(is_array($value)){
			// 		// $texto.' '.$key.': '.serialize($value);
			// 		foreach ($value as $key1 => $vet) {
			// 			$texto_array.=' '.$key1.': 
			// 			';	
			// 		}
			// 	}elseif(is_object($value)){
			// 		// $value->model
			// 		// $value->data
			// 		// if(isset($value->model)) $texto_object.=$value->model;
			// 		// if(isset($value->data)) $texto_object.=$value->data;
			// 		// if(isset($value->config)) $texto_object.=$value->config;
			// 		foreach ($value as $key1 => $vet) {
			// 			$texto_object.=' '.$key1.': 
			// 			';	
			// 		}
			// 	}else{
			// 		$texto.=' '.$key.': '.$value.'
			// 		';
			// 	}
			// }

			// $texto.=' data: '.$model->data.' ';
			// $texto.=' model: '.$model->model.' ';

			// $arquivo = "meu_arquivo_array.txt";
			// $fp = fopen($arquivo, "w+");
			// fwrite($fp, $texto_array);
			// fclose($fp);

			// $arquivo = "meu_arquivo_object.txt";
			// $fp = fopen($arquivo, "w+");
			// fwrite($fp, $texto_object);
			// fclose($fp);

			if($escrever){
				$arquivo = "meu_arquivo.txt";
				$fp = fopen($arquivo, "w+");
				fwrite($fp, $texto);
				fclose($fp);
			}
		});



		// FileUpload::extend(function($model) {
		// 	function onUpload(){
		// 		return;
		// 	// print_r('teste');
		// 		// $vals=array() $file;
		// 		$texto='testando upload';
		// 		$arquivo = "nome_arquivo.txt";
		// 		$fp = fopen($arquivo, "w+");
  //           // fwrite($fp, $file->pathUrl);
		// 		fwrite($fp, $texto);
		// 		fclose($fp);
		// 	},
		// 	// function teste(){
		// 	// 	echo 'teste';
		// 	// },
		// 	// $model->bindEvent('user.register', function() use ($model) {
  //  //      // Code to register $model->email to mailing list
		// 	// });
		// });

		// FileUpload::onUpload();
		// print_r('teste');


        // $this->app->resolving('validator', function($validator) {
        //     Validator::extend('requiredifcnpj', 'Martin\Forms\Classes\Requiredifcnpj');
        // });

        // Validator::resolver(function($translator, $data, $rules, $messages, $customAttributes) {
        //     return new CustomValidator($translator, $data, $rules, $messages, $customAttributes); 
        // });
        // Validator::extend('priceformat', 'Martin\Forms\Classes\CustomValidator');


				// if(isset($_GET['teste'])){
			// $image=new Image('teste.png');
			// $image->resize(100);
			// $image->save('teste1.png','jpg',80);

			// echo '<pre>';
			// print_r(get_declared_classes());
			// echo '<pre>';
			// exit;
			// print_r(__DIR__);
		// }

		// $dados=Settings::first();
		// $dados = Db::table('system_settings')->where('item',$dados->settingsCode)->first();
		// if(!isset($dados->value)) $dados=new stdclass();
		// else $dados=json_decode($dados->value);

		// $imagem_marca = Settings::instance();
		// $imagem_marca = $imagem_marca->imagem_marca;
		// $marca=$imagem_marca->getPath();

		// $marca=str_replace(array('http://'.$_SERVER['HTTP_HOST'].'/','https://'.$_SERVER['HTTP_HOST'].'/'),array('',''),$marca);
		// echo '<pre>';
		// // print_r(base_path());
		// // print_r('http://'.$_SERVER['HTTP_HOST']);
		// // print_r($imagem_marca);
		// print_r($marca);
		// echo '</pre>';

Event::listen( 'media.file.upload', function ( $widget, $filePath, $uploadedFile ) {

			// $post=new Input();
			// $arquivo = fopen('nome_arquivo.txt','w+');
			// $texto=serialize($post);
			// fwrite($arquivo, $texto);
			// fclose($arquivo);

	$caminho=array();
	$caminho['media']='storage/app/media';
	$dados=Settings::first();
	$dados = Db::table('system_settings')->where('item',$dados->settingsCode)->first();
	if(!isset($dados->value)) $dados=new stdclass();
	else $dados=json_decode($dados->value);

	if(!isset($dados->name_arq)) $dados->name_arq=false;
	if(!isset($dados->converter_jpg)) $dados->converter_jpg=false;
	if(!isset($dados->tamanho_max) || !$dados->tamanho_max || $dados->tamanho_max == 0 || str_replace(' ', '', $dados->tamanho_max) == '') $dados->tamanho_max=3000;
	if(!isset($dados->compression) || !$dados->compression || $dados->compression == 0 || str_replace(' ', '', $dados->compression) == '') $dados->compression=80;

			// ////////GET NAME E EXTENSION DA IMAGE
	$original_name  = $uploadedFile->getClientOriginalName();
	$ext     = pathinfo( $original_name, PATHINFO_EXTENSION );
	$original_name_no_ext = pathinfo( $original_name, PATHINFO_FILENAME );
	$original_name_no_ext=pathinfo( $filePath, PATHINFO_FILENAME );
			// ////////GET NAME E EXTENSION DA IMAGE


			// ////////CONVERTER NAME IMAGE TO COD
	$cod=md5(uniqid(mt_rand()));

			// MediaLibrary::instance()->moveFile( $filePath, str_replace($original_name_no_ext,$cod,$filePath) );
			// $filePath_local=str_replace($original_name_no_ext,$cod,$filePath);

			// $filePath_local=pathinfo( $filePath, PATHINFO_FILENAME );
	$filePath_local=str_replace(array('/'.$original_name_no_ext,'.jpeg'),array('/'.$cod,'.jpg'), $filePath);
	if($ext == 'jpeg') $ext='jpg';
	MediaLibrary::instance()->moveFile( $filePath, $filePath_local );
	$filePath=str_replace('.jpeg','.jpg',$filePath);
			// ////////CONVERTER NAME IMAGE TO COD

	$size = getimagesize($caminho['media'].'/'.$filePath_local);

	$image=new Image($caminho['media'].'/'.$filePath_local);
			// $image_work = ImageWorkshop::initFromPath($caminho['media'].'/'.$filePath_local);
	
	if($size[0] > $dados->tamanho_max && $size[0] > $size[1]) $image->resize($dados->tamanho_max,false,'transparent');
	elseif($size[1] > $dados->tamanho_max && $size[1] > $size[0]) $image->resize(false,$dados->tamanho_max,'transparent');

			// if($size[0] > $dados->tamanho_max && $size[0] > $size[1]) $image_work->resizeInPixel($dados->tamanho_max, null, true);
			// elseif($size[1] > $dados->tamanho_max && $size[1] > $size[0]) $image_work->resizeInPixel(null, $dados->tamanho_max, true);



	if($dados->converter_jpg) $new_ext='jpg';
	else $new_ext=$ext;

	$filePath_local_new=$filePath_local;
	if($dados->converter_jpg) $filePath_local_new=str_replace('.'.$ext,'.'.$new_ext,$filePath_local);

	$image->save($caminho['media'].'/'.$filePath_local_new,$new_ext,$dados->compression);



	$imagem_marca = Settings::instance();
	$imagem_marca = $imagem_marca->imagem_marca;
	if($imagem_marca){
		$marca=$imagem_marca->getPath();
		$marca=str_replace(array('http://'.$_SERVER['HTTP_HOST'].'/','https://'.$_SERVER['HTTP_HOST'].'/'),array('',''),$marca);

			// /////////////////////////////////////MARCA DAGUA
				// $size = getimagesize($image);

				// $posicao_horizonal='center'; if(isset($dados->posicao_horizonal) && $dados->posicao_horizonal) $posicao_horizonal=$dados->posicao_horizonal;

				// $posicao_vertical='center'; if(isset($dados->posicao_vertical) && $dados->posicao_vertical) $posicao_vertical=$dados->posicao_vertical;

				// $opacity_marca=50; if(isset($dados->opacity_marca) && $dados->opacity_marca > 0 && $dados->opacity_marca < 101) $opacity_marca=$dados->opacity_marca;

				// $proporcao_marca=50; if(isset($dados->proporcao_marca) && $dados->proporcao_marca > 0 && $dados->proporcao_marca < 101) $proporcao_marca=$dados->proporcao_marca;

				// $espacamento_marca=20; if(isset($dados->espacamento_marca) && $dados->espacamento_marca > 0 && $dados->espacamento_marca < 101) $espacamento_marca=$dados->espacamento_marca;


				// $size_marca = getimagesize($marca);
				// if($size[0] > $size[1]){
				// 	$width=($size[0]*$proporcao_marca)/100;
				// 	$height=($width*$size[1])/$size[0];
				// }else{
				// 	$height=($size[1]*$proporcao_marca)/100;
				// 	$width=($height*$size[0])/$size[1];
				// }
				// if($width > $size_marca[0] || $height > $size_marca[1]){
				// 	$width=$size_marca[0];
				// 	$height=$size_marca[1];
				// }

				// $x=0; $y=0;
				// if($posicao_horizonal == 'center') $x=($size[0]/2)-($width/2);
				// elseif($posicao_horizonal == 'left') $x=$espacamento_marca;
				// elseif($posicao_horizonal == 'right') $x=$size[0]-$width-$espacamento_marca;

				// if($posicao_vertical == 'center') $y=($size[1]/2)-($height/2);
				// elseif($posicao_vertical == 'top') $y=$espacamento_marca;
				// elseif($posicao_vertical == 'bottom') $y=$size[1]-$height-$espacamento_marca;

				// $image_marca=Image::open($marca)->cropResize($width, $height)->opacity($opacity_marca);
				// $image->merge($image_marca,$x,$y);
				// $image->save($caminho['media'].'/'.$filePath_local_new,'jpg',$dados->compression);
			// /////////////////////////////////////MARCA DAGUA
	}

	if($filePath_local_new != $filePath_local) unlink($caminho['media'].'/'.$filePath_local);
			// return;

				// //////////VERIFICAR SE EXITE IMAGE COM MESMO NOME E CRIAR UM NOME COM UM ID
	$stop=1;
	$veri='';
	for ($i=0; $i < $stop; $i++) {
		if($i > 2) continue;

		if($dados->name_arq) $new_name = $original_name_no_ext.($i?'-'.$i:'');
		else $new_name = str_slug( $original_name_no_ext, '-' ).($i?'-'.$i:'');

		$veri=' '.$new_name.' ';
		$newPath=str_replace(array($original_name_no_ext,'.'.$ext), array($new_name,'.'.$new_ext), $filePath);
		if(file_exists($caminho['media'].$newPath)) $stop++;
	}
	$new_name.='.' . strtolower($ext);

				// //////////VERIFICAR SE EXITE IMAGE COM MESMO NOME E CRIAR UM NOME COM UM ID
			// }

			// $arquivo = fopen('nome_arquivo.txt','w+');
			// $texto=$caminho['media'].$newPath.' - '.$i.' - '.$veri;
			// fwrite($arquivo, $texto);
			// fclose($arquivo);
			// return;

			// if($filePath != $newPath) MediaLibrary::instance()->moveFile( $filePath, $newPath );
	MediaLibrary::instance()->moveFile( $filePath_local_new, $newPath );

			// ///////PASSAR IMAGEM NO TINYPNG PARA OTIMIZAR
	if(isset($dados->api_tiny) && str_replace(' ','',$dados->api_tiny) != ''){
		$informacoes=Informacoes::where('id',1)->first();
		if(!isset($informacoes['id'])){
			Informacoes::insert( ['mes_tinypng' => date('mY'), 'count_tinypng' => 0] );
			$informacoes=array();
			$informacoes['mes_tinypng']=date('mY');
			$informacoes['count_tinypng']=0;
		}

		if($informacoes['count_tinypng'] < 500 || $informacoes['mes_tinypng'] != date('mY')){
				// $api_key='mLM462vSbljMXWLkwwBNJ4GYBgdZ6VTv';
			$api_key=$dados->api_tiny;
			\Tinify\setKey($api_key);

			$source = \Tinify\fromFile(storage_path() . '/app/media'.$newPath);
			$compressionsThisMonth = \Tinify\compressionCount();
			if($compressionsThisMonth <= 500) $source->toFile(storage_path() . '/app/media'.$newPath);
			Informacoes::where('id', 1)->update(['count_tinypng' => $compressionsThisMonth, 'mes_tinypng' => date('mY')]);
		}
	}
			// ///////PASSAR IMAGEM NO TINYPNG PARA OTIMIZAR

} );

}

}
