<?php

require_once '../autoload.php';

use Diveramkt\Uploads\Classes\Gregwar\Image\GarbageCollect;

GarbageCollect::dropOldFiles(__DIR__.'/cache', 5, true);
