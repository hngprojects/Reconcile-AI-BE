<?php

namespace App\Repositories\CompanyLedger;

use LaravelEasyRepository\Repository;

interface CompanyLedgerRepository extends Repository{

    public function createLedger(array $data);
    public function activateLedger(string $businessId, string $ledgerId);
    public function getByBusiness(string $businessId);
}
