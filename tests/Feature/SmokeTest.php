<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SmokeTest extends TestCase
{
    #[Test]
    public function application_boots_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(302); // Redirects to /api/docs
    }

    #[Test]
    public function health_endpoint_works()
    {
        $response = $this->get('/health');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'services'
                ]);
    }

    #[Test]
    public function api_docs_accessible()
    {
        $response = $this->get('/api/docs');
        $response->assertStatus(200);
    }
}