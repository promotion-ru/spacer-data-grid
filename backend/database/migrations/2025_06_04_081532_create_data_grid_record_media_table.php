<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('data_grid_record_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_grid_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['data_grid_record_id', 'media_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grid_record_media');
    }
};
