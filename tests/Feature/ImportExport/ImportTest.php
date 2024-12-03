<?php

namespace ImportExport;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_cannot_access_home_via_web(): void
    {
        $response = $this->get('/');

        $response->assertStatus(404);
    }
}
