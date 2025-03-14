<?php

namespace App\Repositories\NewsLetter;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\NewsLetter;

class NewsLetterRepositoryImplement extends Eloquent implements NewsLetterRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property NewsLetter|mixed $model;
    */
    protected NewsLetter $model;

    public function __construct(NewsLetter $model)
    {
        $this->model = $model;
    }

    public function subscribe($email)
    {
        return $this->model->updateOrCreate(
            ['email' => $email],
            ['subscribed' => true]
        );
    }

    public function unsubscribe($email)
    {
        $this->model->where('email', $email)->firstOrFail();
        return $this->model->where('email', $email)->update(['subscribed' => false]);
    }

    public function checkforsubscriber($email)
    {
        return $this->model->where('email', $email)->firstOrFail();
    }

    public function findByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function resubscribe($email)
    {
        return $this->model->where('email', $email)->update(['subscribed' => true]);
    }
}
