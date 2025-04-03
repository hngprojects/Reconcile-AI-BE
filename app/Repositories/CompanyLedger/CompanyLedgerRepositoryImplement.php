<?php

namespace App\Repositories\CompanyLedger;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\CompanyLedger;

class CompanyLedgerRepositoryImplement extends Eloquent implements CompanyLedgerRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected CompanyLedger $model;

    public function __construct(CompanyLedger $model)
    {
        $this->model = $model;
    }

    public function createLedger(array $data)
    {
        return $this->model->create($data);
    }

    public function activateLedger(string $businessId, string $ledgerId)
    {
        $this->model->where('business_infos_id', $businessId)
            ->update(['is_active' => false]);

        return $this->model->where('id', $ledgerId)
            ->update(['is_active' => true]);
    }

    public function getByBusiness(string $businessId)
    {
        return $this->model->where('business_infos_id', $businessId)->get();
    }
}
