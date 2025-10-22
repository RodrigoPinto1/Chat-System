<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('room member can send a text message', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $room = Room::create(['name' => 'R', 'reference' => 'r-1']);
    // attach user as member
    $room->users()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $payload = [
        'content' => 'Hello from test',
        'room_id' => $room->id,
    ];

    $res = $this->postJson('/messages', $payload);
    $res->assertStatus(201);
    $res->assertJsonFragment(['content' => 'Hello from test']);

    // content is encrypted in the model before saving; assert row exists and
    // verify decrypted content via the Message model accessor
    $this->assertDatabaseHas('messages', ['room_id' => $room->id]);
    $message = \App\Models\Message::where('room_id', $room->id)->first();
    $this->assertNotNull($message);
    $this->assertEquals('Hello from test', $message->content);
});
