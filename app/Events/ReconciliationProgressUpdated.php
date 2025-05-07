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

    public array $message;
    public string $reconciliationId;

    public function __construct(string $reconciliationId, array $message)
    {
        $this->reconciliationId = $reconciliationId;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('reconciliation.' . $this->reconciliationId);
    }

    public function broadcastAs()
    {
        return 'reconciliation-progress-updated';
    }

    public function broadcastWith()
    {
        return $this->message;
    }
}
