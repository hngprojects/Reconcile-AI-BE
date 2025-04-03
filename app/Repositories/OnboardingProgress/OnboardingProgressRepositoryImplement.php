<?php

namespace App\Repositories\OnboardingProgress;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\OnboardingProgress;

class OnboardingProgressRepositoryImplement extends Eloquent implements OnboardingProgressRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected OnboardingProgress $model;

    public function __construct(OnboardingProgress $model)
    {
        $this->model = $model;
    }

    public function getProgress(int $userId)
    {
        return $this->model->firstOrCreate(['user_id' => $userId]);
    }

    public function completeStep(int $userId, string $step)
    {
        $validSteps = ['basics', 'bank', 'ledger', 'finish'];
        
        if (!in_array($step, $validSteps)) {
            throw new \InvalidArgumentException("Invalid onboarding step");
        }

        return $this->model->updateOrCreate(
            ['user_id' => $userId],
            ["completed_{$step}" => true]
        );
    }

    public function isComplete(int $userId)
    {
        $progress = $this->getProgress($userId);
        return $progress->completed_basics 
            && $progress->completed_bank
            && $progress->completed_ledger
            && $progress->completed_finish;
    }
}
