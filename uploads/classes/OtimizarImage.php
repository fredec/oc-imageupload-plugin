<?php
namespace Diveramkt\Uploads\Classes;

use Gregwar\Image\Image;
use Diveramkt\Uploads\Models\Settings;
use Diveramkt\Uploads\Models\Informacoes;
use Event;
use Db;
use Stdclass;
use Input;
use Request;
use str;

// use October\Rain\Database\Attach\File;
use File as FileHelper;
use Http;
use System\Classes\MediaLibrary;
use Storage;

use Diveramkt\Uploads\Classes\Functions3;

class OtimizarImage {

	public $settings='';
	public $config=[];

	function __construct($config=false)
	{

		// $this->settings = Settings::instance();
		$this->settings=Functions3::getSettings();
		$default=[
			'compression' => 90,
			'tamanho_max' => 3000,
			'converter_ext' => false,
			// 'name_arq' => false,
			'api_tiny' => false,
			// 'rename' => true,
			'imagem_marca' => false,
		];
		if(!$config) $config=[];
		$config=array_merge($default, $config);

		foreach ($config as $key => $value) {
			// if(isset($dados->$key) && $dados->$key) $this->$key=$dados->$key;
			// else $this->$key=$value;
			if(isset($this->settings->$key) && $this->settings->$key) $this->config[$key]=$this->settings->$key;
			else $this->config[$key]=$value;
		}
	}

	public function isS3(){
		if(config('cms.storage.uploads.disk') != 'local') return true;
		else return false;
	}

	// public function base_link_file($url, $folder='uploads'){
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

	// $texto=json_encode($path).' - '.json_encode($new).' checar ';
	// $arquivo = "meu_arquivo.txt";
	// $fp = fopen($arquivo, "w+");
	// fwrite($fp, $texto);
	// fclose($fp);
	// return;
	public function otimizar($url=false, $path=false, $local=false,$realPath=false){
		// $file=new Winter\Storm\Database\Attach\File();
		// $file=new October\Rain\Database\Attach\File();

		if(!$this->isLocalStorage()){
			$Functions3=new Functions3();
			$path_full=$Functions3->fromUrl($url);
			$check_path=array_reverse(explode('/', $path_full));

			$path_destination=$path;
			$path='';
			foreach ($check_path as $key => $value) {
				if(file_exists($path)) continue;
				if(!$key) $path=$value;
				else $path=$value.'/'.$path;
			}
		}

		$path=trim(str_replace(' /','',' '.$path.' '));
		if(!file_exists($path)) return;

		$path=implode('/',array_filter(explode('/', $path)));
		$infos=pathinfo($path);

		$ext=$infos['extension'];
		if($local != 'media'){
			if($this->config['converter_ext']) $ext=$this->config['converter_ext'];
			if($ext == 'jpeg') $ext='jpg';
		}

		$new=$infos['dirname'].'/'.$infos['filename'].'.'.$ext;
		$new=implode('/',array_filter(explode('/', $new)));

		$width=false; $height=false; $quality=80;
		$size = getimagesize($path);
		if($this->config['tamanho_max'] > 0){
			$max_width=$this->config['tamanho_max'];
			if($max_width && $size[0] > $max_width) $width=$max_width;
		// if($max_height && $size[1] > $max_height) $height=$max_height;
		}
		if($this->config['compression'] > 0) $quality=$this->config['compression'];

		if(isset($size[0]) && $width>$size[0]) $width=$size[0];
		if(isset($size[1]) && $height>$size[1]) $height=$size[1];

		if($height == 'auto') $height=false; if($width == 'auto') $width=false;

		// if(!$realPath) $realPath=$path; $image=new Image($realPath);
		$image=new Image($path);
		if($width || $height) $image->resize($width,$height,'transparent');

		$rotate=$this->correctOrientation($path);
		if($rotate) $image->rotate($rotate);

		$image->save($new,$ext,$quality);
		if($new != $path) FileHelper::delete($path);

		if(($ext == 'jpg' || $ext == 'jpeg') && $quality > 0){
                // https://github.com/gumlet/php-image-resize
			$image2 = new \Gumlet\ImageResize($new);
			$image2->quality_jpg=$quality;
			$image2->save($new);
		}
		$this->optimizeTiny($new,$ext);

		$return= [
			'success' => 1,
			'file_name' => $infos['filename'],
			'folder' => $infos['dirname'],
			'filesize' => filesize($image),
			'mime_type' => mime_content_type($new),
			'extension' => $ext,
		];

		if(!$this->isLocalStorage()){
			if(!$Functions3->copyLocalToStorage($new,$path_destination)) $return['success']=0;
		}

		return $return;
	}

	protected function isLocalStorage()
	{
		return Storage::getDefaultDriver() == 'local';
	}


	public function optimizeTiny($path,$ext){
		if(!isset($this->settings->api_tiny_enabled) || !$this->settings->api_tiny_enabled) return;
		if(!isset($this->settings->api_tiny) || !$this->settings->api_tiny) return;
		// if(!isset($this->settings->api_tiny) || !$this->settings->api_tiny) return;
		if($this->settings->api_tiny_enabled_png && $ext != 'png') return;

// ///////PASSAR IMAGEM NO TINYPNG PARA OTIMIZAR
		if($this->config['api_tiny']){
			if(!isset($this->settings->mes_tinypng)) $this->settings->mes_tinypng=date('mY');
			if(!isset($this->settings->count_tinypng) || !is_numeric($this->settings->count_tinypng)) $this->settings->count_tinypng=0;

			if($this->settings->count_tinypng < 500 || $this->settings->mes_tinypng != date('mY')){
                // $api_key='mLM462vSbljMXWLkwwBNJ4GYBgdZ6VTv';
				\Tinify\setKey($this->config['api_tiny']);

				$source = \Tinify\fromFile($path);
				$compressionsThisMonth = \Tinify\compressionCount();
				if($compressionsThisMonth < 500) $source->toFile($path);
				$compressionsThisMonth = \Tinify\compressionCount();

				$this->settings->mes_tinypng=date('mY');
				$this->settings->count_tinypng=$compressionsThisMonth;
				$this->settings->save();
			}
		}
            // ///////PASSAR IMAGEM NO TINYPNG PARA OTIMIZAR
	}


	static public function correctOrientation($imagem=false){
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

	static public function flip($image, $horizontal=0, $vertical=0){
		if(!$horizontal and !$vertical) return $image;
		
		$exp=explode('.', $image);
		$ext=end($exp);
		$name=str_replace('.'.$ext, '', $image);

		$new_name=$name;
		if($horizontal) $new_name.='-horizontal';
		if($vertical) $new_name.='-vertical';
		$new_name.='.'.$ext;
		if(file_exists($new_name)) return $new_name;

		$imagem=new Image($image);
		$imagem->flip($vertical,$horizontal);
		$new_name=$imagem->save($new_name);

		return $new_name;
	}

	public function marca_dagua($image) {
		if(!$this->config['imagem_marca']) return;
		$imagem_marca=$this->config['imagem_marca'];

		$marca=$imagem_marca->getPath();
		// $marca=str_replace(url('/').'/','',$marca);
		$marca=str_replace([url('/').'/',url('/')], ['',''], $marca);
			/////////////////////////////////////MARCA DAGUA

		try {
			$size = @getimagesize($image);

			$posicao_horizonal='center'; if(isset($this->settings->posicao_horizonal) && $this->settings->posicao_horizonal) $posicao_horizonal=$this->settings->posicao_horizonal;

			$posicao_vertical='center'; if(isset($this->settings->posicao_vertical) && $this->settings->posicao_vertical) $posicao_vertical=$this->settings->posicao_vertical;

			$opacity_marca=50; if(isset($this->settings->opacity_marca) && $this->settings->opacity_marca > 0 && $this->settings->opacity_marca < 101) $opacity_marca=$this->settings->opacity_marca;

			$proporcao_marca=50; if(isset($this->settings->proporcao_marca) && $this->settings->proporcao_marca >= 0 && $this->settings->proporcao_marca < 101) $proporcao_marca=$this->settings->proporcao_marca;

			$espacamento_marca=20; if(isset($this->settings->espacamento_marca) && $this->settings->espacamento_marca >= 0 && $this->settings->espacamento_marca < 101) $espacamento_marca=$this->settings->espacamento_marca;


			$size_marca = getimagesize($marca);

			$width=($size[0]*$proporcao_marca)/100;
			if($width>$size_marca[0]) $width=$size_marca[0];
			$height=($size_marca[1]*$width)/$size_marca[0];

			$x=$espacamento_marca; $y=$espacamento_marca;
			if($posicao_horizonal == 'center') $x=($size[0]/2)-($width/2);
			elseif($posicao_horizonal == 'right') $x=($size[0]-$width)-$espacamento_marca;

			if($posicao_vertical == 'center') $y=($size[1]/2)-($height/2);
			elseif($posicao_vertical == 'bottom') $y=($size[1]-$height)-$espacamento_marca;

			$imagem=new Image($image);
			$image_marca=Image::open($marca)->cropResize($width, $height)->opacity($opacity_marca);
			$imagem->merge($image_marca,$x,$y);
			// $imagem->save('teste.jpg','jpg',$this->compression);
			$imagem->save($image);
			return $image;
		} catch (Exception $e) {
			return $image;
		}

		// $imagem->save($caminho['media'].'/'.$filePath_local_new,'jpg',$dados->compression);
			/////////////////////////////////////MARCA DAGUA
		// }

	}


	static public function veri_extension_image($caminho){
		$info=pathinfo($caminho);
		$ext=mb_strtolower($info['extension'], 'UTF-8');
		if($ext == 'jpeg' or $ext == 'jpg' or $ext == 'png' or $ext == 'webp') return true;
		else false;
		// if(!exif_imagetype($caminho)) $type_imagem=false;
	}



}