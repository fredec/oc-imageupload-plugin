<?php
// require_once("vendor/autoload.php");
      require_once("lib_tiny/Tinify/Exception.php");
      require_once("lib_tiny/Tinify/ResultMeta.php");
      require_once("lib_tiny/Tinify/Result.php");
      require_once("lib_tiny/Tinify/Source.php");
      require_once("lib_tiny/Tinify/Client.php");
      require_once("lib_tiny/Tinify.php");
      
class CI_Tinypng
{

    /**
     * Set Tiny API Key
     *
     * @return void
     */
    function __construct($api_key=false)
    {

      $api_key=CHAVE_TINYPNG;
      \Tinify\setKey($api_key);
    }

    function tinify_image($filepath) {
      $source = \Tinify\fromFile($filepath);
      $source->toFile($filepath);
    }
  }
