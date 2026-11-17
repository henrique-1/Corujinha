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
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('normalized_name', 100)->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $defaults = [
            ['name' => '4º Ano', 'normalized_name' => '4ano'],
            ['name' => '5º Ano', 'normalized_name' => '5ano'],
            ['name' => '6º Ano', 'normalized_name' => '6ano'],
            ['name' => '7º Ano', 'normalized_name' => '7ano'],
            ['name' => '8º Ano', 'normalized_name' => '8ano'],
            ['name' => '9º Ano', 'normalized_name' => '9ano'],
            ['name' => '1º Ano EM', 'normalized_name' => '1anoem'],
            ['name' => '2º Ano EM', 'normalized_name' => '2anoem'],
            ['name' => '3º Ano EM', 'normalized_name' => '3anoem'],
        ];

        $now = now();
        foreach ($defaults as $item) {
            DB::table('grade_levels')->updateOrInsert(
                ['normalized_name' => $item['normalized_name']],
                [
                    'name' => $item['name'],
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
