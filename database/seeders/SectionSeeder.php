<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $qualitat = Section::factory()->create([
            'name' => 'QUALITAT',
            'description' => 'Comprovacions de qualitat de l\'equipament',
            'order' => 1,
        ]);

        // Les 3 categories inicials les crea la migració create_question_categories_table.
        $category = fn (string $name) => QuestionCategory::firstOrCreate(['name' => $name])->id;

        $questions = [
            ['text' => 'L\'acabat superficial és correcte?', 'category' => $category('Estètica')],
            ['text' => 'L\'etiquetatge és correcte i llegible?', 'category' => $category('Estètica')],
            ['text' => 'Les dimensions compleixen l\'especificació?', 'category' => $category('Funcional / Mecànica')],
            ['text' => 'Les connexions elèctriques estan ben fixades?', 'category' => $category('Electrònica')],
        ];

        foreach ($questions as $index => $question) {
            Question::factory()->create([
                'section_id' => $qualitat->id,
                'text' => $question['text'],
                'question_category_id' => $question['category'],
                'order' => $index + 1,
                'is_required' => true,
            ]);
        }
    }
}
