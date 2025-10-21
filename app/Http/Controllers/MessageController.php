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

        // Optionally broadcast event here (if you wire MessageSent)
        // event(new \App\Events\MessageSent($message));

        return response()->json($message, 201);
    }
}
