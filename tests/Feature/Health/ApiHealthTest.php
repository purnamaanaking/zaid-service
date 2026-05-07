<?php

namespace Tests\Feature\Health;

use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    public function test_api_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Zaid service is healthy',
        ]);
    }
}
