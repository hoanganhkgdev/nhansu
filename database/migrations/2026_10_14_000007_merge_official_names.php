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
            $table->string('fullname')->default('')->after('seq');
        });

        DB::table('officials')->update(['fullname' => DB::raw("TRIM(CONCAT(lastname, ' ', firstname))")]);

        Schema::table('officials', function (Blueprint $table) {
            $table->dropColumn(['lastname', 'firstname']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible: lastname/firstname were merged into fullname.');
    }
};
