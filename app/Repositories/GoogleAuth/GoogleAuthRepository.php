<?php

namespace App\Repositories\GoogleAuth;

use LaravelEasyRepository\Repository;

interface GoogleAuthRepository extends Repository{

    public function validateGoogleToken(string $idToken): ?array;
    public function findOrCreateUser(array $payload): array;
    public function generateToken($user): string;
    public function assignBasicPlan($user): void;
}
