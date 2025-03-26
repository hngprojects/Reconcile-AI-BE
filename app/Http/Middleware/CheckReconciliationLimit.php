<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Log;

class CheckReconciliationLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Ensure the plan is eagerly loaded
        $activePlan = $user->paymentPlan;

        if (!$activePlan) {
            return response()->json(['message' => 'No active plan found. Please subscribe.'], 403);
        }

        $paymentPlan = $activePlan->plan;

        // dd($activePlan->plan);

        // Get the plan's reconciliation limit
        $maxReconciliations = $paymentPlan->reconciliations_per_month;

        // Skip check for Business plan (-1 means unlimited)
        if ($maxReconciliations === -1) {
            return $next($request);
        }

        // Get the plan's active period
        $startDate = Carbon::parse($paymentPlan->start_date)->startOfDay()->toDateTimeString();
        $expireDate = Carbon::parse($paymentPlan->expire_date)->endOfDay()->toDateTimeString();

        // Optimized count check
        $reconciliationCount = 0;
        if (Reconciliation::where('user_id', $user->id)
        ->whereBetween('created_at', [$startDate, $expireDate])
        ->exists()) {
            // Count reconciliations
            $reconciliationCount = Reconciliation::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $expireDate])
                ->count();
        }
        

        if ($reconciliationCount >= $maxReconciliations) {
            $activePlan->update(['reconciliations_used' => $reconciliationCount]);

            return response()->json([
                'message' => 'You have reached your reconciliation limit. Please upgrade your plan or wait until the next period.'
            ], 429);
        }

        // $activePlan->update(['reconciliations_used' => $reconciliationCount + 1]);

        // Increment only if reconciliations exist
        if ($reconciliationCount > 0) {
            $activePlan->increment('reconciliations_used');
        }

        return $next($request);
    }
}
