<?php
namespace _____Diveramkt\Uploads;

use System\Classes\PluginBase;
use Event;
use Db;
// use imagejpg;
// use Stdclass;

use Diveramkt\Uploads\Models\Settings;
use Diveramkt\Uploads\Models\Informacoes;

use Diveramkt\Uploads\Classes\Libtiny\Tinify\Exception;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\ResultMeta;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Result;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Source;
use Diveramkt\Uploads\Classes\Libtiny\Tinify\Cliente;
use Diveramkt\Uploads\Classes\Libtiny\Tinify;
// use Diveramkt\Uploads\Class\Tinypng

// php composer.phar require gregwar/image
// use Gregwar\Image\Image;

// use Diveramkt\Uploads\Class\Tinypng

// use Diveramkt\Uploads\Classes\Wideimage\Exception WideImage_Exception;
// use Diveramkt\Uploads\Classes\Wideimage\Image;
// use Diveramkt\Uploads\Classes\Wideimage\TrueColorImage;
// use Diveramkt\Uploads\Classes\Wideimage\PaletteImage;
// use Diveramkt\Uploads\Classes\Wideimage\Coordinate;
// use Diveramkt\Uploads\Classes\Wideimage\Canvas;
// use Diveramkt\Uploads\Classes\Wideimage\MapperFactory;
// use Diveramkt\Uploads\Classes\Wideimage\OperationFactory;
// use Diveramkt\Uploads\Classes\Wideimage\Font\TTF;
// use Diveramkt\Uploads\Classes\Wideimage\Font\GDF;
// use Diveramkt\Uploads\Classes\Wideimage\Font\PS;

// https://phpimageworkshop.com/
// use Diveramkt\Uploads\Classes\PHPImageWorkshop\Exception\ImageWorkshopException;
// use Diveramkt\Uploads\Classes\PHPImageWorkshop\ImageWorkshop;
// use PHPImageWorkshop\ImageWorkshop;

// use Diveramkt\Uploads\Classes\Wideimage\WideImage;
// use ToughDeveloper\ImageResizer\Classes\Image;

// use Intervention\Image\ImageManager;

use System\Classes\MediaLibrary;

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
				'keywords'    => 'whatsapp link diveramkt',
				'permissions' => ['Uploads.manage_upload']
			]
		];
	}

	// ////////////////////REMOVER ESPAÇO NOME ARQUIVOS UPLOAD
	function compress_images($source, $destination, $quality) {

		$linha='';
		$ponteiro = fopen ($source,"r");
		while (!feof ($ponteiro)) { $linha.= fgets($ponteiro, 4096); }
		fclose ($ponteiro);
		if(empty($linha)) return;

		$info = getimagesize($source);
		if(!isset($info['mime']) && $info['mime'] != '') return;
		$antigo=filesize($source);

		if ($info['mime'] == 'image/jpeg') 
			$image = imagecreatefromjpeg($source);
		elseif ($info['mime'] == 'image/gif') 
			$image = imagecreatefromgif($source);
		elseif ($info['mime'] == 'image/png') 
			$image = imagecreatefrompng($source);

		if(!isset($image)) return;
		imagejpeg($image, $destination, $quality);
		$novo=filesize($destination);
		$tamanho['antigo']=$antigo;
		$tamanho['novo']=$novo;
    // return $destination;
		return $tamanho;
	}

	function converter_to_jpg($imagem_entrada, $caminho){
		$imagem_entrada=str_replace('//','/',$caminho.'/'.$imagem_entrada);
		$ext     = pathinfo( $imagem_entrada, PATHINFO_EXTENSION );

		if(strtolower($ext) == 'jpg') return str_replace($caminho.'/', '', $imagem_entrada);
		elseif(strtolower($ext) == 'jpeg'){
			$imagem_saida=str_replace('jpeg','jpg',$imagem_entrada);
			$img = imagecreatefromjpeg($imagem_entrada);
		}elseif(strtolower($ext) == 'gif'){
			$imagem_saida=str_replace('gif','jpg',$imagem_entrada);
			$img = imagecreatefromgif($imagem_entrada);
		}elseif(strtolower($ext) == 'png'){
			$imagem_saida=str_replace('png','jpg',$imagem_entrada);
			$img = imagecreatefrompng($imagem_entrada);
		}

		$w = imagesx($img);
		$h = imagesy($img);
		$trans = imagecolortransparent($img);
		if($trans >= 0) {
			$rgb = imagecolorsforindex($img, $trans);
			$oldimg = $img;
			$img = imagecreatetruecolor($w,$h);
			$color = imagecolorallocate($img,$rgb['red'],$rgb['green'],$rgb['blue']);
			imagefilledrectangle($img,0,0,$w,$h,$color);
			imagecopy($img,$oldimg,0,0,0,0,$w,$h);
		}
		imagejpeg($img,$imagem_saida);
		// imagejpg($img,$imagem_saida);

		unlink($imagem_entrada);
		return str_replace($caminho.'/', '', $imagem_saida);
	}

	public function boot(){
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

		// $layer = ImageWorkshop::initFromPath('teste.png');
		// $layer->resizeInPixel(null, 300, true);
		// // echo $layer->getWidth();
		// // echo $layer->getHeight();

		// $dirPath = str_replace('plugins\diveramkt\uploads','',__DIR__);
		// // $dirPath='C:\xampp\htdocs\diadieta.october\public_html';
		// // $dirPath='';
		// $filename = "teste1.png";
		// $createFolders = true;
		// $backgroundColor = null; // transparent, only for PNG (otherwise it will be white if set null)
		// $imageQuality = 80; // useless for GIF, usefull for PNG and JPEG (0 to 100%)
		// $layer->save($dirPath, $filename, $createFolders, $backgroundColor, $imageQuality);


		// print_r('teste');
		// $image = WideImage::load('teste.png');

		// echo '<pre>';
		// print_r($image);
		// echo '</pre>';

		// $image->resize(100, 100);
		// $image->saveToFile('teste2.png', 80);

		// $dados=Settings::first();
		// $dados = Db::table('system_settings')->where('item',$dados->settingsCode)->first();
		// $dados=json_decode($dados->value);

		// echo '<pre>';
		// print_r($dados);
		// echo '</pre>';

		// Gregwar
		// $image=new Image('teste.png');
		// $image->open('teste.png');
		// $image->resize(100, 100);
		// $image->save('teste2.jpg','png',50);
		// $image->savePng('testes',50);

		// $veri=Tinify();
		// print_r($veri);

		// print_r(get_declared_classes()); exit;
		
		Event::listen( 'media.file.upload', function ( $widget, $filePath, $uploadedFile ) {
			$caminho=array();
			$caminho['media']='storage/app/media';
			$dados=Settings::first();
			$dados = Db::table('system_settings')->where('item',$dados->settingsCode)->first();
			$dados=json_decode($dados->value);

			if(!isset($dados->name_arq)) $dados->name_arq=false;
			if(!isset($dados->converter_jpg)) $dados->converter_jpg=false;


			$original_name  = $uploadedFile->getClientOriginalName();
			$ext     = pathinfo( $original_name, PATHINFO_EXTENSION );

			// //////////CONVERTER IMAGENS PARA JPG
			if($dados->converter_jpg){
				$filePath=$this->converter_to_jpg($filePath,$caminho['media']);
				$original_name=str_replace('.'.$ext,'.jpg',$original_name);
				$ext='jpg';
			}
			// //////////CONVERTER IMAGENS PARA JPG

			$original_name_no_ext = pathinfo( $original_name, PATHINFO_FILENAME );

			if($dados->name_arq) $new_name = $original_name_no_ext.'.'.strtolower($ext);
			else{
				// //////////VERIFICAR SE ESTA IMAGEM JA EXITE E CRIAR UM NOME COM UM ID
			// $new_name = str_slug( $original_name_no_ext, '-' ).'.' . strtolower($ext);
				$stop=1;
				for ($i=0; $i < $stop; $i++) { 
					$new_name = str_slug( $original_name_no_ext, '-' ).($i?'-'.$i:'');
					if(file_exists($caminho['media'].str_replace($original_name_no_ext, $new_name, $filePath))){
						$stop++;
					}
				}
				$new_name.='.' . strtolower($ext);
				// //////////VERIFICAR SE ESTA IMAGEM JA EXITE E CRIAR UM NOME COM UM ID
			}

			// if ( $new_name == $original_name ) { return; }
			$original_name_pronto = str_replace( $ext, strtolower($ext), $original_name );
			$newPath = str_replace( $original_name_pronto, $new_name, $filePath );

			// $arquivo = fopen('nome_arquivo.txt','w+');
			// // $texto=$this->key_tiny;
			// // $texto = storage_path() . '/app/media'.$newPath;
			// // $texto=$filePath.' - '.$newPath.' - '.$uploadedFile.' - '.$original_name_no_ext;
			// // $texto='storage/app/media'.str_replace($original_name_no_ext, str_slug( $original_name_no_ext, '-' ), $filePath);
			// // if(file_exists($texto)){
			// // 	$texto='existe';
			// // }
			// $texto=str_slug( $original_name_no_ext, '-' ).' - '.$original_name_no_ext;
			// // $texto=$ext.' - '.str_slug($ext,'').' - '.$original_name_pronto.' - '.$new_name.' - '.$filePath;
			// fwrite($arquivo, $texto);
			// fclose($arquivo);

			// $texto=' - '.$newPath;

			if($filePath != $newPath) MediaLibrary::instance()->moveFile( $filePath, $newPath );

			// ///////COMPRESSÃO DA IMAGEM DE ACORDO COM A QUALIDADE PASSADA
			if(!isset($dados->compression) || !$dados->compression || $dados->compression == 0 || str_replace(' ', '', $dados->compression) == '') $dados->compression=80;
			if($dados->compression > 100) $dados->compression=100; if($dados->compression < 0) $dados->compression=0;
			$this->compress_images($caminho['media'].$newPath,$caminho['media'].$newPath,$dados->compression);
			// ///////COMPRESSÃO DA IMAGEM DE ACORDO COM A QUALIDADE PASSADA

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
            // ////////////////////REMOVER ESPAÇO NOME ARQUIVOS UPLOAD

}
