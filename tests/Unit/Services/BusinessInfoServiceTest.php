<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\BusinessInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use App\Services\BusinessInfo\BusinessInfoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\BusinessInfo\BusinessInfoRepository;
use App\Services\BusinessInfo\BusinessInfoServiceImplement;

class BusinessInfoServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected BusinessInfoService $businessInfoService;

    public function setUp(): void
    {
        parent::setUp();
        $this->businessInfoService = $this->app->make(BusinessInfoService::class);
    }

    public function testSetupBusinessInfoSuccess()
    {
        // Mock the repository and the returned model
        $mockRepository = $this->createMock(BusinessInfoRepository::class);

        $mockBusinessInfo = BusinessInfo::factory()->make([
            'user_id' => 1,
            'name' => 'Test Business',
            'type' => 'Retail',
            'currency' => 'USD',
            'reporting_year' => 'January - December',
        ]);

        $mockRepository->method('createBusinessInfo')->willReturn($mockBusinessInfo);

        $service = new BusinessInfoServiceImplement($mockRepository);

        // Simulate a request with valid data
        $mockRequest = Request::create('/setup-business-info', 'POST', [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'USD',
            'reporting_year' => 'January - December',
        ]);

        $mockRequest->setUserResolver(fn() => (object)['id' => 1]);

        // Call the service method
        $response = $service->setupBusinessInfo($mockRequest);

        // Assertions
        $this->assertEquals(201, $response['code']);
        $this->assertEquals('Business info created successfully', $response['message']);
        $this->assertNotNull($response['data']);
        $this->assertEquals('Test Business', $response['data']->name);
    }

    public function testSetupBusinessInfoValidationError()
    {
        // Mock the repository (not needed for validation)
        $mockRepository = $this->createMock(BusinessInfoRepository::class);
        $service = new BusinessInfoServiceImplement($mockRepository);

        // Simulate a request with missing required fields
        $mockRequest = Request::create('/setup-business-info', 'POST', [
            'business_type' => 'Retail',
            'currency' => 'USD',
            // Missing 'business_name' and 'reporting_year'
        ]);

        // Call the service method and catch the response
        $response = $service->setupBusinessInfo($mockRequest);

        // Assertions
        $this->assertEquals(422, $response['code']);
        $this->assertEquals('Validation error', $response['message']);
        $this->assertNotNull($response['error']);
        $this->assertArrayHasKey('business_name', $response['error']);
        $this->assertArrayHasKey('reporting_year', $response['error']);
    }

    public function testUpdateBusinessInfoSuccess()
    {
        // First create a user
        $user = User::factory()->create(['id' => 1]);
        
        // Create a business info record for this user
        $businessInfo = BusinessInfo::factory()->create([
            'user_id' => $user->id, // Use the created user's ID
            'name' => 'Original Business',
            'type' => 'Original Type',
            'currency' => 'NGN',
            'reporting_year' => 'January - December',
        ]);

        $updateData = [
            'business_name' => 'Updated Business',
            'business_type' => 'Updated Retail',
            'currency' => 'USD',
            'reporting_year' => 'July - June',
        ];

        // Create a request with the authenticated user
        $request = Request::create("/business-info/{$businessInfo->id}", 'PUT', $updateData);
        $request->setUserResolver(fn() => $user);

        // Call the service method
        $response = $this->businessInfoService->updateBusinessInfo($businessInfo->id, $request);

        // Assertions
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('Business info updated successfully', $response['message']);
        
        // Refresh the model from database
        $updatedBusinessInfo = BusinessInfo::find($businessInfo->id);
        
        $this->assertEquals('Updated Business', $updatedBusinessInfo->name);
        $this->assertEquals('Updated Retail', $updatedBusinessInfo->type);
        $this->assertEquals('USD', $updatedBusinessInfo->currency);
        $this->assertEquals('July - June', $updatedBusinessInfo->reporting_year);
    }

    public function testUpdateBusinessInfoNotFound()
    {
        // Mock the repository to return null for "not found"
        $mockRepository = $this->createMock(BusinessInfoRepository::class);
        $mockRepository->method('findOne')->willReturn(null);

        $service = new BusinessInfoServiceImplement($mockRepository);

        // Simulate a request with valid data
        $mockRequest = Request::create('/update-business-info', 'PUT', [
            'business_name' => 'Updated Business',
            'business_type' => 'Updated Retail',
            'currency' => 'USD',
            'reporting_year' => 'July - June',
        ]);

        // Call the service method
        $response = $service->updateBusinessInfo('non-existent-id', $mockRequest);

        // Assertions
        $this->assertEquals(404, $response['code']);
        $this->assertEquals('Business info not found', $response['message']);
        $this->assertNull($response['data']);
    }

    public function testUpdateBusinessInfoValidationError()
    {
        // Mock the repository and the existing model
        $mockRepository = $this->createMock(BusinessInfoRepository::class);
        $mockRepository->method('findOne')->willReturn(new BusinessInfo());

        $service = new BusinessInfoServiceImplement($mockRepository);

        // Simulate a request with invalid data
        $mockRequest = Request::create('/update-business-info', 'PUT', [
            'business_name' => '', // Invalid data
            'business_type' => '', // Invalid data
        ]);

        // Call the service method
        $response = $service->updateBusinessInfo('1f2b3c4d-5e6f-7g8h-9i0j-123456789abc', $mockRequest);

        // Assertions
        $this->assertEquals(422, $response['code']);
        $this->assertEquals('Validation error', $response['message']);
        $this->assertNotNull($response['error']);
        $this->assertArrayHasKey('business_name', $response['error']);
        $this->assertArrayHasKey('business_type', $response['error']);
    }
}
