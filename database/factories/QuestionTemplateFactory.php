<?php

namespace Database\Factories;

use App\Models\QuestionCategory;
use App\Models\QuestionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionTemplate>
 */
class QuestionTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text' => $this->faker->sentence().'?',
            'question_category_id' => QuestionCategory::factory(),
            'is_required' => true,
        ];
    }
}
