<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Room;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collections\Collection;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        // Create additional users
        $users = User::factory(5)->create();

        // Create a room and attach users
        $room = Room::factory()->create([
            'name' => 'General',
            'reference' => 'general',
        ]);

        // attach admin and users
        $room->users()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);
        foreach ($users as $u) {
            $room->users()->attach($u->id, ['role' => 'member', 'joined_at' => now()]);
        }

        // Create sample messages
        Message::factory()->count(10)->make()->each(function ($m) use ($room, $admin, $users) {
            /** @var \App\Models\Message $m */
            $m->user_id = fake()->randomElement(array_merge([$admin->id], $users->pluck('id')->all()));
            $m->room_id = $room->id;
            $m->save();
        });
    }
}
