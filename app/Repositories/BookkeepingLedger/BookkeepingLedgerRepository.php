<?php

namespace App\Repositories\BookkeepingLedger;

use LaravelEasyRepository\Repository;
use App\Models\BookkeepingLedger;

interface BookkeepingLedgerRepository extends Repository{

    public function createLedger(array $data): BookkeepingLedger;
    public function createDefaultLedgers(int $userId, array $ledgerTypes): array;
}
