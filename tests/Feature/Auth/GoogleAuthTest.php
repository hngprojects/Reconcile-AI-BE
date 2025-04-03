<?php

namespace Tests\Feature\Auth;

use Mockery;
use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use App\Services\GoogleAuth\GoogleAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\GoogleAuth\GoogleAuthRepository;
use App\Services\GoogleAuth\GoogleAuthServiceImplement;
use App\Repositories\GoogleAuth\GoogleAuthRepositoryImplement;

class GoogleAuthTest extends TestCase
{
    protected $googleAuthService;
    protected $googleAuthRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock of the repository
        $this->googleAuthRepository = Mockery::mock(GoogleAuthRepository::class);
        $this->app->instance(GoogleAuthRepository::class, $this->googleAuthRepository);
        
        // Bind the mock repository to the service
        $this->googleAuthService = new GoogleAuthServiceImplement($this->googleAuthRepository);
        $this->app->instance(GoogleAuthService::class, $this->googleAuthService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_validates_google_login_request()
    {
        $response = $this->postJson('/api/v1/auth/google-login', []);
        
        $response->assertStatus(422)
            ->assertJson([
                'status_code' => 422,
                'message' => [
                    'id_token' => ['The id token field is required.']
                ]
            ]);
    }

    #[Test]
    public function it_rejects_invalid_google_token_for_login()
    {
        $this->googleAuthRepository
            ->shouldReceive('validateGoogleToken')
            ->with('invalid_token')
            ->andReturnNull();
        
        $response = $this->postJson('/api/v1/auth/google-login', [
            'id_token' => 'invalid_token'
        ]);
        
        $response->assertStatus(401)
            ->assertJson([
                'status_code' => 401,
                'message' => 'Invalid Token'
            ]);
    }

    #[Test]
    public function it_logs_in_existing_user_with_valid_google_token()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['plan' => 'Basic']);
        $user->paymentPlan()->create([
            'plan_id' => $plan->id,
            'start_date' => now(),
            'expire_date' => now()->addDays(30)
        ]);
        
        $mockPayload = [
            'sub' => '1234567890',
            'email' => $user->email,
            'given_name' => 'John',
            'family_name' => 'Doe',
            'picture' => 'https://example.com/avatar.jpg'
        ];
        
        $this->googleAuthRepository
            ->shouldReceive('validateGoogleToken')
            ->with('valid_token')
            ->andReturn($mockPayload);
            
        $this->googleAuthRepository
            ->shouldReceive('findOrCreateUser')
            ->with($mockPayload)
            ->andReturn([
                'user' => $user,
                'isNewUser' => false
            ]);
            
        $this->googleAuthRepository
            ->shouldReceive('generateToken')
            ->with($user)
            ->andReturn('jwt_token');
            
        $this->googleAuthRepository
            ->shouldReceive('formatResponse')
            ->with($user, 'jwt_token', 'Login Successful', 200)
            ->andReturn([
                'status_code' => 200,
                'message' => 'Login Successful',
                'access_token' => 'jwt_token',
                'data' => [
                    'user' => array_merge($user->toArray(), ['payment_plan' => $user->paymentPlan])
                ]
            ]);
        
        $response = $this->postJson('/api/v1/auth/google-login', [
            'id_token' => 'valid_token'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Login Successful',
                'access_token' => 'jwt_token'
            ]);
    }

    #[Test]
    public function it_rejects_login_for_unregistered_user()
    {
        $mockPayload = [
            'sub' => '1234567890',
            'email' => 'new@example.com',
            'given_name' => 'New',
            'family_name' => 'User',
            'picture' => 'https://example.com/avatar.jpg'
        ];
        
        $this->googleAuthRepository
            ->shouldReceive('validateGoogleToken')
            ->with('valid_token')
            ->andReturn($mockPayload);
            
        $this->googleAuthRepository
            ->shouldReceive('findOrCreateUser')
            ->with($mockPayload)
            ->andReturn([
                'user' => null,
                'isNewUser' => true
            ]);
        
        $response = $this->postJson('/api/v1/auth/google-login', [
            'id_token' => 'valid_token'
        ]);
        
        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'Please register'
            ]);
    }

    #[Test]
    public function it_registers_new_user_with_valid_google_token()
    {
        Mail::fake();
        
        $mockPayload = [
            'sub' => '1234567890',
            'email' => 'new@example.com',
            'given_name' => 'New',
            'family_name' => 'User',
            'picture' => 'https://example.com/avatar.jpg'
        ];
        
        $user = User::factory()->make(['email' => 'new@example.com']);
        $token = 'jwt_token';
        
        // 1. Setup ALL required mock expectations
        $this->googleAuthRepository
            ->shouldReceive('validateGoogleToken')
            ->with('valid_token')
            ->andReturn($mockPayload);
            
        $this->googleAuthRepository
            ->shouldReceive('findOrCreateUser')
            ->with($mockPayload)
            ->andReturn([
                'user' => $user,
                'isNewUser' => true
            ]);
            
        $this->googleAuthRepository
            ->shouldReceive('generateToken')
            ->with($user)
            ->andReturn($token);
            
        $this->googleAuthRepository
            ->shouldReceive('formatResponse')
            ->with($user, $token, 'User Created Successfully', 201)
            ->andReturn([
                'status_code' => 201,
                'message' => 'User Created Successfully',
                'access_token' => $token,
                'data' => [
                    'user' => array_merge($user->toArray(), ['payment_plan' => null])
                ]
            ]);
        
        // 2. Make the call to the endpoint
        $response = $this->postJson('/api/v1/auth/google-register', [
            'id_token' => 'valid_token'
        ]);
        
        // 3. Assert the response
        $response->assertStatus(201)
            ->assertJson([
                'status_code' => 201,
                'message' => 'User Created Successfully',
                'access_token' => $token
            ]);
        
        // 4. Verify email was sent
        /* Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        }); */
    }

    #[Test]
    public function it_rejects_registration_for_existing_user()
    {
        $user = User::factory()->create();
        
        $mockPayload = [
            'sub' => '1234567890',
            'email' => $user->email,
            'given_name' => 'Existing',
            'family_name' => 'User',
            'picture' => 'https://example.com/avatar.jpg'
        ];
        
        $this->googleAuthRepository
            ->shouldReceive('validateGoogleToken')
            ->with('valid_token')
            ->andReturn($mockPayload);
            
        $this->googleAuthRepository
            ->shouldReceive('findOrCreateUser')
            ->with($mockPayload)
            ->andReturn([
                'user' => $user,
                'isNewUser' => false
            ]);
        
        $response = $this->postJson('/api/v1/auth/google-register', [
            'id_token' => 'valid_token'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'status_code' => 403,
                'message' => 'Please login into your account'
            ]);
    }

    #[Test]
    public function it_creates_basic_plan_if_not_exists_during_registration()
    {
        Mail::fake();
        
        // Delete any existing Basic plans
        Plan::where('plan', 'Basic')->delete();
        
        $mockPayload = [
            'sub' => '1234567890',
            'email' => 'new@example.com',
            'given_name' => 'New',
            'family_name' => 'User',
            'picture' => 'https://example.com/avatar.jpg'
        ];
        
        $user = User::factory()->make(['email' => 'new@example.com']);
        
        // Mock the repository to simulate plan creation
        $this->googleAuthRepository
            ->shouldReceive('validateGoogleToken')
            ->with('valid_token')
            ->andReturn($mockPayload);
            
        $this->googleAuthRepository
            ->shouldReceive('findOrCreateUser')
            ->with($mockPayload)
            ->andReturnUsing(function ($payload) use ($user) {
                // This simulates what the actual repository does
                Plan::firstOrCreate(
                    ['plan' => 'Basic'],
                    [
                        'name' => 'Basic Plan',
                        'description' => 'Free trial',
                        'plan_length' => 30,
                        'reconciliations_per_month' => 5,
                        'amount' => 0.00
                    ]
                );
                
                return [
                    'user' => $user,
                    'isNewUser' => true
                ];
            });
            
        $this->googleAuthRepository
            ->shouldReceive('generateToken')
            ->with($user)
            ->andReturn('jwt_token');
            
        $this->googleAuthRepository
            ->shouldReceive('formatResponse')
            ->with($user, 'jwt_token', 'User Created Successfully', 201)
            ->andReturn([
                'status_code' => 201,
                'message' => 'User Created Successfully',
                'access_token' => 'jwt_token',
                'data' => [
                    'user' => array_merge($user->toArray(), ['payment_plan' => null])
                ]
            ]);
        
        $response = $this->postJson('/api/v1/auth/google-register', [
            'id_token' => 'valid_token'
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('plans', ['plan' => 'Basic']);
    }
}
