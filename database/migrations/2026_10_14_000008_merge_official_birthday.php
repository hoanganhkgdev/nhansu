<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officials', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('dharma_name');
        });

        // Rows with an incomplete or impossible date keep a NULL birthday.
        DB::table('officials')
            ->whereNotNull('birth_day')->whereNotNull('birth_month')->whereNotNull('birth_year')
            ->where('birth_year', '>', 0)
            ->update(['birthday' => DB::raw("STR_TO_DATE(CONCAT(birth_year, '-', birth_month, '-', birth_day), '%Y-%m-%d')")]);

        Schema::table('officials', function (Blueprint $table) {
            $table->dropIndex(['birth_month', 'birth_day']);
            $table->dropColumn(['birth_day', 'birth_month', 'birth_year']);
            $table->index('birthday');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible: birth_day/month/year were merged into birthday.');
    }
};
