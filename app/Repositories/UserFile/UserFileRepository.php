<?php

namespace App\Repositories\UserFile;

use LaravelEasyRepository\Repository;
use App\Models\User;

interface UserFileRepository extends Repository{

    public function store(array $data);
    public function list(User $user);
}
