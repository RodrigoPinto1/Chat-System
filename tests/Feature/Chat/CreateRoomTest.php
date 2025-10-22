<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can create a room', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $payload = [
        'name' => 'Sala de Teste',
        'reference' => 'sala-teste-1',
        'avatar' => '',
    ];

    $res = $this->postJson('/rooms', $payload);
    $res->assertStatus(201);
    $res->assertJsonFragment(['name' => 'Sala de Teste', 'reference' => 'sala-teste-1']);

    // ensure room exists in DB and user attached as owner
    $this->assertDatabaseHas('rooms', ['reference' => 'sala-teste-1']);
});
