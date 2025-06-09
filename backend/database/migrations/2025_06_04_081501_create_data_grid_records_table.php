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
            $table->string('name');
            $table->date('date')->comment('Дата операции');
            $table->tinyInteger('operation_type_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('amount')->nullable()->comment('Сумма операции');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grid_records');
    }
};
