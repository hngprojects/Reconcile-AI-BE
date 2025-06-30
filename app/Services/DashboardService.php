<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Ledger;
use App\Models\BookkeepingLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get monthly analytics for the given user.
     *
     * @param User $user
     * @return array
     */
    public function getMonthlyAnalytics(User $user): array
    {
        // Get current month and last month date ranges
        $now = Carbon::now();
        $startOfThisMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfThisMonth = $now->copy()->endOfMonth()->toDateString();

        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth()->toDateString();

        // Total bank balance of all user's accounts
        $totalBankBalance = BankAccount::where('user_id', $user->id)
            ->sum('opening_balance');

        // Helper function to get ledger total by category and date range
        $getLedgerTotal = function (string $category, string $startDate, string $endDate) use ($user) {
            return Ledger::join('bookkeeping_ledgers', 'ledgers.bookkeeping_ledger_id', '=', 'bookkeeping_ledgers.id')
                ->where('bookkeeping_ledgers.user_id', $user->id)
                ->whereJsonContains('bookkeeping_ledgers.categories', $category)
                ->whereBetween('ledgers.date', [$startDate, $endDate])
                ->selectRaw('SUM(CAST(ledgers.amount AS numeric)) as total')
                ->value('total') ?? 0;
        };

        // Total amount of ledgers with expense category for this month
        $totalExpenseThisMonth = $getLedgerTotal('expense', $startOfThisMonth, $endOfThisMonth);

        // Total amount of ledgers with income category for this month
        $totalIncomeThisMonth = $getLedgerTotal('income', $startOfThisMonth, $endOfThisMonth);

        // Total amount of ledgers with expense category for last month
        $totalExpenseLastMonth = $getLedgerTotal('expense', $startOfLastMonth, $endOfLastMonth);

        // Total amount of ledgers with income category for last month
        $totalIncomeLastMonth = $getLedgerTotal('income', $startOfLastMonth, $endOfLastMonth);

        // Calculate differences and increase/decrease flags as percentages
        $expenseDifferenceValue = $totalExpenseThisMonth - $totalExpenseLastMonth;
        $incomeDifferenceValue = $totalIncomeThisMonth - $totalIncomeLastMonth;

        $expenseDifferencePercent = $totalExpenseLastMonth != 0 ? ($expenseDifferenceValue / $totalExpenseLastMonth) * 100 : 0;
        $incomeDifferencePercent = $totalIncomeLastMonth != 0 ? ($incomeDifferenceValue / $totalIncomeLastMonth) * 100 : 0;

        $bankBalanceLastMonth = BankAccount::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('opening_balance');

        $bankBalanceDifferenceValue = $totalBankBalance - $bankBalanceLastMonth;
        $bankBalanceDifferencePercent = $bankBalanceLastMonth != 0 ? ($bankBalanceDifferenceValue / $bankBalanceLastMonth) * 100 : 0;
        $bankBalanceIncreased = $bankBalanceDifferenceValue > 0;
        $bankBalanceDecreased = $bankBalanceDifferenceValue < 0;

        $bankBalance = [
            'total' => $totalBankBalance,
            'difference_value' => $bankBalanceDifferenceValue,
            'difference_percent' => $bankBalanceDifferencePercent,
            'increased' => $bankBalanceIncreased,
            'decreased' => $bankBalanceDecreased,
        ];

        return [
            'bank_balance' => $bankBalance,
            'expense' => [
                'this_month' => $totalExpenseThisMonth,
                'last_month' => $totalExpenseLastMonth,
                'difference_value' => $expenseDifferenceValue,
                'difference_percent' => $expenseDifferencePercent,
                'increased' => $expenseDifferenceValue > 0,
                'decreased' => $expenseDifferenceValue < 0,
            ],
            'income' => [
                'this_month' => $totalIncomeThisMonth,
                'last_month' => $totalIncomeLastMonth,
                'difference_value' => $incomeDifferenceValue,
                'difference_percent' => $incomeDifferencePercent,
                'increased' => $incomeDifferenceValue > 0,
                'decreased' => $incomeDifferenceValue < 0,
            ],
        ];
    }
}
