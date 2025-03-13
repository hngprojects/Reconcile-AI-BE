<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\NewsLetter\NewsLetterService;
use App\Http\Requests\NewsLetter\SubscribeRequest;
use App\Http\Requests\NewsLetter\UnsubscribeRequest;
use OpenApi\Annotations as OA;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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

    /**
     * @OA\Get(
     *     path="/api/v1/newsletter/unsubscribe/{email}",
     *     summary="One-click unsubscribe from the newsletter",
     *     tags={"Newsletter"},
     *     description="Allows a user to unsubscribe from the newsletter by clicking a link in their email.",
     *     operationId="oneClickUnsubscribe",
     *     @OA\Parameter(
     *         name="email",
     *         in="path",
     *         required=true,
     *         description="The email address to unsubscribe",
     *         @OA\Schema(type="string", format="email", example="user@example.com")
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
     *         description="Invalid email format",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Invalid email format"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found in subscription list",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Email not found in subscription list"),
     *         )
     *     )
     * )
     */
    public function oneClickUnsubscribe($email)
    {

        return $this->newsletter->onClick($email);
    }
    
    /**
     * Display the result page for newsletter actions (unsubscribe/resubscribe)
     */
    public function showResult(Request $request)
    {
        $action = $request->get('action', 'unsubscribe'); // Default to 'unsubscribe'
        $status = $request->get('status', 'error');

        // Different messages based on action and status
        $messages = [
            'unsubscribe' => [
                'success' => 'You have been successfully unsubscribed from our newsletter.',
                'error' => 'There was an error processing your unsubscribe request. Please try again or contact support.',
                'invalid' => 'The unsubscribe link is invalid or expired.'
            ],
            'resubscribe' => [
                'success' => 'Welcome back! You have successfully resubscribed to our newsletter.',
                'error' => 'There was an error processing your resubscribe request. Please try again or contact support.',
                'invalid' => 'The resubscribe link is invalid or expired.'
            ]
        ];

        return view('newsletter.result', [
            'action' => $action,
            'status' => $status,
            'message' => $messages[$action][$status] ?? $messages[$action]['error']
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/newsletter/resubscribe/{email}",
     *     summary="One-click resubscribe to the newsletter",
     *     tags={"Newsletter"},
     *     description="Allows a user to resubscribe to the newsletter by clicking a link in their email.",
     *     operationId="oneClickResubscribe",
     *     @OA\Parameter(
     *         name="email",
     *         in="path",
     *         required=true,
     *         description="The email address to resubscribe",
     *         @OA\Schema(type="string", format="email", example="user@example.com")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resubscription successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Resubscribed Successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid email format",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Invalid email format"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found in subscription list",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Email not found in subscription list"),
     *         )
     *     )
     * )
     */
    public function oneClickResubscribe($email)
    {
        return $this->newsletter->onClickResubscribe($email);
    }
}
