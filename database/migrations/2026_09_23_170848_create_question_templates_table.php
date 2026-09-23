<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_templates', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->foreignId('question_category_id')->constrained()->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        // La pregunta del banc de la qual s'ha copiat (si n'hi ha). La còpia és independent:
        // si s'elimina la pregunta del banc, la de la secció es manté i només es perd l'enllaç.
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('question_template_id')->nullable()->after('section_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_template_id');
        });

        Schema::dropIfExists('question_templates');
    }
};
