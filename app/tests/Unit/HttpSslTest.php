<?php

namespace Tests\Unit;

use App\Support\HttpSsl;
use Tests\TestCase;

class HttpSslTest extends TestCase
{
    public function test_ca_bundle_path_exists_and_is_used(): void
    {
        $bundle = storage_path('certs/cacert.pem');
        $this->assertFileExists($bundle);

        $options = HttpSsl::clientOptions();
        $this->assertArrayHasKey('verify', $options);
        $this->assertSame($bundle, $options['verify']);
    }
}
