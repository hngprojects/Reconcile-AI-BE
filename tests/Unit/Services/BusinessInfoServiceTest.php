<?php

namespace Tests\Feature\Services;

use App\Services\BusinessInfo\BusinessInfoServiceImplement;
use App\Repositories\BusinessInfo\BusinessInfoRepository;
use App\Models\BusinessInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Tests\TestCase;

class BusinessInfoServiceTest extends TestCase
{
    use RefreshDatabase;

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
        // dd($response);

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
}
