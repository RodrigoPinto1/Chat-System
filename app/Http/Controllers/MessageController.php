<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // Fetch messages for the current user in a specific room
    public function index(Request $request)
    {
        $roomId = $request->query('room_id');
        $user = $request->user();
        $messages = Message::where('room_id', $roomId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Return messages, current user id, and admin status for reliable frontend comparison
        $isAdmin = $user && $roomId ? $user->isAdminInRoom($roomId) : false;
        // Always return a clean array of messages with type
        $messagesArr = $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'user' => [
                    'id' => $msg->user->id ?? null,
                    'name' => $msg->user->name ?? null,
                    'avatar' => $msg->user->avatar ?? null,
                ],
                'content' => $msg->content,
                'type' => $msg->type ?? null,
                'meta' => $msg->meta ?? null,
                'created_at' => $msg->created_at,
            ];
        });
        return response()->json([
            'messages' => $messagesArr,
            'currentUserId' => $user->id,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'content' => 'required|string|max:2000',
            'room_id' => 'nullable|exists:rooms,id',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        // Always set user_id to the authenticated user
        $user = $request->user();
        $data['user_id'] = $user->id;

        // Check if user is a member of the room
        $roomId = $data['room_id'] ?? null;
        if ($roomId) {
            $isMember = $user->rooms()->where('rooms.id', $roomId)->exists();
            if (!$isMember) {
                return response()->json(['error' => 'Você não é membro desta sala.'], 403);
            }
        }

        $message = Message::create($data);
        $message->load('user');

        // When a user sends a message to a room, mark that room as read for them
        // so their own sent message doesn't count as unread.
        if (!empty($roomId)) {
            try {
                $message->room->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);
            } catch (\Exception $e) {
                // If pivot column doesn't exist or other issue, don't block message sending.
                // We'll handle schema migrations separately.
            }
        }
        // Optionally broadcast event here (if you wire MessageSent)
        // event(new \App\Events\MessageSent($message));

        return response()->json($message, 201);
    }
}
