<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    // List all rooms
    public function index()
    {
        return response()->json(Room::all());
    }

    // Create a new room
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'reference' => 'required|string|max:255|unique:rooms,reference',
            'avatar' => 'nullable|string',
        ]);
        $room = Room::create($data);
        // Optionally add creator as member
        $room->users()->attach(Auth::id(), ['role' => 'owner', 'joined_at' => now()]);
        return response()->json($room, 201);
    }

    // Join a room
    public function join(Request $request, Room $room)
    {
        $user = $request->user();
        $room->users()->syncWithoutDetaching([$user->id => ['role' => 'member', 'joined_at' => now()]]);
        return response()->json(['joined' => true]);
    }

    // Show a room
    public function show(Room $room)
    {
        return response()->json($room);
    }
}
