<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_grid_record_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('data_grid_id')->constrained()->onDelete('cascade');
            $table->foreignId('data_grid_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // 'record_created', 'record_updated', 'record_deleted', 'attachment_added', 'attachment_removed', 'type_created', 'type_deleted'
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['data_grid_id', 'created_at']);
            $table->index(['data_grid_record_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_grid_record_logs');
    }
};
