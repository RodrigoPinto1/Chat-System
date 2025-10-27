<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\UserInvitedToRoom;
use Illuminate\Support\Facades\Schema;

class RoomController extends Controller
{
    // List all rooms
    public function index(Request $request)
    {
        $user = $request->user();
        // Show rooms where the user is a member (any role)
        // Include a messages_count so the frontend can show message counts without extra requests
        $rooms = Room::whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->withCount('messages')->get();

        // Compute unread_count per room for the current user based on pivot.last_read_at
        // If the pivot column doesn't exist yet (migration not run), avoid querying it and
        // just return rooms with unread_count = 0 to prevent SQL errors.
        $hasLastRead = Schema::hasColumn('room_user', 'last_read_at');

        if (!$hasLastRead) {
            // Migration not applied yet — return rooms without per-user unread counts
            $roomsArray = $rooms->map(function ($room) use ($user) {
                // Determine if current user is owner based on pivot role if present
                $pivot = $room->users()->where('user_id', $user->id)->first()?->pivot ?? null;
                $isOwner = $pivot && isset($pivot->role) ? ($pivot->role === 'owner') : false;
                $membersCount = $room->users()->count();
                $isPrivate = $membersCount === 2;
                return array_merge($room->toArray(), ['unread_count' => 0, 'is_owner' => $isOwner, 'members_count' => $membersCount, 'is_private' => $isPrivate]);
            });
            return response()->json($roomsArray->values());
        }

        $roomsWithUnread = $rooms->map(function ($room) use ($user) {
            // Find pivot info for this user
            $pivot = $room->users()->where('user_id', $user->id)->first()?->pivot ?? null;
            $isOwner = $pivot && isset($pivot->role) ? ($pivot->role === 'owner') : false;
            $lastRead = $pivot && $pivot->last_read_at ? $pivot->last_read_at : $pivot?->joined_at;

            if ($lastRead) {
                $unread = $room->messages()->where('created_at', '>', $lastRead)->count();
            } else {
                // if no lastRead and no joined_at, consider all messages as unread
                $unread = $room->messages()->count();
            }

            $membersCount = $room->users()->count();
            $isPrivate = $membersCount === 2;
            return array_merge($room->toArray(), ['unread_count' => $unread, 'is_owner' => $isOwner, 'members_count' => $membersCount, 'is_private' => $isPrivate]);
        });

        return response()->json($roomsWithUnread->values());
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

    // Mark the room as read for the current user by updating pivot.last_read_at
    public function markRead(Request $request, Room $room)
    {
        $user = $request->user();
        // Ensure user is a member
        $isMember = $room->users()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            return response()->json(['error' => 'Você não é membro desta sala.'], 403);
        }

        // Update pivot
        $room->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json(['marked' => true]);
    }
}
