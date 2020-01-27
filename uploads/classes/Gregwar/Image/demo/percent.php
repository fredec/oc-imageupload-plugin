<?php

require_once '../autoload.php';

use Diveramkt\Uploads\Classes\Gregwar\Image\Image;

Image::open('img/test.png')
    ->resize('26%')
    ->negate()
    ->save('out.jpg', 'jpg');
