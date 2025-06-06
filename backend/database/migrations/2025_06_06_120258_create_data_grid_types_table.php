<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_grid_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('data_grid_id', 36)->nullable();
            $table->boolean('is_global')->default(false);
            $table->bigInteger('created_by')->unsigned();
            $table->timestamps();

            $table->foreign('data_grid_id')->references('id')->on('data_grids')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['data_grid_id', 'name']);
            $table->index(['is_global', 'name']);
            $table->unique(['name', 'data_grid_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grid_types');
    }
};
