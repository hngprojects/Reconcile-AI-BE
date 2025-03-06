<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\NewsLetter\NewsLetterService;
use App\Http\Requests\NewsLetter\SubscribeRequest;
use App\Http\Requests\NewsLetter\UnsubscribeRequest;
use OpenApi\Annotations as OA;

class NewsLetterController extends Controller
{
    private NewsLetterService $newsletter;

    public function __construct(NewsLetterService $newsletter)
    {
        $this->newsletter = $newsletter;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/newsletter/subscribe",
     *     summary="Subscribe to the newsletter",
     *     tags={"Newsletter"},
     *     description="Allows a user to subscribe to the newsletter.",
     *     operationId="subscribeNewsletter",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subscription successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Subscription Successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email must be a valid email address.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        return $this->newsletter->subscribe($request)->toJson();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/newsletter/unsubscribe",
     *     summary="Unsubscribe from the newsletter",
     *     tags={"Newsletter"},
     *     description="Allows a user to unsubscribe from the newsletter.",
     *     operationId="unsubscribeNewsletter",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unsubscription successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unsubscribed Successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Unsubscription failed (email not found)",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Unsubscription Failed"),
     *         )
     *     )
     * )
     */
    public function unsubscribe(UnsubscribeRequest $request): JsonResponse
    {
        return $this->newsletter->unsubscribe($request)->toJson();
    }
}
