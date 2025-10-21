<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    // Search users by name (for invite/autocomplete)
    public function search(Request $request)
    {
        $name = $request->query('name', '');
        if (!$name) {
            return response()->json([]);
        }
        $users = User::where('name', 'like', "%$name%")
            ->select('id', 'name', 'avatar')
            ->limit(10)
            ->get();
        return response()->json($users);
    }
}
