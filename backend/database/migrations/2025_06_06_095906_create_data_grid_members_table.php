<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_grid_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('data_grid_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->json('permissions'); // ['view', 'create', 'update', 'delete']
            $table->timestamps();

            $table->unique(['data_grid_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grid_members');
    }
};
