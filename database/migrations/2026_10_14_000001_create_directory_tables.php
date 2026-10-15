<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per former NukeViet "official" module clone (nhan-su-cac-tinh-thanh).
        Schema::create('directories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->unsignedSmallInteger('per_page')->default(25);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parts')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('office')->default('');
            $table->string('address')->default('');
            $table->string('phone', 20)->default('');
            $table->string('fax', 20)->default('');
            $table->string('website', 100)->default('');
            $table->string('email')->default('');
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['directory_id', 'slug']);
        });

        // jobtitle / specialize / politic / language lists share one table.
        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directory_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title');
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['directory_id', 'type']);
        });

        Schema::create('officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->nullable()->constrained('parts')->nullOnDelete();
            // Per-directory number used in public URLs; imported rows keep their NukeViet id so old links/QR codes still resolve.
            $table->unsignedInteger('seq');
            $table->string('lastname');
            $table->string('firstname', 50);
            $table->string('dharma_name')->default('');
            // Dates are often partial (year only), so day/month/year are stored separately.
            $table->unsignedTinyInteger('birth_day')->nullable();
            $table->unsignedTinyInteger('birth_month')->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->unsignedTinyInteger('gender')->default(1); // 1 = male, 0 = female
            $table->string('image')->default('');
            $table->string('email', 100)->default('');
            $table->string('phone', 20)->default('');
            $table->string('member_code', 50)->default('');
            $table->string('id_number', 50)->default('');
            $table->date('id_issued_date')->nullable();
            $table->string('id_issued_place')->default('');
            $table->string('position')->default('');
            $table->string('resident')->default('');
            $table->string('hometown')->default('');
            $table->string('practicing_place')->default('');
            $table->string('precept_rank', 50)->default('');
            $table->string('clergy_rank', 100)->default('');
            $table->string('education')->default('');
            $table->foreignId('specialize_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('politic_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->unsignedMediumInteger('sort')->default(0);
            $table->boolean('is_priority')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['directory_id', 'seq']);
            $table->index(['directory_id', 'is_active']);
            $table->index('part_id');
            $table->index(['birth_month', 'birth_day']);
        });

        Schema::create('lookup_official', function (Blueprint $table) {
            $table->foreignId('official_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lookup_id')->constrained()->cascadeOnDelete();
            $table->primary(['official_id', 'lookup_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_official');
        Schema::dropIfExists('officials');
        Schema::dropIfExists('lookups');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('directories');
    }
};
