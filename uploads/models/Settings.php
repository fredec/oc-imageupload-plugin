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

}
