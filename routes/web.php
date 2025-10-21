
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PusherController;use App\Http\Controllers\RoomController;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('chat', function () {
    return Inertia::render('Chat');
})->middleware(['auth', 'verified'])->name('chat');

// Pusher Routes
Route::get('pusher', [PusherController::class, 'index'])->name('pusher.index');

Route::post('pusher/broadcast', [PusherController::class, 'broadcast'])->name('pusher.broadcast');

Route::post('pusher/receive', [PusherController::class, 'receive'])->name('pusher.receive');

// Room routes
Route::get('rooms', [RoomController::class, 'index'])->middleware('auth')->name('rooms.index');
Route::post('rooms', [RoomController::class, 'store'])->middleware('auth')->name('rooms.store');
Route::post('rooms/{room}/join', [RoomController::class, 'join'])->middleware('auth')->name('rooms.join');
Route::get('rooms/{room}', [RoomController::class, 'show'])->middleware('auth')->name('rooms.show');

// Message store route
use App\Http\Controllers\MessageController;


Route::get('messages', [MessageController::class, 'index'])->middleware('auth')->name('messages.index');
Route::post('messages', [MessageController::class, 'store'])->middleware('auth')->name('messages.store');

// End Pusher Routes

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
