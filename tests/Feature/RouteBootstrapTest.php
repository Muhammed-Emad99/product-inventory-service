<?php

namespace Tests\Feature;

use Tests\TestCase;

class RouteBootstrapTest extends TestCase
{
    public function test_home_route_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertJson([
                'message' => 'Product Inventory Microservice API is running.',
            ]);
    }
}
