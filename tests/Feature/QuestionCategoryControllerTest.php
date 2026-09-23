<?php

use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('ships the three initial categories created by the migration, in order', function () {
    $response = $this->getJson('/api/question-categories');

    $response->assertOk();
    expect(collect($response->json())->pluck('name')->all())
        ->toBe(['Estètica', 'Funcional / Mecànica', 'Electrònica']);
});

it('lists categories ordered by the order column, with their question count', function () {
    QuestionCategory::query()->delete();
    $second = QuestionCategory::factory()->create(['order' => 1]);
    $first = QuestionCategory::factory()->create(['order' => 0]);
    Question::factory()->count(2)->create(['question_category_id' => $first->id]);

    $response = $this->getJson('/api/question-categories');

    $response->assertOk();
    expect(collect($response->json())->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and($response->json('0.questions_count'))->toBe(2);
});

it('creates a category at the end of the list when no order is given', function () {
    $response = $this->postJson('/api/question-categories', ['name' => 'Soldadura']);

    $response->assertCreated()->assertJsonPath('name', 'Soldadura');
    expect(QuestionCategory::where('name', 'Soldadura')->value('order'))->toBe(QuestionCategory::max('order'));
});

it('rejects a duplicated category name', function () {
    QuestionCategory::factory()->create(['name' => 'Soldadura']);

    $this->postJson('/api/question-categories', ['name' => 'Soldadura'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('renames a category, allowing it to keep its own name', function () {
    $category = QuestionCategory::factory()->create(['name' => 'Soldadura']);

    $this->putJson("/api/question-categories/{$category->id}", ['name' => 'Soldadura'])->assertOk();
    $this->putJson("/api/question-categories/{$category->id}", ['name' => 'Soldadures'])
        ->assertOk()
        ->assertJsonPath('name', 'Soldadures');
});

it('deletes a category that no question uses', function () {
    $category = QuestionCategory::factory()->create();

    $this->deleteJson("/api/question-categories/{$category->id}")->assertNoContent();
    expect(QuestionCategory::find($category->id))->toBeNull();
});

it('blocks deleting a category still used by a question', function () {
    $category = QuestionCategory::factory()->create();
    Question::factory()->create(['question_category_id' => $category->id]);

    $this->deleteJson("/api/question-categories/{$category->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category');
    expect(QuestionCategory::find($category->id))->not->toBeNull();
});

it('reorders categories following the order of the ids received', function () {
    QuestionCategory::query()->delete();
    [$a, $b, $c] = QuestionCategory::factory()->count(3)->create()->all();

    $response = $this->postJson('/api/question-categories/reorder', ['category_ids' => [$c->id, $a->id, $b->id]]);

    $response->assertOk();
    expect(collect($response->json())->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('forbids an operari from managing categories', function () {
    $this->actingAs(User::factory()->operari()->create());

    $this->getJson('/api/question-categories')->assertForbidden();
});
