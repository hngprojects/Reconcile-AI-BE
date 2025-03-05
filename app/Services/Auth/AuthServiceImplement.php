<?php

namespace App\Services\Auth;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\Auth\AuthRepository;

class AuthServiceImplement extends ServiceApi implements AuthService{

    /**
     * set title message api for CRUD
     * @param string $title
     */
     protected string $title = "";
     /**
     * uncomment this to override the default message
     * protected string $create_message = "";
     * protected string $update_message = "";
     * protected string $delete_message = "";
     */

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected AuthRepository $mainRepository;

    public function __construct(AuthRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
