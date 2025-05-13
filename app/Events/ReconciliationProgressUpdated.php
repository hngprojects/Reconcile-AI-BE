<?php

namespace App\Events;

use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconciliationProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;
    public Reconciliation $reconciliation;
    public User $user;

    public function __construct(Reconciliation $reconciliation, User $owner, array $message)
    {
        $this->reconciliation = $reconciliation;
        $this->user = $owner;
        $this->message = $message;
    }

    public function broadcastOn()
    {

        Log::info('Broadcasting to channel: reconciliation.' . $this->reconciliation->id);
        return new PrivateChannel('reconciliation.' . $this->reconciliation->id);
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
