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

    public function __construct(protected BankAccount $model)
    {
    }

    public function createBankAccount(array $data): BankAccount
    {
        return $this->model->create($data);
    }
}
