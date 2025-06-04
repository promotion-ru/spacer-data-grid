<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_grid_records', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('data_grid_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->tinyInteger('operation_type_id')->nullable();
            $table->integer('type_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('amount')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grid_records');
    }
};
