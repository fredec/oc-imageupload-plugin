<?php
require_once '../autoload.php';

use Diveramkt\Uploads\Classes\Gregwar\Image\Image;

?>
<img src="<?php echo Image::open('img/test.png')->resize('50%')->inline() ?>" />
