<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('data_grid_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('data_grid_id')->constrained()->onDelete('cascade');
            $table->string('action');                                                              // 'created', 'updated', 'deleted', 'member_added', 'member_removed', 'invitation_sent', etc.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();        // кто выполнил действие
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete(); // кто выполнил действие
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable(); // дополнительная информация
            $table->timestamps();

            $table->index(['data_grid_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grid_logs');
    }
};
