<?php

use App\Models\Answer;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('searches order fabrications by partial number, with the project, its family, its sections and the equipment count loaded', function () {
    $project = Project::factory()->create(['number' => '1400C0137.00']);
    $section = Section::factory()->create(['name' => 'AH17DX2']);
    $project->sections()->attach($section, ['order' => 0]);
    $orderFabrication = OrderFabrication::factory()->for($project)->create(['number' => '2026/01/0000123']);
    Equipment::factory()->for($project)->for($orderFabrication)->count(4)->create();
    OrderFabrication::factory()->create(['number' => '2026/01/0000999']);

    $response = $this->getJson('/operari/api/order-fabrications?q=0000123');

    $response->assertOk()->assertJsonCount(1);
    expect($response->json('0.project.number'))->toBe('1400C0137.00')
        ->and($response->json('0.project.family.name'))->toBe($project->family->name)
        ->and($response->json('0.project.sections.0.name'))->toBe('AH17DX2')
        ->and($response->json('0.equipment_count'))->toBe(4);
});

it('lets an admin access the operari order fabrication search directly', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson('/operari/api/order-fabrications');

    $response->assertOk();
});

it('lists order fabrications without a search query, with how many equipment are still pending review', function () {
    $orderFabrication = OrderFabrication::factory()->create();
    Equipment::factory()->for($orderFabrication->project)->for($orderFabrication)->count(2)->create(['checked_at' => now()]);
    Equipment::factory()->for($orderFabrication->project)->for($orderFabrication)->count(3)->create(['checked_at' => null]);

    $response = $this->getJson('/operari/api/order-fabrications');

    $response->assertOk()->assertJsonCount(1);
    expect($response->json('0.equipment_count'))->toBe(5)
        ->and($response->json('0.pending_equipment_count'))->toBe(3);
});

it('reports zero pending equipment when every equipment of the OF has been finished', function () {
    $orderFabrication = OrderFabrication::factory()->create();
    Equipment::factory()->for($orderFabrication->project)->for($orderFabrication)->count(2)->create(['checked_at' => now()]);

    $response = $this->getJson('/operari/api/order-fabrications');

    expect($response->json('0.pending_equipment_count'))->toBe(0);
});

it('lists order fabrications with pending equipment first, then the rest, each group by number', function () {
    $finished = OrderFabrication::factory()->create(['number' => '2026/01/0000001']);
    Equipment::factory()->for($finished->project)->for($finished)->create(['checked_at' => now()]);
    $empty = OrderFabrication::factory()->create(['number' => '2026/01/0000002']);
    $pendingB = OrderFabrication::factory()->create(['number' => '2026/01/0000004']);
    Equipment::factory()->for($pendingB->project)->for($pendingB)->create(['checked_at' => null]);
    $pendingA = OrderFabrication::factory()->create(['number' => '2026/01/0000003']);
    Equipment::factory()->for($pendingA->project)->for($pendingA)->count(5)->create(['checked_at' => null]);

    $response = $this->getJson('/operari/api/order-fabrications');

    expect(collect($response->json())->pluck('id')->all())
        ->toBe([$pendingA->id, $pendingB->id, $finished->id, $empty->id]);
});

it('counts an equipment as started once it has any answer or has been finished, so an untouched OF can be shown as not started', function () {
    $untouched = OrderFabrication::factory()->create(['number' => '2026/01/0000010']);
    Equipment::factory()->for($untouched->project)->for($untouched)->count(2)->create(['checked_at' => null]);

    $started = OrderFabrication::factory()->create(['number' => '2026/01/0000011']);
    $answered = Equipment::factory()->for($started->project)->for($started)->create(['checked_at' => null]);
    Answer::factory()->create(['equipment_id' => $answered->id]);
    Equipment::factory()->for($started->project)->for($started)->create(['checked_at' => now()]);
    Equipment::factory()->for($started->project)->for($started)->create(['checked_at' => null]);

    $response = $this->getJson('/operari/api/order-fabrications');
    $byNumber = collect($response->json())->keyBy('number');

    expect($byNumber['2026/01/0000010']['started_equipment_count'])->toBe(0)
        ->and($byNumber['2026/01/0000011']['started_equipment_count'])->toBe(2);
});
