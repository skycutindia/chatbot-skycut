<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_login_when_no_demo_site_exists(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
