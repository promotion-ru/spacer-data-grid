<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_grids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('image_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);

            $table->foreign('image_id')
                ->references('id')
                ->on('media')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grids');
    }
};
