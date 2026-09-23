<?php

use App\Enums\AnswerResponse;
use App\Enums\EquipmentStatus;
use App\Models\Answer;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\Family;
use App\Models\OrderFabrication;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Section;
use Illuminate\Support\Carbon;

it('relates Project to OrderFabrication and Equipment', function () {
    $project = Project::factory()->create();
    $orderFabrication = OrderFabrication::factory()->for($project)->create();
    $equipment = Equipment::factory()->create([
        'project_id' => $project->id,
        'order_fabrication_id' => $orderFabrication->id,
    ]);

    expect($project->orderFabrications)->toHaveCount(1)
        ->and($project->orderFabrications->first()->is($orderFabrication))->toBeTrue()
        ->and($project->equipment)->toHaveCount(1)
        ->and($project->equipment->first()->is($equipment))->toBeTrue();
});

it('relates OrderFabrication back to its Project and Equipment', function () {
    $orderFabrication = OrderFabrication::factory()->create();
    $equipment = Equipment::factory()->create([
        'project_id' => $orderFabrication->project_id,
        'order_fabrication_id' => $orderFabrication->id,
    ]);

    expect($orderFabrication->project)->not->toBeNull()
        ->and($orderFabrication->project->is($orderFabrication->project))->toBeTrue()
        ->and($orderFabrication->equipment->first()->is($equipment))->toBeTrue();
});

it('casts Equipment status and checked_at correctly', function () {
    $equipment = Equipment::factory()->create([
        'status' => EquipmentStatus::Ok,
        'checked_at' => now(),
    ]);

    expect($equipment->status)->toBe(EquipmentStatus::Ok)
        ->and($equipment->checked_at)->toBeInstanceOf(Carbon::class);
});

it('relates Equipment to Answers, Defects and Photos', function () {
    $equipment = Equipment::factory()->create();
    $answer = Answer::factory()->create(['equipment_id' => $equipment->id]);
    $defect = Defect::factory()->create([
        'equipment_id' => $equipment->id,
        'answer_id' => $answer->id,
    ]);
    $photo = Photo::factory()->create(['equipment_id' => $equipment->id]);

    expect($equipment->answers->first()->is($answer))->toBeTrue()
        ->and($equipment->defects->first()->is($defect))->toBeTrue()
        ->and($equipment->photos->first()->is($photo))->toBeTrue()
        ->and($answer->equipment->is($equipment))->toBeTrue()
        ->and($defect->equipment->is($equipment))->toBeTrue()
        ->and($photo->equipment->is($equipment))->toBeTrue();
});

it('orders Section questions by the order column', function () {
    $section = Section::factory()->create();
    Question::factory()->create(['section_id' => $section->id, 'order' => 2, 'text' => 'Second']);
    Question::factory()->create(['section_id' => $section->id, 'order' => 1, 'text' => 'First']);

    expect($section->questions->pluck('text')->all())->toBe(['First', 'Second']);
});

it('relates Question to Section and Answers, and casts Answer response', function () {
    $question = Question::factory()->create();
    $answer = Answer::factory()->create([
        'question_id' => $question->id,
        'response' => AnswerResponse::Defect,
    ]);

    expect($question->section)->not->toBeNull()
        ->and($question->answers->first()->is($answer))->toBeTrue()
        ->and($answer->question->is($question))->toBeTrue()
        ->and($answer->response)->toBe(AnswerResponse::Defect);
});

it('relates Defect to its originating Answer, nullable', function () {
    $defectWithAnswer = Defect::factory()->create(['answer_id' => Answer::factory()->create()->id]);
    $defectWithoutAnswer = Defect::factory()->create(['answer_id' => null]);

    expect($defectWithAnswer->answer)->not->toBeNull()
        ->and($defectWithoutAnswer->answer)->toBeNull();
});

it('relates Project and Section many-to-many, a project only using a subset of the available sections', function () {
    $project = Project::factory()->create();
    $usedSection = Section::factory()->create();
    $unusedSection = Section::factory()->create();

    $project->sections()->attach($usedSection);

    expect($project->sections)->toHaveCount(1)
        ->and($project->sections->first()->is($usedSection))->toBeTrue()
        ->and($usedSection->projects->first()->is($project))->toBeTrue()
        ->and($unusedSection->projects)->toHaveCount(0);
});

it('relates Project to a single Family', function () {
    $family = Family::factory()->create(['name' => 'DB2']);
    $project = Project::factory()->create(['family_id' => $family->id]);

    expect($project->family->is($family))->toBeTrue()
        ->and($family->projects->first()->is($project))->toBeTrue();
});

it('preserves the order Sections were attached to a Project in, via the pivot order column', function () {
    $project = Project::factory()->create();
    $sectionA = Section::factory()->create(['name' => 'AH17DX2']);
    $sectionB = Section::factory()->create(['name' => 'USB']);

    $project->sections()->attach($sectionA->id, ['order' => 1]);
    $project->sections()->attach($sectionB->id, ['order' => 0]);

    expect($project->sections->pluck('name')->all())->toBe(['USB', 'AH17DX2']);
});

it('links a Question to its QuestionCategory, and the category to its questions', function () {
    $category = QuestionCategory::factory()->create();
    $question = Question::factory()->create(['question_category_id' => $category->id]);

    expect($question->category->is($category))->toBeTrue()
        ->and($category->questions->pluck('id')->all())->toBe([$question->id]);
});
