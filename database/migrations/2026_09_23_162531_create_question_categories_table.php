<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valors de l'antic enum App\Enums\QuestionCategory → nom de la categoria al nou catàleg.
     * L'ordre de l'array és l'ordre inicial de les categories.
     */
    private const INITIAL_CATEGORIES = [
        'estetica' => 'Estètica',
        'funcional_mecanica' => 'Funcional / Mecànica',
        'electronica' => 'Electrònica',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        $categoryIds = [];
        foreach (array_values(self::INITIAL_CATEGORIES) as $index => $name) {
            $categoryIds[$name] = DB::table('question_categories')->insertGetId([
                'name' => $name,
                'order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('question_category_id')->nullable()->after('text')->constrained()->restrictOnDelete();
        });

        foreach (self::INITIAL_CATEGORIES as $value => $name) {
            DB::table('questions')->where('category', $value)->update(['question_category_id' => $categoryIds[$name]]);
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('question_category_id')->nullable(false)->change();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('text');
        });

        $values = array_flip(self::INITIAL_CATEGORIES);
        foreach (DB::table('question_categories')->get() as $category) {
            DB::table('questions')
                ->where('question_category_id', $category->id)
                ->update(['category' => $values[$category->name] ?? 'estetica']);
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_category_id');
        });

        Schema::dropIfExists('question_categories');
    }
};
