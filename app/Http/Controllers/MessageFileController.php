<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Message;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;

class MessageFileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'room_id' => 'required|exists:rooms,id',
        ]);
        $user = $request->user();
        $room = Room::findOrFail($request->room_id);
        $file = $request->file('file');
        $path = $file->store('chat_uploads', 'public');
        $url = config('app.url') . '/storage/' . ltrim($path, '/');
        $meta = null;
        if ($request->has('meta')) {
            $meta = $request->input('meta');
        }
        $message = Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'content' => $url,
            'type' => $file->getMimeType(),
            'meta' => $meta,
        ]);
        return response()->json([
            'id' => $message->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ],
            'content' => $url,
            'type' => $file->getMimeType(),
            'meta' => $meta,
            'created_at' => $message->created_at,
        ]);
    }
}
