<?php

use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('reconciliation.{reconciliationId}', function ($user, $reconciliationId) {
    $reconciliation = Reconciliation::find($reconciliationId);

    if (!$reconciliation) {
        Log::warning("Reconciliation not found: {$reconciliationId}");
        return false;
    }

    $isOwner = $reconciliation->user_id === $user->id;
    Log::info("Channel auth for {$user->id}: " . ($isOwner ? 'GRANTED' : 'DENIED'));

    return $isOwner;
});
