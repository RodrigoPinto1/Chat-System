<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\UserInvitedToRoom;

class RoomController extends Controller
{
    // List all rooms
    public function index(Request $request)
    {
        $user = $request->user();
        // Show rooms where the user is a member (any role)
        $rooms = Room::whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get();
        return response()->json($rooms);
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
        // Redirect to the main chat page and open the newly created room.
        // Use Inertia::location so we force a full browser redirect even when the request
        // was intercepted by Inertia/XHR on the client side.
        return \Inertia\Inertia::location('/chat?room=' . $room->id);
    }

    // Show create form (Inertia page)
    public function create()
    {
        return
            \Inertia\Inertia::render('rooms/Create');
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

    // Get all members of a room with their roles
    public function members(Room $room)
    {
        $members = $room->users()->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'role' => $user->pivot->role,
            ];
        });
        return response()->json($members);
    }

    // Invite a user to the room (admin/owner only)
    public function invite(Request $request, Room $room)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $user = $request->user();
        if (!$user->isAdminInRoom($room->id)) {
            return response()->json(['error' => 'Only admins/owners can invite.'], 403);
        }
        $inviteeId = $request->input('user_id');
        $room->users()->syncWithoutDetaching([$inviteeId => ['role' => 'member', 'joined_at' => now()]]);
        // Fire broadcast event
        event(new UserInvitedToRoom($room, $user, \App\Models\User::find($inviteeId)));
        return response()->json(['invited' => true]);
    }
}
