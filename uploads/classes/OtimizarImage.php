<?php
namespace Diveramkt\Uploads\Classes;

use Gregwar\Image\Image;
// use Diveramkt\Uploads\Classes\Gregwar\Image\Image;
// use Diveramkt\Uploads\Classes\Gregwar\Cache\Cache;
use Diveramkt\Uploads\Models\Settings;
use Diveramkt\Uploads\Models\Informacoes;
// use System\Classes\MediaLibrary;
use Event;
use Db;
use Stdclass;
use Input;
use Request;
use str;

// use October\Rain\Database\Attach\File;
use File as FileHelper;
use Http;
use Diveramkt\Uploads\Classes\Extra\Fileuploads;

class OtimizarImage {

	public $settings='';
	public $config=[];

	function __construct($config=false)
	{

		$this->settings = Settings::instance();
		$default=[
			'compression' => 90,
			'tamanho_max' => 3000,
			'converter_ext' => false,
			'name_arq' => false,
			'api_tiny' => false,
			'rename' => true,
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

	public function base_link_file($url, $folder='uploads'){
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



	public function otimizar($url=false, $link=false, $local=false){
		$path=config('cms.storage.'.$local.'.path');
		$file = new Fileuploads;
		$file->setSettings($this->settings);
		$infos=pathinfo($link);

		if(!$url || !$file->checkFile($url)) return false;
		$file->setFolder($infos['dirname'], $link);

		if($local == 'media'){
			if(!$this->config['name_arq']) $new_name=str::slug($infos['filename']);
			$new_name=str::slug($infos['filename']).'.'.$infos['extension'];
			$file->setRename(mb_strtolower($new_name, 'UTF-8'));
		}

		$options=[
			'quality' => $this->config['compression'],
			'compress' => true,
			'extension' => $infos['extension'],
		];
		if($this->config['converter_ext']) $options['extension']=$this->config['converter_ext'];

		// $options['extension']
		// $texto=json_encode(pathinfo(url($this->filesave)));
		// $texto='extensão: '.$options['extension'];
  //       $arquivo = "meu_arquivo.txt";
  //       $fp = fopen($arquivo, "w+");
  //       fwrite($fp, $texto);
  //       fclose($fp);

		$file->resizeOptions('auto','auto',$options);
		$file->maxWidth($this->config['tamanho_max']);

		if(isset($this->settings->api_tiny_enabled) && $this->settings->api_tiny_enabled && isset($this->settings->api_tiny) && !empty($this->settings->api_tiny) && $this->settings->api_tiny){
			if(!$this->settings->api_tiny_enabled_png || ($this->settings->api_tiny_enabled_png && $options['extension'] == 'png')) $file->setKeyTinypng($this->settings->api_tiny);
		}

		if($file->isExterno()) $file->fromUrl($url);
		else $file->fromFile($url);

		$result=$file->getResult();
		$infos2=pathinfo($result['filesave']);

		if($file->success()){
			if($infos['basename'] != $infos2['basename']) $file->deleteFileUrl($url, $local);
			return $result;
		}else return false;

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

		// $marca=str_replace(array('http://'.$_SERVER['HTTP_HOST'].'/','https://'.$_SERVER['HTTP_HOST'].'/'),array('',''),$marca);
		$marca=str_replace(array('http://'.Request::server('HTTP_HOST').'/','https://'.Request::server('HTTP_HOST').'/'),array('',''),$marca);

			/////////////////////////////////////MARCA DAGUA

		try {
			$size = @getimagesize($image);

			$posicao_horizonal='center'; if(isset($this->settings->posicao_horizonal) && $this->settings->posicao_horizonal) $posicao_horizonal=$this->settings->posicao_horizonal;

			$posicao_vertical='center'; if(isset($this->settings->posicao_vertical) && $this->settings->posicao_vertical) $posicao_vertical=$this->settings->posicao_vertical;

			$opacity_marca=50; if(isset($this->settings->opacity_marca) && $this->settings->opacity_marca > 0 && $this->settings->opacity_marca < 101) $opacity_marca=$this->settings->opacity_marca;

			$proporcao_marca=50; if(isset($this->settings->proporcao_marca) && $this->settings->proporcao_marca > 0 && $this->settings->proporcao_marca < 101) $proporcao_marca=$this->settings->proporcao_marca;

			$espacamento_marca=20; if(isset($this->settings->espacamento_marca) && $this->settings->espacamento_marca > 0 && $this->settings->espacamento_marca < 101) $espacamento_marca=$this->settings->espacamento_marca;


			$size_marca = getimagesize($marca);
			if($size[0] > $size[1]){
				$width=($size[0]*$proporcao_marca)/100;
				$height=($width*$size[1])/$size[0];
			}else{
				$height=($size[1]*$proporcao_marca)/100;
				$width=($height*$size[0])/$size[1];
			}
			if($width > $size_marca[0] || $height > $size_marca[1]){
				$width=$size_marca[0];
				$height=$size_marca[1];
			}

			$x=0; $y=0;
			if($posicao_horizonal == 'center') $x=($size[0]/2)-($width/2);
			elseif($posicao_horizonal == 'left') $x=$espacamento_marca;
			elseif($posicao_horizonal == 'right') $x=$size[0]-$width-$espacamento_marca;

			if($posicao_vertical == 'center') $y=($size[1]/2)-($height/2);
			elseif($posicao_vertical == 'top') $y=$espacamento_marca;
			elseif($posicao_vertical == 'bottom') $y=$size[1]-$height-$espacamento_marca;

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