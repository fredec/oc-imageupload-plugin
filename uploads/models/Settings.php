<?php namespace Diveramkt\Uploads\Models;

use Model;
use Db;

class Settings extends Model
{
	public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
	public $settingsCode = 'uploads_settings';

    // Reference to field configuration
	public $settingsFields = 'fields.yaml';

	public $attachOne = [
		'imagem_marca' => 'System\Models\File'
	];

	public function beforeUpdate(){
		$veri=Db::table($this->table)->where('id',$this->id)->first();
		$val=json_decode($veri->value);
		$vals=$this->value;

		// $arquivo = "meu_arquivo.txt";
		// $fp = fopen($arquivo, "w+");
		// fwrite($fp, 'atualizando');
		// fclose($fp);

		// or $val->imagem_marca != $vals['imagem_marca']
		if(
			(isset($val->enabled_marca) && $val->enabled_marca != $vals['enabled_marca'])
			or (isset($val->posicao_horizonal) && $val->posicao_horizonal != $vals['posicao_horizonal'])
			or (isset($val->posicao_vertical) && $val->posicao_vertical != $vals['posicao_vertical'])
			or (isset($val->opacity_marca) && $val->opacity_marca != $vals['opacity_marca'])
			or (isset($val->proporcao_marca) && $val->proporcao_marca != $vals['proporcao_marca'])
			or (isset($val->espacamento_marca) && $val->espacamento_marca != $vals['espacamento_marca'])
		){
			$vals['atualizacao_marca']=date('YmdHis');
			$this->value=$vals;
		}
	}

	public function afterFetch(){
		if(!$this->allowed_files) $this->allowed_files=implode(',', $this->getAllowedFilesOptions());
		if(!$this->allowed_images) $this->allowed_images=implode(',', $this->getAllowedImagesOptions());
	}

	public function getAllowedImagesOptions(){
		$extension=['svg','jpg', 'jpeg', 'bmp', 'png', 'webp', 'gif'];
		return $extension;
	}

	public function getAllowedFilesOptions(){
		$extension= [
            // defaults
			'json',
			'js',
			'map',
			'ico',
			'css',
			'less',
			'scss',
			'ics',
			'odt',
			'doc',
			'docx',
			'ppt',
			'pptx',
			'pdf',
			'swf',
			'txt',
			'xml',
			'ods',
			'xls',
			'xlsx',
			'eot',
			'woff',
			'woff2',
			'ttf',
			'flv',
			'wmv',
			'mp3',
			'ogg',
			'wav',
			'avi',
			'mov',
			'mp4',
			'mpeg',
			'webm',
			'mkv',
			'rar',
			'xml',
			'zip',
		];
		$extension=array_merge($extension, $this->getAllowedImagesOptions());
		return $extension;
	}

	// public function getAllowedFilesOptions(){
	// 	$extension= [
	// 		'json',
 //            // defaults
	// 		'jpg',
	// 		'jpeg',
	// 		'bmp',
	// 		'png',
	// 		'webp',
	// 		'gif',
	// 		'svg',
	// 		'js',
	// 		'map',
	// 		'ico',
	// 		'css',
	// 		'less',
	// 		'scss',
	// 		'ics',
	// 		'odt',
	// 		'doc',
	// 		'docx',
	// 		'ppt',
	// 		'pptx',
	// 		'pdf',
	// 		'swf',
	// 		'txt',
	// 		'xml',
	// 		'ods',
	// 		'xls',
	// 		'xlsx',
	// 		'eot',
	// 		'woff',
	// 		'woff2',
	// 		'ttf',
	// 		'flv',
	// 		'wmv',
	// 		'mp3',
	// 		'ogg',
	// 		'wav',
	// 		'avi',
	// 		'mov',
	// 		'mp4',
	// 		'mpeg',
	// 		'webm',
	// 		'mkv',
	// 		'rar',
	// 		'xml',
	// 		'zip',
	// 	];
	// 	return $extension;
	// }

}
