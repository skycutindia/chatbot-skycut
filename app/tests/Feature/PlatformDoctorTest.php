<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformDoctorTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_doctor_passes_in_test_environment(): void
    {
        $this->artisan('platform:doctor')
            ->assertExitCode(0);
    }
}
