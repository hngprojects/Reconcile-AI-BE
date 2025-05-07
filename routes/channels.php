<?php

use App\Models\Reconciliation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('reconciliation.{reconciliationId}', function ($user, $reconciliationId) {
    Log::info('Channel auth attempt', [
        'user_id' => $user->id ?? 'null',
        'reconciliation_id' => $reconciliationId
    ]);

    $reconciliation = Reconciliation::where('id', '=', $reconciliationId)->with('user')->first();

    if (!$reconciliation) {
        Log::info('Reconciliation not found');
        return false;
    }

    if (!$reconciliation->user) {
        Log::info('Reconciliation user not found');
        return false;
    }

    $authorized = $reconciliation->user->id === $user->id;
    Log::info('Auth result', ['authorized' => $authorized]);

    return $authorized;
});
