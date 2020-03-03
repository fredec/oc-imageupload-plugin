<?php
namespace Diveramkt\Uploads\Components;
// namespace diveramkt\whtasappfloat\components;

use Diveramkt\Uploads\Models\Settings;
// use Detection\MobileDetect as Mobile_Detect;

class Upload extends \Cms\Classes\ComponentBase
{

	public function componentDetails(){
		return [
			'name' => 'Teste',
			'description' => 'Plugin para teste.'
		];
	}

	public function onRun(){
		// $this->key_tiny='mLM462vSbljMXWLkwwBNJ4GYBgdZ6VTv';
	}

	// public $key_tiny='teste';

}