<?php

namespace App\Repositories\Statement;

use LaravelEasyRepository\Repository;

interface StatementRepository extends Repository{

    public function store(array $data);
}
