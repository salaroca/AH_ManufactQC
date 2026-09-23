<?php

use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionTemplate;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

describe('banc de preguntes (CRUD)', function () {
    it('creates a bank question with its category', function () {
        $category = QuestionCategory::factory()->create(['name' => 'Soldadura']);

        $response = $this->postJson('/api/question-templates', [
            'text' => 'La soldadura és uniforme?',
            'question_category_id' => $category->id,
            'is_required' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('category.name', 'Soldadura')
            ->assertJsonPath('is_required', false)
            ->assertJsonPath('questions_count', 0);
    });

    it('requires a text and an existing category', function () {
        $this->postJson('/api/question-templates', ['question_category_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'question_category_id']);
    });

    it('lists bank questions grouped by category order, with how many sections use each one', function () {
        $second = QuestionCategory::factory()->create(['order' => 20]);
        $first = QuestionCategory::factory()->create(['order' => 10]);
        $late = QuestionTemplate::factory()->create(['question_category_id' => $second->id]);
        $early = QuestionTemplate::factory()->create(['question_category_id' => $first->id]);
        $early->copyToSection(Section::factory()->create(), 0);
        $early->copyToSection(Section::factory()->create(), 0);

        $response = $this->getJson('/api/question-templates');

        $response->assertOk();
        expect(collect($response->json())->pluck('id')->all())->toBe([$early->id, $late->id])
            ->and($response->json('0.questions_count'))->toBe(2);
    });

    it('updates only the bank question, leaving the copies already in sections untouched', function () {
        $template = QuestionTemplate::factory()->create(['text' => 'Text original?']);
        $copy = $template->copyToSection(Section::factory()->create(), 0);

        $this->putJson("/api/question-templates/{$template->id}", [
            'text' => 'Text nou?',
            'question_category_id' => $template->question_category_id,
        ])->assertOk()->assertJsonPath('text', 'Text nou?');

        expect($copy->fresh()->text)->toBe('Text original?');
    });

    it('deletes a bank question but keeps its copies in sections, only unlinking them', function () {
        $template = QuestionTemplate::factory()->create();
        $copy = $template->copyToSection(Section::factory()->create(), 0);

        $this->deleteJson("/api/question-templates/{$template->id}")->assertNoContent();

        expect(QuestionTemplate::find($template->id))->toBeNull()
            ->and($copy->fresh())->not->toBeNull()
            ->and($copy->fresh()->question_template_id)->toBeNull();
    });

    it('blocks deleting a category still used by a bank question', function () {
        $template = QuestionTemplate::factory()->create();

        $this->deleteJson("/api/question-categories/{$template->question_category_id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    });
});

describe('afegir preguntes del banc a una secció', function () {
    it('copies the chosen bank questions to the end of the section, keeping text, category and required flag', function () {
        $section = Section::factory()->create();
        Question::factory()->create(['section_id' => $section->id, 'order' => 0]);
        $a = QuestionTemplate::factory()->create(['text' => 'A?', 'is_required' => false]);
        $b = QuestionTemplate::factory()->create(['text' => 'B?']);

        $response = $this->postJson("/api/sections/{$section->id}/questions/from-templates", [
            'template_ids' => [$b->id, $a->id],
        ]);

        $response->assertOk()->assertJsonCount(3);
        $copies = $section->questions()->whereNotNull('question_template_id')->get();
        expect($copies->pluck('text')->all())->toBe(['B?', 'A?'])
            ->and($copies->pluck('order')->all())->toBe([1, 2])
            ->and($copies->firstWhere('text', 'A?')->is_required)->toBeFalse()
            ->and($copies->firstWhere('text', 'A?')->question_category_id)->toBe($a->question_category_id)
            ->and($response->json('2.category.id'))->toBe($a->question_category_id);
    });

    it('places copies after the highest existing order, even when existing orders have gaps or repeats', function () {
        $section = Section::factory()->create();
        Question::factory()->create(['section_id' => $section->id, 'order' => 1]);
        Question::factory()->create(['section_id' => $section->id, 'order' => 5]);
        $template = QuestionTemplate::factory()->create();

        $this->postJson("/api/sections/{$section->id}/questions/from-templates", ['template_ids' => [$template->id]])->assertOk();

        expect($section->questions()->where('question_template_id', $template->id)->value('order'))->toBe(6);
    });

    it('does not duplicate a bank question the section already has', function () {
        $section = Section::factory()->create();
        $template = QuestionTemplate::factory()->create();
        $template->copyToSection($section, 0);

        $this->postJson("/api/sections/{$section->id}/questions/from-templates", ['template_ids' => [$template->id]])
            ->assertOk()
            ->assertJsonCount(1);
    });

    it('lets the same bank question be added to several sections, each with its own independent copy', function () {
        $template = QuestionTemplate::factory()->create();
        $sectionA = Section::factory()->create(['name' => 'AH17DB2']);
        $sectionB = Section::factory()->create(['name' => 'AH17DX2']);

        $this->postJson("/api/sections/{$sectionA->id}/questions/from-templates", ['template_ids' => [$template->id]])->assertOk();
        $this->postJson("/api/sections/{$sectionB->id}/questions/from-templates", ['template_ids' => [$template->id]])->assertOk();

        expect(Question::where('question_template_id', $template->id)->pluck('section_id')->sort()->values()->all())
            ->toBe(collect([$sectionA->id, $sectionB->id])->sort()->values()->all());
    });

    it('rejects an empty selection or a bank question that does not exist', function () {
        $section = Section::factory()->create();

        $this->postJson("/api/sections/{$section->id}/questions/from-templates", ['template_ids' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('template_ids');
        $this->postJson("/api/sections/{$section->id}/questions/from-templates", ['template_ids' => [999999]])
            ->assertUnprocessable()->assertJsonValidationErrors('template_ids.0');
    });
});

describe('desar una pregunta de secció també al banc', function () {
    it('creates a bank question from a new section question when save_to_bank is checked', function () {
        $section = Section::factory()->create();
        $category = QuestionCategory::factory()->create();

        $this->postJson('/api/questions', [
            'section_id' => $section->id,
            'text' => 'Pregunta comuna?',
            'question_category_id' => $category->id,
            'is_required' => true,
            'save_to_bank' => true,
        ])->assertCreated()->assertJsonPath('question_template_id', fn ($id) => $id !== null);

        $template = QuestionTemplate::sole();
        expect($template->text)->toBe('Pregunta comuna?')
            ->and($template->question_category_id)->toBe($category->id)
            ->and(Question::sole()->question_template_id)->toBe($template->id);
    });

    it('does not touch the bank when save_to_bank is not checked', function () {
        $this->postJson('/api/questions', [
            'section_id' => Section::factory()->create()->id,
            'text' => 'Només d\'aquesta secció?',
            'question_category_id' => QuestionCategory::factory()->create()->id,
        ])->assertCreated();

        expect(QuestionTemplate::count())->toBe(0);
    });

    it('can send an existing section question to the bank when editing it, only once', function () {
        $question = Question::factory()->create();
        $payload = [
            'section_id' => $question->section_id,
            'text' => $question->text,
            'question_category_id' => $question->question_category_id,
            'save_to_bank' => true,
        ];

        $this->putJson("/api/questions/{$question->id}", $payload)->assertOk();
        $this->putJson("/api/questions/{$question->id}", $payload)->assertOk();

        expect(QuestionTemplate::count())->toBe(1)
            ->and($question->fresh()->question_template_id)->toBe(QuestionTemplate::sole()->id);
    });
});

it('forbids an operari from using the question bank', function () {
    $this->actingAs(User::factory()->operari()->create());

    $this->getJson('/api/question-templates')->assertForbidden();
});
