<?php
namespace Diveramkt\Uploads\Components;
// namespace diveramkt\whtasappfloat\components;

use October\Rain\Filesystem\Definitions;
use Diveramkt\Uploads\Models\Settings;
// use Detection\MobileDetect as Mobile_Detect;
use ApplicationException;
use Input;

class Fileupload extends \Cms\Classes\ComponentBase
{

	use \Diveramkt\Uploads\Traits\Extendupload;

	public function componentDetails(){
		return [
			'name' => 'Upload para form',
			'description' => 'Gerar input file para upload de arquivo para formulários'
		];
	}

	public function defineProperties() {
		return [
			'button' => [
				'title' => 'Legenda do Botão',
				'default' => 'Clique aqui ou arrate os arquivos para fazer o upload',
			],
			'name_file' => [
				'title'             => 'Name do input',
				// 'description'       => 'diveramkt.uploads::lang.components.uploader.uploader_maxsize.description',
				'default'           => 'files',
				'type'              => 'string',
				'group'             => 'diveramkt.uploads::lang.components.uploader.group_uploader',
				'showExternalParam' => false,
			],
			'limit' => [
				'title'             => 'Limite de arquivos',
				// 'description'       => 'diveramkt.uploads::lang.components.uploader.uploader_maxsize.description',
				'default'           => '0',
				'type'              => 'string',
				'group'             => 'diveramkt.uploads::lang.components.uploader.group_uploader',
				'showExternalParam' => false,
			],
			'maxSize' => [
				'title'             => 'diveramkt.uploads::lang.components.uploader.uploader_maxsize.title',
				'description'       => 'diveramkt.uploads::lang.components.uploader.uploader_maxsize.description',
				'default'           => '5',
				'type'              => 'string',
				'group'             => 'diveramkt.uploads::lang.components.uploader.group_uploader',
				'showExternalParam' => false,
			],
			'fileTypes' => [
				'title'             => 'diveramkt.uploads::lang.components.uploader.uploader_types.title',
				'description'       => 'diveramkt.uploads::lang.components.uploader.uploader_types.description',
				'default'           => Definitions::get('defaultExtensions'),
				'type'              => 'stringList',
				'group'             => 'diveramkt.uploads::lang.components.uploader.group_uploader',
				'showExternalParam' => false,
			],
		];
	}

	public $fileTypes,$maxSize,$button,$limit;

	public function onRun(){
		if (Input::hasFile('Fileupload_file_data_'.$this->alias)) return $this->checkUploadAction('Fileupload_file_data_'.$this->alias);
		$this->addJs('/plugins/diveramkt/uploads/assets/js/uploader/dropzone.js');
		$this->addJs('/plugins/diveramkt/uploads/assets/js/uploader/uploader.js');
		$this->addCss('/plugins/diveramkt/uploads/assets/js/uploader/uploader.css');

		if($this->property('fileTypes') && is_array($this->property('fileTypes'))) $this->fileTypes='.'.implode(',.', $this->property('fileTypes'));
		$this->maxSize=$this->property('maxSize');
		$this->button=$this->property('button');
		$this->limit=$this->property('limit');
	}

	// public $key_tiny='teste';

}