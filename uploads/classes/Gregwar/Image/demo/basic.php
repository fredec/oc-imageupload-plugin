<?php

require_once '../autoload.php';

use Diveramkt\Uploads\Classes\Gregwar\Image\Image;

Image::open('in.gif')
    ->resize(500, 500)
    ->save('out.png', 'png');
