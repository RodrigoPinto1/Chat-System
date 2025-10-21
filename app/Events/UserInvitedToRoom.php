<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\User;
use App\Models\Room;

class UserInvitedToRoom implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $roomId;
    public $inviterId;
    public $inviteeId;

    public function __construct(Room $room, User $inviter, User $invitee)
    {
        $this->roomId = $room->id;
        $this->inviterId = $inviter->id;
        $this->inviteeId = $invitee->id;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }
}
