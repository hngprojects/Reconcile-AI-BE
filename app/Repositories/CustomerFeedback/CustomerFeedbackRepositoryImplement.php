<?php

namespace App\Repositories\CustomerFeedback;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\CustomerFeedback;

class CustomerFeedbackRepositoryImplement extends Eloquent implements CustomerFeedbackRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected CustomerFeedback $model;

    public function __construct(CustomerFeedback $model)
    {
        $this->model = $model;
    }

    public function store(array $data){
        return $this->model->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'] ?? null,
            'request_type' => $data['request_type'] ?? 'Feedback'
        ]);
    }

    public function findByEmail($email){
        return $this->model->where('email', '=', $email)->get();
    }
}