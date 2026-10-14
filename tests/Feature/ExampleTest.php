<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Website yang tumbuh bersama idemu.')
            ->assertSee('Undangan digital untuk momen spesial.')
            ->assertSee('Bantuan pemrograman, dari ide sampai jalan.')
            ->assertSee('Untitled27_20261014014436.png')
            ->assertSee('Untitled27_20261014062958.png');
        $this->get('/profile')->assertRedirect(route('login'));
    }
}
