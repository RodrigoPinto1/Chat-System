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

        // Return messages and current user id for reliable frontend comparison
        return response()->json([
            'messages' => $messages,
            'currentUserId' => $user->id,
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
        $data['user_id'] = $request->user()->id;

        $message = Message::create($data);
        $message->load('user');

        // Optionally broadcast event here (if you wire MessageSent)
        // event(new \App\Events\MessageSent($message));

        return response()->json($message, 201);
    }
}
