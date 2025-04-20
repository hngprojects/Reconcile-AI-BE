<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReconciliationProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public string $reconciliationId;

    public function __construct($reconciliationId, $message)
    {
        $this->reconciliationId = $reconciliationId;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('reconciliation.' . $this->reconciliationId);
    }
}
