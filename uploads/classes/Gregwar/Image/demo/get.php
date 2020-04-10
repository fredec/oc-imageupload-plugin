<?php

require_once '../autoload.php';

use Diveramkt\Uploads\Classes\Gregwar\Image\Image;

$image = Image::open('img/test.png')
    ->resize(100, 100)
    ->negate()
    ->get('jpeg');

echo $image;
