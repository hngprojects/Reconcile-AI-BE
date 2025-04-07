<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *    title="Reconcile AI Apis",
 *    version="1.0.0",
 *    description="API documentation for Reconcile AI application"
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}
