<?php

namespace App\Repositories\StatementFile;

use LaravelEasyRepository\Repository;

interface StatementFileRepository extends Repository{
    public function store(array $data);
}
