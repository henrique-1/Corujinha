<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teaching_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('teaching_area_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teaching_area_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'teaching_area_id']);
        });

        $now = now();
        $defaultAreas = [
            'Língua Portuguesa',
            'História',
            'Geografia',
            'Matemática',
            'Filosofia',
            'Sociologia',
            'Química',
            'Física',
            'Biologia',
            'Inglês',
        ];

        DB::table('teaching_areas')->insert(
            array_map(fn (string $name) => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ], $defaultAreas)
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_area_user');
        Schema::dropIfExists('teaching_areas');
    }
};
