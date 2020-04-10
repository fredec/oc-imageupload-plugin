<?php
namespace Diveramkt\Uploads\Classes\Wideimage;

use Diveramkt\Uploads\Classes\Wideimage\Exception;
use Diveramkt\Uploads\Classes\Wideimage\WideImage_Exception;

use Diveramkt\Uploads\Classes\Wideimage\Operation\AddNoise;
use Diveramkt\Uploads\Classes\Wideimage\Operation\ApplyConvolution;
use Diveramkt\Uploads\Classes\Wideimage\Operation\ApplyFilter;
use Diveramkt\Uploads\Classes\Wideimage\Operation\ApplyMask;
use Diveramkt\Uploads\Classes\Wideimage\Operation\AsGrayscale;
use Diveramkt\Uploads\Classes\Wideimage\Operation\AsNegative;
use Diveramkt\Uploads\Classes\Wideimage\Operation\AutoCrop;

use Diveramkt\Uploads\Classes\Wideimage\Operation\CopyChannelsPalette;
use Diveramkt\Uploads\Classes\Wideimage\Operation\CopyChannelsTrueColor;
use Diveramkt\Uploads\Classes\Wideimage\Operation\CorrectGamma;
use Diveramkt\Uploads\Classes\Wideimage\Operation\Crop;
use Diveramkt\Uploads\Classes\Wideimage\Operation\Flip;
use Diveramkt\Uploads\Classes\Wideimage\Operation\GetMask;
use Diveramkt\Uploads\Classes\Wideimage\Operation\Merge;
use Diveramkt\Uploads\Classes\Wideimage\Operation\Mirror;
use Diveramkt\Uploads\Classes\Wideimage\Operation\ResizeCanvas;
use Diveramkt\Uploads\Classes\Wideimage\Operation\Rotate;
use Diveramkt\Uploads\Classes\Wideimage\Operation\RoundCorners;
use Diveramkt\Uploads\Classes\Wideimage\Operation\Unsharp;

use Diveramkt\Uploads\Classes\Wideimage\Operation\Resize;
use Diveramkt\Uploads\Classes\Wideimage\Operation\WideImage_Operation_Resize;
// namespace OperationFactory;

	/**
 * @author Gasper Kozak
 * @copyright 2007-2011

    This file is part of WideImage.
		
    WideImage is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 2.1 of the License, or
    (at your option) any later version.
		
    WideImage is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.
		
    You should have received a copy of the GNU Lesser General Public License
    along with WideImage; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

	* @package Internals
  **/
	
	/**
	 * @package Exceptions
	 */
	class WideImage_UnknownImageOperationException extends WideImage_Exception {}
	
	/**
	 * Operation factory
	 * 
	 * @package Internals
	 **/
	class WideImage_OperationFactory
	{
		static protected $cache = array();
		
		static function get($operationName)
		{
			$lcname = strtolower($operationName);
			if (!isset(self::$cache[$lcname]))
			{
				$opClassName = "WideImage_Operation_" . ucfirst($operationName);
				if (!class_exists($opClassName, false))
				{
					$fileName = WideImage::path() . 'Operation/' . ucfirst($operationName) . '.php';
					if (file_exists($fileName))
						require_once $fileName;
					elseif (!class_exists($opClassName))
						throw new WideImage_UnknownImageOperationException("Can't load '{$operationName}' operation.");
				}

				self::$cache[$lcname] = new $opClassName();
			}
			return self::$cache[$lcname];
		}
	}
