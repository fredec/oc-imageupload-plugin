<?php namespace Diveramkt\Uploads\Classes;

use File as FileHelper;
use Diveramkt\Uploads\Models\Settings;
use October\Rain\Network\Http;
use Exception;
use Storage;

class Functions3
{

	protected $autoMimeTypes = [
		'docx' => 'application/msword',
		'xlsx' => 'application/excel',
		'gif'  => 'image/gif',
		'png'  => 'image/png',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'webp' => 'image/webp',
		'pdf'  => 'application/pdf',
		'svg'  => 'image/svg+xml',
	];

	public static $getSettingsCache=false;
	public static function getSettings(){
		if(!Self::$getSettingsCache) Self::$getSettingsCache = Settings::instance();
		return Self::$getSettingsCache;
	}

	public function fromUrl($url, $filename = null)
	{
		$data = Http::get($url);

		if ($data->code != 200) {
			throw new Exception(sprintf('Error getting file "%s", error code: %d', $data->url, $data->code));
		}

		if (empty($filename)) {
            // Parse the URL to get the path info
			$filePath = parse_url($data->url, PHP_URL_PATH);

            // Get the filename from the path
			$filename = pathinfo($filePath)['filename'];

            // Attempt to detect the extension from the reported Content-Type, fall back to the original path extension if not able to guess
			$mimesToExt = array_flip($this->autoMimeTypes);
			if (!empty($data->headers['Content-Type']) && isset($mimesToExt[$data->headers['Content-Type']])) {
				$ext = $mimesToExt[$data->headers['Content-Type']];
			} else {
				$ext = pathinfo($filePath)['extension'];
			}

            // Generate the filename
			$filename = "{$filename}.{$ext}";
		}

		return $this->fromData($data, $filename);
	}

	public function fromData($data, $filename)
	{
		if ($data === null) {
			return;
		}

		$tempPath = temp_path($filename);
		FileHelper::put($tempPath, $data);

		// $file = $this->fromFile($tempPath);
		// FileHelper::delete($tempPath);

		// return $file;
		return $tempPath;
	}

	public function copyLocalToStorage($localPath, $storagePath)
	{
		$return=$this->getDisk()->put($storagePath, FileHelper::get($localPath), $this->isPublic() ? 'public' : null);
		if(file_exists($localPath)) FileHelper::delete($localPath);
		return $return;
	}

	public function getDisk()
	{
		return Storage::disk();
	}

	public function isPublic()
	{
		// if (array_key_exists('is_public', $this->attributes)) {
		// 	return $this->attributes['is_public'];
		// }

		// if (isset($this->is_public)) {
		// 	return $this->is_public;
		// }

		return true;
	}

}