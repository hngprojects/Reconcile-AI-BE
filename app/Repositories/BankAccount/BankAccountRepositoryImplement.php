<?php

namespace App\Repositories\BankAccount;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\BankAccount;

class BankAccountRepositoryImplement extends Eloquent implements BankAccountRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected BankAccount $model;

    public function __construct(BankAccount $model)
    {
        $this->model = $model;
    }

    public function createAccount(array $data)
    {
        return $this->model->create($data);
    }

    public function setPrimaryAccount(string $businessId, string $accountId)
    {
        $this->model->where('business_infos_id', $businessId)
            ->update(['is_primary' => false]);

        return $this->model->where('id', $accountId)
            ->update(['is_primary' => true]);
    }

    public function getByBusiness(string $businessId)
    {
        return $this->model->where('business_infos_id', $businessId)->get();
    }
}
