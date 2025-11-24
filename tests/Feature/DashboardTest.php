<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_ventas_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/ventas');

        $response->assertStatus(200);

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ventas')
        );
    }

    public function test_creditos_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/creditos');

        $response->assertStatus(200);

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Creditos')
        );
    }
}
