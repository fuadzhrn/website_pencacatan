<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_the_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('login');
    }

    public function test_login_page_can_be_displayed(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSee('Masuk ke Sistem')
            ->assertSee('Sistem Informasi Preventive Maintenance X-Ray');
    }
}
