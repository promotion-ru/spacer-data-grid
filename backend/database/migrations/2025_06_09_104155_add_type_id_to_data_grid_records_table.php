<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('data_grid_records', function (Blueprint $table) {
            $table->foreignId('type_id')
                ->nullable()
                ->after('operation_type_id')
                ->constrained('data_grid_types')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('data_grid_records', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn('type_id');
        });
    }
};
