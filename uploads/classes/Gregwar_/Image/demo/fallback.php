<?php

require_once '../autoload.php';

use Diveramkt\Uploads\Classes\Gregwar\Image\Image;

echo Image::open('img/something-wrong.png')
    ->resize(100, 0)
    ->negate()
    ->jpeg()
    ;
