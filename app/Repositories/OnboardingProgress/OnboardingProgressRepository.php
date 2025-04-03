<?php

namespace App\Repositories\OnboardingProgress;

use LaravelEasyRepository\Repository;

interface OnboardingProgressRepository extends Repository{

    public function getProgress(int $userId);
    public function completeStep(int $userId, string $step);
    public function isComplete(int $userId);
}
