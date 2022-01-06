<?php namespace Diveramkt\Uploads\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateDiveramktUploadsInformacoes extends Migration
{
    public function up()
    {
        Schema::dropIfExists('diveramkt_uploads_informacoes');
        // Schema::create('diveramkt_uploads_informacoes', function($table)
        // {
        //     $table->engine = 'InnoDB';
        //     $table->increments('id')->unsigned();
        //     $table->string('mes_tinypng', 255);
        //     $table->string('count_tinypng', 255);
        // });
    }
    
    public function down()
    {
        Schema::dropIfExists('diveramkt_uploads_informacoes');
    }
}