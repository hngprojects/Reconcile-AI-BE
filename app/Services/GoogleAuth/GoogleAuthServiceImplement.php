<?php

namespace App\Services\GoogleAuth;

use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use LaravelEasyRepository\ServiceApi;
use App\Repositories\GoogleAuth\GoogleAuthRepository;
use App\Models\ChartAccountCategory;

class GoogleAuthServiceImplement extends ServiceApi implements GoogleAuthService
{

    /**
     * set title message api for CRUD
     * @param string $title
     */
    protected string $title = "Google Auth Service";
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
    public function __construct(protected GoogleAuthRepository $repository)
    {
        // $this->mainRepository = $mainRepository;
    }

    /**
     * @return string
     */
    public function loginWithGoogle(Request $request): array
    {
        $validator = $request->validate([
            'id_token' => 'required|string'
        ]);

        $payload = $this->repository->validateGoogleToken($request->id_token);

        if (!$payload) {
            throw new \Exception('Invalid Google token', 401);
        }

        ['user' => $user, 'is_new_user' => $isNewUser] = $this->repository->findOrCreateUser($payload);
        $token = $this->repository->generateToken($user);

        if ($isNewUser) {
            $this->sendWelcomeEmail($user, $token);
        }

        if ($user->accountChartCategories()->count() == 0) {
            // Get all the REQUIRED categories 
            $requiredCategories = ChartAccountCategory::where('is_required', true)->get();

            // Create an array of pivot data with UUIDs
            $pivotData = [];
            foreach ($requiredCategories as $category) {
                $pivotData[$category->id] = [
                    'user_id' => $user->id,
                    'account_chart_category_id' => $category->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // Attach categories with pivot data
            $user->accountChartCategories()->attach($pivotData);
        }

        return [
            'user' => $user->setAttribute('is_new_user', $isNewUser), // Add to user object,
            'token' => $token,
            'is_new_user' => $isNewUser,
            'plan' => $user->paymentPlan
        ];
    }

    protected function sendWelcomeEmail($user, $token): void
    {
        $getStartedUrl = config('app.frontend_url') . '/file-upload?token=' . $token;
        Mail::to($user->email)->queue(new WelcomeEmail($user, $getStartedUrl));
    }
}
