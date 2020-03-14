<?php
namespace Diveramkt\Uploads\Classes;

use Diveramkt\Uploads\Classes\Gregwar\Image\Image;
// use Diveramkt\Uploads\Classes\Gregwar\Cache\Cache;
use Diveramkt\Uploads\Models\Settings;
use Diveramkt\Uploads\Models\Informacoes;
// use System\Classes\MediaLibrary;
use Event;
use Db;
use Stdclass;
use Input;

class OtimizarImage {

	static public $compression=100;
	static public $tamanho_max=3000;
	static public $converter_jpg=false;
	static public $name_arq=false;
	static public $api_tiny=false;
	static public $rename=true;
	static public $imagem_marca=false;

	static public $compression_small_size=50000;
	static public $compression_small=20;

	function __construct($config=false)
	{

		$dados2=Settings::first();
		$settins_instance = Settings::instance();
		$dados = Db::table('system_settings')->where('item',$dados2->settingsCode)->first();
		if(!isset($dados->value)) $dados=new stdclass();
		else $dados=json_decode($dados->value);

		if(!isset($dados->name_arq)) $dados->name_arq=false;
		if(!isset($dados->converter_jpg)) $dados->converter_jpg=false;
		if(!isset($dados->tamanho_max) || !$dados->tamanho_max || $dados->tamanho_max == 0 || str_replace(' ', '', $dados->tamanho_max) == '') $dados->tamanho_max=3000;
		if(!isset($dados->compression) || !$dados->compression || $dados->compression == 0 || str_replace(' ', '', $dados->compression) == '') $dados->compression=80;

		if(isset($config['compression'])) self::$compression=$config['compression'];
		else{
			if(isset($config['compression'])) self::$compression=$config['compression'];
			elseif(isset($dados->compression)) self::$compression=$dados->compression;
		}

		if(isset($config['compression_small_size'])) self::$compression_small_size=$config['compression_small_size'];
		elseif(isset($dados->compression_small_size)) self::$compression_small_size=$dados->compression_small_size;

		if(isset($config['compression_small'])) self::$compression_small=$config['compression_small'];
		elseif(isset($dados->compression_small)) self::$compression_small=$dados->compression_small;


		if(isset($config['name_arq'])) self::$name_arq=$config['name_arq'];
		elseif(isset($dados->name_arq)) self::$name_arq=$dados->name_arq;

		if(isset($config['tamanho_max'])) self::$tamanho_max=$config['tamanho_max'];
		elseif(isset($dados->tamanho_max)) self::$tamanho_max=$dados->tamanho_max;

		if(isset($config['converter_jpg'])) self::$converter_jpg=$config['converter_jpg'];
		elseif(isset($dados->converter_jpg)) self::$converter_jpg=$dados->converter_jpg;

		if(isset($config['api_tiny'])) self::$api_tiny=$config['api_tiny'];
		elseif(isset($dados->api_tiny)) self::$api_tiny=$dados->api_tiny;

		// if(isset($config['imagem_marca'])) self::$imagem_marca=$config['imagem_marca'];
		// else self::$imagem_marca=$settins_instance->imagem_marca;
		if($settins_instance->imagem_marca) self::$imagem_marca=$settins_instance->imagem_marca;

		if(isset($config['rename'])) self::$rename=$config['rename'];
		elseif(isset($dados->rename)) self::$rename=$dados->rename;
	}

	// compress_images($caminho,$destino,90);
	static public function compress_images($source, $destination, $quality) {
		// include 'plugins/diveramkt/uploads/classes/Gregwar/Image/Image.php';

		$linha='';
		$ponteiro = fopen ($source,"r");
		while (!feof ($ponteiro)) { $linha.= fgets($ponteiro, 4096); }
		fclose ($ponteiro);
		if(empty($linha)) return;

		$info = getimagesize($source);
		if(!isset($info['mime']) && $info['mime'] != '') return;
		$antigo=filesize($source);

		if ($info['mime'] == 'image/jpeg'){
			$image = imagecreatefromjpeg($source);
			if(!isset($image)) return;
			imagejpeg($image, $destination, $quality);
		}elseif ($info['mime'] == 'image/gif') {
			$image = imagecreatefromgif($source);
			if(!isset($image)) return;
			// imagealphablending($image, true);
			// imagesavealpha($image, true);
			// imagegif($image, $destination, $quality);
			imagegif($image, $destination);
		}elseif ($info['mime'] == 'image/png') {
			$quality=($quality/10); $quality=round($quality); $quality=(10-($quality));
			$image = imagecreatefrompng($source);
			if(!isset($image)) return;
			imagealphablending($image, true);
			imagesavealpha($image, true);
			// $quality=($quality/10); $quality=round($quality); $quality=(10-($quality));
			imagepng($image, $destination, $quality);
		}

		$novo=filesize($destination);
		$tamanho['antigo']=$antigo;
		$tamanho['novo']=$novo;
    // return $destination;
		return $tamanho;
	}

	static public function compress_png($path_to_png_file, $max_quality = 80)
	{

		// https://pngquant.org/php.html
		if (!file_exists($path_to_png_file)) {
			// throw new Exception("File does not exist: $path_to_png_file");
			return;
		}


    // guarantee that quality won't be worse than that.
		$min_quality = 10;

    // '-' makes it use stdout, required to save to $compressed_png_content variable
    // '<' makes it read from the given file path
    // escapeshellarg() makes this safe to use with any path
		$compressed_png_content = shell_exec("pngquant --quality=$min_quality-$max_quality - < ".escapeshellarg(    $path_to_png_file));

		// if (!$compressed_png_content) {
			// throw new Exception("Conversion to compressed PNG failed. Is pngquant 1.8+ installed on the server?");
		// }

		// $arquivo = "meu_arquivo.txt";
		// $fp = fopen($arquivo, "w+");
		// fwrite($fp, $compressed_png_content);
		// fclose($fp);

		if ($compressed_png_content) file_put_contents($path_to_png_file, $compressed_png_content);
		// return $compressed_png_content;
	}

	static public function otimizar($imagem=false, $local=false){
		if(!$imagem) return $imagem;
		$base = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['SCRIPT_NAME']) . '/');
		$base=str_replace('\/','/',$base);

		$ext=explode('.', $imagem); $ext=end($ext);
		$ext_original=$ext;

		if(self::$converter_jpg) $ext='jpg';
		// if($ext == 'jpeg' || self::$converter_jpg) $ext='jpg';

		$caminho=str_replace($base,'',$imagem);
		if(!exif_imagetype($caminho)) return false;
		$caminho_novo=str_replace('.'.$ext_original,'.'.$ext,$caminho);

		// ///////////////////////////////////////
		// if($local == 'midias'){
		if(self::$rename){
			$exp=explode('/', $caminho); $name_arq_original=explode('.', end($exp)); $name_arq_original=$name_arq_original[0];
			$caminho_rename=str_replace('.'.$ext_original,md5(uniqid(mt_rand())).'.'.$ext_original,$caminho);
			// $caminho_rename=str_replace($name_arq_original, str_slug($name_arq_original,'-').'-'.md5(uniqid(mt_rand())), $caminho);
			rename($caminho, $caminho_rename);
			$caminho=$caminho_rename;
		}
		// ///////////////////////////////////////

		// $arquivo = "meu_arquivo.txt";
		// $fp = fopen($arquivo, "w+");
		// fwrite($fp, json_encode(filesize($caminho)));
		// fclose($fp);

		$filesize=filesize($caminho);
		if($filesize <= self::$compression_small_size) self::$compression=self::$compression_small;

		$image=new Image($caminho);
		
		// RESIZE IMAGE TAMANHO MÁXIMO
		$size = getimagesize($caminho);
		if($size[0] > self::$tamanho_max && $size[0] > $size[1]) $image->resize(self::$tamanho_max,false,'transparent');
		elseif($size[1] > self::$tamanho_max && $size[1] > $size[0]) $image->resize(false,self::$tamanho_max,'transparent');
		// else $image->resize($size[0],false,'transparent');
		// RESIZE IMAGE TAMANHO MÁXIMO


			// //////////VERIFICAR SE EXITE IMAGE COM MESMO NOME E CRIAR UM NOME COM UM ID
		$texto='';
		if((!self::$name_arq || $ext != $ext_original) && $local == 'midias' && self::$rename){

			$arq_=explode('/', $caminho_novo);
			
			$name=end($arq_); $name=str_replace('.'.$ext, '', $name);
			$caminho_base=str_replace('/'.$name.'.'.$ext,'',$caminho_novo);
			$name_original=$name;
			if(!self::$name_arq) $name=str_slug($name,'-');
			$name_arq=$name.'.'.$ext;

			// if(mb_strtolower($name_original, 'UTF-8') != $name){
			$stop=1;
			for ($i=0; $i < $stop; $i++) {
				if($i > 2) continue;

				if($i) $name_arq=$name.'-'.$i.'.'.$ext;
				if(file_exists($caminho_base.'/'.$name_arq)) $stop++;
			}
			// }
			$caminho_novo=$caminho_base.'/'.$name_arq;

		}

		// MediaLibrary::instance()->moveFile( $filePath_local_new, $newPath );
			// //////////VERIFICAR SE EXITE IMAGE COM MESMO NOME E CRIAR UM NOME COM UM ID

		// SALVANDO IMAGES
		$image->save($caminho_novo,$ext,self::$compression);
		// SALVANDO IMAGES

		// if($ext == 'jpg' && self::$compression < 100) self::compress_images($caminho_novo,$caminho_novo,self::$compression);

		// //////////////////COMPRESSION NAS PNG
		// if($ext == 'png' && self::$compression < 100) self::compress_images($caminho_novo,$caminho_novo,self::$compression);
		// if($ext == 'png' && self::$compression < 100) self::compress_png($caminho_novo,self::$compression);
		// //////////////////COMPRESSION NAS PNG

		// return;
		if($local == 'midias' && file_exists($caminho) && $caminho != $caminho_novo) unlink($caminho);
		else if($ext != $ext_original && file_exists($caminho) && $caminho != $caminho_novo) unlink($caminho);

		// if(self::$imagem_marca) $image=self::marca_dagua($image);

		// ///////PASSAR IMAGEM NO TINYPNG PARA OTIMIZAR
		if(isset(self::$api_tiny) && str_replace(' ','',self::$api_tiny) != '' && self::$api_tiny && $ext == 'png'){
			$informacoes=Informacoes::where('id',1)->first();
			if(!isset($informacoes['id'])){
				Informacoes::insert( ['mes_tinypng' => date('mY'), 'count_tinypng' => 0] );
				$informacoes=array();
				$informacoes['mes_tinypng']=date('mY');
				$informacoes['count_tinypng']=0;
			}

			if($informacoes['count_tinypng'] < 500 || $informacoes['mes_tinypng'] != date('mY')){
				// $api_key='mLM462vSbljMXWLkwwBNJ4GYBgdZ6VTv';
				$api_key=self::$api_tiny;
				\Tinify\setKey($api_key);

				// $source = \Tinify\fromFile(storage_path() . '/app/media'.$newPath);
				// $compressionsThisMonth = \Tinify\compressionCount();
				// if($compressionsThisMonth <= 500) $source->toFile(storage_path() . '/app/media'.$newPath);

				$source = \Tinify\fromFile($caminho_novo);
				$compressionsThisMonth = \Tinify\compressionCount();
				if($compressionsThisMonth <= 500) $source->toFile($caminho_novo);

				Informacoes::where('id', 1)->update(['count_tinypng' => $compressionsThisMonth, 'mes_tinypng' => date('mY')]);
			}
		}
			// ///////PASSAR IMAGEM NO TINYPNG PARA OTIMIZAR

		$retorno['imagem']=$caminho_novo;
		$retorno['ext']=$ext;
		$retorno['filesize']=filesize($caminho_novo);
		$retorno['mime_type']=mime_content_type($caminho_novo);
		return $retorno;
	}


	static public function marca_dagua($image) {

		// $arquivo = "meu_arquivo.txt";
		// $fp = fopen($arquivo, "w+");
		// fwrite($fp, json_encode(self::$imagem_marca));
		// fclose($fp);

		return $image;
		$imagem_marca=self::$imagem_marca;
		// return $image;
		// $imagem_marca = Settings::instance();
		// $imagem_marca = $imagem_marca->imagem_marca;
		// if($imagem_marca){
		$marca=$imagem_marca->getPath();
		$marca=str_replace(array('http://'.$_SERVER['HTTP_HOST'].'/','https://'.$_SERVER['HTTP_HOST'].'/'),array('',''),$marca);

			/////////////////////////////////////MARCA DAGUA
		$size = getimagesize($image);

		$posicao_horizonal='center'; if(isset($dados->posicao_horizonal) && $dados->posicao_horizonal) $posicao_horizonal=$dados->posicao_horizonal;

		$posicao_vertical='center'; if(isset($dados->posicao_vertical) && $dados->posicao_vertical) $posicao_vertical=$dados->posicao_vertical;

		$opacity_marca=50; if(isset($dados->opacity_marca) && $dados->opacity_marca > 0 && $dados->opacity_marca < 101) $opacity_marca=$dados->opacity_marca;

		$proporcao_marca=50; if(isset($dados->proporcao_marca) && $dados->proporcao_marca > 0 && $dados->proporcao_marca < 101) $proporcao_marca=$dados->proporcao_marca;

		$espacamento_marca=20; if(isset($dados->espacamento_marca) && $dados->espacamento_marca > 0 && $dados->espacamento_marca < 101) $espacamento_marca=$dados->espacamento_marca;


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

		$image_marca=Image::open($marca)->cropResize($width, $height)->opacity($opacity_marca);
		$image->merge($image_marca,$x,$y);
		$image->save($caminho['media'].'/'.$filePath_local_new,'jpg',$dados->compression);
			/////////////////////////////////////MARCA DAGUA
		// }

	}

}