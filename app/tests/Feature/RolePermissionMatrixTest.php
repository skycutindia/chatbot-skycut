<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_settings_shows_role_matrix(): void
    {
        $org = Organization::create([
            'name' => 'Matrix Org',
            'slug' => 'matrix-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'matrix-owner@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('settings.organization.edit'))
            ->assertOk()
            ->assertSee('Role permissions')
            ->assertSee('Viewer');

        $agentRow = collect(RolePermissionMatrix::rows())->firstWhere('role', 'agent');
        $this->assertTrue($agentRow['inbox']);
        $this->assertFalse($agentRow['settings']);
    }
}
