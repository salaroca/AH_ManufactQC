<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Family;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Section;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $qualitat = Section::where('name', 'QUALITAT')->first();
        $componentSections = $this->componentSections();
        $families = Family::all();

        Project::factory()
            ->count(3)
            ->when($families->isNotEmpty(), fn ($factory) => $factory->state(fn () => ['family_id' => $families->random()->id]))
            ->create()
            ->each(function (Project $project) use ($qualitat, $componentSections) {
                $selected = $componentSections->random(min(2, $componentSections->count()))->values();
                if ($qualitat) {
                    $selected->push($qualitat);
                }

                $selected->each(function (Section $section, int $index) use ($project) {
                    $project->sections()->attach($section->id, ['order' => $index]);
                });

                OrderFabrication::factory()
                    ->count(2)
                    ->for($project)
                    ->create()
                    ->each(function (OrderFabrication $orderFabrication) use ($project) {
                        Equipment::factory()
                            ->count(3)
                            ->create([
                                'project_id' => $project->id,
                                'order_fabrication_id' => $orderFabrication->id,
                            ]);
                    });
            });
    }

    /**
     * Real component-code sections the user named, each a lightweight section
     * with a couple of categorized questions (demo depth, not exhaustive).
     */
    private function componentSections()
    {
        $names = ['AH17DX2', 'AH22DX2', 'TS', 'USB'];

        return collect($names)->map(function (string $name) {
            $section = Section::firstOrCreate(['name' => $name], ['description' => null, 'order' => 10]);

            if ($section->questions()->count() === 0) {
                Question::factory()->create([
                    'section_id' => $section->id,
                    'text' => "L'acabat de {$name} és correcte?",
                    'question_category_id' => QuestionCategory::firstOrCreate(['name' => 'Estètica'])->id,
                    'order' => 1,
                ]);
                Question::factory()->create([
                    'section_id' => $section->id,
                    'text' => "El muntatge de {$name} és correcte?",
                    'question_category_id' => QuestionCategory::firstOrCreate(['name' => 'Funcional / Mecànica'])->id,
                    'order' => 2,
                ]);
            }

            return $section;
        });
    }
}
