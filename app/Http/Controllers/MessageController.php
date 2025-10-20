<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'content' => 'required|string|max:2000',
            'room_id' => 'nullable|exists:rooms,id',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        $message = $request->user()->messages()->create($data);
        $message->load('user');

        // Optionally broadcast event here (if you wire MessageSent)
        // event(new \App\Events\MessageSent($message));

        return response()->json($message, 201);
    }
}
