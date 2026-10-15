<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('lookup_official');

        Schema::table('officials', function (Blueprint $table) {
            $table->dropForeign(['specialize_id']);
            $table->dropForeign(['politic_id']);
            $table->dropForeign(['language_id']);
            $table->dropColumn(['specialize_id', 'politic_id', 'language_id']);
        });

        Schema::dropIfExists('lookups');
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible: the lookups tables have been removed.');
    }
};
