<?php

require_once '../autoload.php';

use Diveramkt\Uploads\Classes\Gregwar\Image\Image;

// Note: create a "cache" directory before try this

echo Image::open('img/test.png')
    ->sepia()
    ->setPrettyName('Some FanCY TestING!')
    ->jpeg();

echo "\n";
