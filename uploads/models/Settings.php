<?php namespace Diveramkt\Uploads\Models;

use Model;

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
}
