<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

/**
 * @OA\Get(
 *     path="/",
 *     summary="Welcome to ReconcieAI",
 *     tags={"General"},
 *     @OA\Response(
 *         response=200,
 *         description="Welcome message",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Welcome to ReconcieAI!")
 *         )
 *     )
 * )
 */
Route::get('/', [HomeController::class, 'index']);
Route::post('/login', [LoginController::class, 'login']);