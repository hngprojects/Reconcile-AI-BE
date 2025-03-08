<?php

namespace App\Repositories\ContactUs;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\ContactSubmission;

class ContactUsRepositoryImplement extends Eloquent implements ContactUsRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected ContactSubmission $model;

    public function __construct(ContactSubmission $model)
    {
        $this->model = $model;
    }

    public function creatContactMessge($data)
    {
        return $this->model->create($data);
    }
}
