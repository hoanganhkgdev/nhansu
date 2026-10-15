<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officials', function (Blueprint $table) {
            $table->dropForeign(['directory_id']);
            $table->dropUnique(['directory_id', 'seq']);
            $table->dropIndex(['directory_id', 'is_active']);
            $table->dropColumn('directory_id');
            $table->unique('seq');
            $table->index('is_active');
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->dropForeign(['directory_id']);
            $table->dropUnique(['directory_id', 'slug']);
            $table->dropColumn('directory_id');
            $table->unique('slug');
        });

        Schema::table('lookups', function (Blueprint $table) {
            $table->dropForeign(['directory_id']);
            $table->dropIndex(['directory_id', 'type']);
            $table->dropColumn('directory_id');
            $table->index('type');
        });

        Schema::dropIfExists('directories');
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible: the directories table has been removed.');
    }
};
