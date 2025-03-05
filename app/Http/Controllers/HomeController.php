<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1",
     *     summary="Home Endpoint",
     *     description="Welcome message or API overview",
     *     tags={"Home"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Welcome to the API")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json(["message" => "Welcome to reconcile AI API o"]);
    }

}
