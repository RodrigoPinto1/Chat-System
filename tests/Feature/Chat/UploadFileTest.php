<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('room member can upload a file as message', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $room = Room::create(['name' => 'R2', 'reference' => 'r-2']);
    $room->users()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $file = UploadedFile::fake()->image('photo.jpg');

    $res = $this->postJson('/messages/file', [
        'file' => $file,
        'room_id' => $room->id,
    ]);

    $res->assertStatus(200);
    $res->assertJsonStructure(['id', 'user', 'content', 'type', 'created_at']);

    // Ensure file was stored
    Storage::disk('public')->assertExists('chat_uploads/' . $file->hashName());
});
