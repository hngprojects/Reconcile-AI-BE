<?php

namespace App\Repositories\BookkeepingLedger;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\BookkeepingLedger;

class BookkeepingLedgerRepositoryImplement extends Eloquent implements BookkeepingLedgerRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */

    public function __construct(protected BookkeepingLedger $model)
    {
    }

    public function createLedger(array $data): BookkeepingLedger
    {
        return $this->model->create($data);
    }

    public function createDefaultLedgers(int $userId, array $ledgerTypes): array
    {
        $defaultConfig = BookkeepingLedger::getDefaultConfig();
        $ledgers = [];
        
        foreach ($ledgerTypes as $type) {
            if (isset($defaultConfig[$type])) {
                $ledgers[] = $this->createLedger([
                    'user_id' => $userId,
                    'type' => $type,
                    ...$defaultConfig[$type]
                ]);
            }
        }
        
        return $ledgers;
    }
}
