<?php

use Illuminate\Support\Facades\Route;
/**
 * @OA\Info(
 *     title="ReconcieAI API",
 *     version="1.0.0",
 *     description="A simple API for ReconcieAI"
 * )
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
Route::get('/', function () {
    return response()->json(['message' => 'Welcome to ReconcieAI!']);
});