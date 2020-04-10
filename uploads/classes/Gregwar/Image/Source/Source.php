<?php

namespace Diveramkt\Uploads\Classes\Gregwar\Image\Source;

// include 'plugins/diveramkt/uploads/classes/Gregwar/Image/Source/Source.php';

/**
 * An Image source.
 */
class Source
{
    /**
     * Guess the type of the image.
     */
    public function guessType()
    {
        return 'jpeg';
    }

    /**
     * Is this image correct ?
     */
    public function correct()
    {
        return true;
    }

    /**
     * Returns information about images, these informations should
     * change only if the original image changed.
     */
    public function getInfos()
    {
        return;
    }
}
