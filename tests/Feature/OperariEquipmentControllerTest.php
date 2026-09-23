<?php

use App\Enums\AnswerResponse;
use App\Enums\EquipmentStatus;
use App\Models\Answer;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('lists equipment for an order fabrication, alongside the OF, its project, family and sections', function () {
    $orderFabrication = OrderFabrication::factory()->create();
    $section = Section::factory()->create(['name' => 'AH17DX2']);
    $orderFabrication->project->sections()->attach($section, ['order' => 0]);
    Equipment::factory()->count(3)->create([
        'project_id' => $orderFabrication->project_id,
        'order_fabrication_id' => $orderFabrication->id,
    ]);

    $response = $this->getJson("/operari/api/order-fabrications/{$orderFabrication->id}/equipment");

    $response->assertOk()->assertJsonCount(3, 'equipment');
    expect($response->json('order_fabrication.id'))->toBe($orderFabrication->id)
        ->and($response->json('order_fabrication.project.id'))->toBe($orderFabrication->project_id)
        ->and($response->json('order_fabrication.project.family.id'))->toBe($orderFabrication->project->family_id)
        ->and($response->json('order_fabrication.project.sections.0.name'))->toBe('AH17DX2');
});

it('shows equipment with sections, questions and existing answers', function () {
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create(['order' => 1]);
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'order' => 1]);
    Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => AnswerResponse::Yes,
    ]);

    $response = $this->getJson("/operari/api/equipment/{$equipment->id}");

    $response->assertOk();
    $payload = $response->json();

    expect($payload['equipment']['id'])->toBe($equipment->id)
        ->and($payload['sections'][0]['questions'][0]['answer']['response'])->toBe('yes');
});

it('returns each question with its category name and order, so the Operari groups them by the category catalog', function () {
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create();
    $equipment->project->sections()->attach($section);
    $category = QuestionCategory::factory()->create(['name' => 'Pantalles', 'order' => 7]);
    Question::factory()->create(['section_id' => $section->id, 'question_category_id' => $category->id]);

    $response = $this->getJson("/operari/api/equipment/{$equipment->id}");

    $response->assertOk()
        ->assertJsonPath('sections.0.questions.0.category.name', 'Pantalles')
        ->assertJsonPath('sections.0.questions.0.category.order', 7);
});

it('includes the answer id and its recorded defects, so a re-opened defect question shows what was already written', function () {
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create(['order' => 1]);
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'order' => 1]);
    $answer = Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => AnswerResponse::Defect,
    ]);
    $defect = Defect::factory()->create([
        'equipment_id' => $equipment->id,
        'answer_id' => $answer->id,
        'tipo' => 'visual',
        'observation' => 'Ratllada a la carcassa',
        'actions' => 'Substituir la peça',
    ]);

    $response = $this->getJson("/operari/api/equipment/{$equipment->id}");

    $response->assertOk();
    $questionPayload = $response->json('sections.0.questions.0');

    expect($questionPayload['answer']['id'])->toBe($answer->id)
        ->and($questionPayload['answer']['response'])->toBe('defect')
        ->and($questionPayload['answer']['defects'])->toHaveCount(1)
        ->and($questionPayload['answer']['defects'][0]['id'])->toBe($defect->id)
        ->and($questionPayload['answer']['defects'][0]['observation'])->toBe('Ratllada a la carcassa')
        ->and($questionPayload['answer']['defects'][0]['actions'])->toBe('Substituir la peça');
});

it('includes the responsible production operator\'s name alongside a recorded defect', function () {
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create(['order' => 1]);
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'order' => 1]);
    $answer = Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => AnswerResponse::Defect,
    ]);
    $operator = User::factory()->operariProduccio()->create(['name' => 'Joan']);
    Defect::factory()->create([
        'equipment_id' => $equipment->id,
        'answer_id' => $answer->id,
        'responsibility' => 'produccio',
        'responsible_user_id' => $operator->id,
    ]);

    $response = $this->getJson("/operari/api/equipment/{$equipment->id}");

    $response->assertOk();
    $defectPayload = $response->json('sections.0.questions.0.answer.defects.0');

    expect($defectPayload['responsible_user']['name'])->toBe('Joan');
});

it('only shows sections assigned to the equipment\'s project, not every section in the system', function () {
    $equipment = Equipment::factory()->create();
    $assignedSection = Section::factory()->create(['name' => 'ASSIGNED']);
    $otherSection = Section::factory()->create(['name' => 'NOT_ASSIGNED']);
    $equipment->project->sections()->attach($assignedSection);

    $response = $this->getJson("/operari/api/equipment/{$equipment->id}");

    $sectionNames = collect($response->json('sections'))->pluck('name');

    expect($sectionNames)->toContain('ASSIGNED')
        ->not->toContain('NOT_ASSIGNED');
});

it('saves equipment observations progressively', function () {
    $equipment = Equipment::factory()->create();

    $response = $this->patchJson("/operari/api/equipment/{$equipment->id}", [
        'observations' => 'Tot correcte, sense incidències.',
    ]);

    $response->assertOk();
    expect($equipment->fresh()->observations)->toBe('Tot correcte, sense incidències.');
});

it('finalizes equipment as ok when there are no defects and no observations', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create(['observations' => null]);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", [
        'photos' => [UploadedFile::fake()->image('photo1.jpg')],
    ]);

    $response->assertOk();
    $equipment->refresh();
    expect($equipment->status)->toBe(EquipmentStatus::Ok)
        ->and($equipment->checked_at)->not->toBeNull()
        ->and($equipment->photos)->toHaveCount(1);
    Storage::disk('photos')->assertExists($equipment->photos->first()->path);
});

it('finalizes equipment as ok-with-defects when it has defects, even with photos', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();
    Defect::factory()->create(['equipment_id' => $equipment->id]);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects);
});

it('finalizes equipment as ok when it has observations but no defects, since observations no longer affect status', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create(['observations' => 'Detall a revisar en propera visita.']);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->status)->toBe(EquipmentStatus::Ok);
});

it('refuses to finalize when a required question has not been answered', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create();
    $equipment->project->sections()->attach($section);
    Question::factory()->create(['section_id' => $section->id, 'is_required' => true]);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertUnprocessable();
    expect($equipment->fresh()->checked_at)->toBeNull();
});

it('refuses to finalize when a question is answered as defect but has no recorded defect', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create();
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'is_required' => true]);
    Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => 'defect',
    ]);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertUnprocessable();
    expect($equipment->fresh()->checked_at)->toBeNull();
});

it('finalizes successfully once every required question has an answer', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create();
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'is_required' => true]);
    Answer::factory()->create(['equipment_id' => $equipment->id, 'question_id' => $question->id, 'response' => 'yes']);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->checked_at)->not->toBeNull();
});

it('does not block finalizing on unanswered optional (non-required) questions', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create();
    $equipment->project->sections()->attach($section);
    Question::factory()->create(['section_id' => $section->id, 'is_required' => false]);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->checked_at)->not->toBeNull();
});

it('allows finishing without any photos, since they are optional', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->checked_at)->not->toBeNull();
});
