<?php namespace Xitara\MediaConverter\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateMediaListsTable extends Migration
{
    public function up()
    {
        Schema::create('xitara_mediaconverter_media_lists', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('file_name', 255)->nullable();
            $table->string('disk_name', 255)->nullable();
            $table->string('content_type', 30)->nullable();
            $table->string('owner_class', 255)->nullable();
            $table->integer('owner_id')->nullable();
            $table->enum('status', ['pending', 'progress', 'completed'])->default('pending');
            $table->text('converted')->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xitara_mediaconverter_media_lists');
    }
}
