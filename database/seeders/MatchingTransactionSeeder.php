<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ledger;
use App\Models\Statement;

class MatchingTransactionSeeder extends Seeder
{
    public function run()
    {
        $ledgers = Ledger::all();
        $bankStatements = Statement::all();

        foreach ($ledgers as $ledger) {
            $matchingBankStatement = $bankStatements->firstWhere(function ($bankStatement) use ($ledger) {
                return $bankStatement->date == $ledger->date &&
                    $bankStatement->description == $ledger->description &&
                    $bankStatement->amount == $ledger->amount;
            });

            if ($matchingBankStatement) {
                MatchingTransaction::create([
                    'statement_id' => $matchingBankStatement->id,
                    'ledger_id' => $ledger->id,
                    'status' => "Matched"
                ]);
            }
        }
    }
}

