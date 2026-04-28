<?php

namespace Tests\Feature;

use App\Models\SystemConfig;
use App\Models\User;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    private User $adminUser;
    private User $memberUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->memberUser = User::factory()->create(['role' => User::ROLE_MEMBER]);
    }

    public function test_get_system_configuration(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/config');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    public function test_get_specific_configuration(): void
    {
        SystemConfig::setValue('test_key', 'test_value', 'string', 'Test configuration');

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/config/test_key');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'key',
                'value',
                'type',
                'description',
            ],
        ]);
        $this->assertEquals('test_value', $response->json('data.value'));
    }

    public function test_update_configuration(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/config', [
                'configurations' => [
                    [
                        'key' => 'loan_interest_rate',
                        'value' => '15.00',
                        'type' => 'number',
                        'description' => 'Updated interest rate',
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(15.00, SystemConfig::getValue('loan_interest_rate'));
    }

    public function test_update_single_configuration(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/config/loan_interest_rate', [
                'value' => '18.00',
                'type' => 'number',
                'description' => 'Updated interest rate',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(18.00, SystemConfig::getValue('loan_interest_rate'));
    }

    public function test_non_admin_cannot_update_configuration(): void
    {
        $response = $this->actingAs($this->memberUser)
            ->putJson('/api/config', [
                'configurations' => [
                    [
                        'key' => 'loan_interest_rate',
                        'value' => '20.00',
                        'type' => 'number',
                    ],
                ],
            ]);

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_get_configuration(): void
    {
        $response = $this->actingAs($this->memberUser)
            ->getJson('/api/config');

        $response->assertStatus(403);
    }

    public function test_configuration_type_casting(): void
    {
        SystemConfig::setValue('test_number', '42', 'number');
        SystemConfig::setValue('test_boolean', '1', 'boolean');
        SystemConfig::setValue('test_json', '{"key":"value"}', 'json');

        $this->assertEquals(42.0, SystemConfig::getValue('test_number'));
        $this->assertTrue(SystemConfig::getValue('test_boolean'));
        $this->assertIsArray(SystemConfig::getValue('test_json'));
    }

    public function test_get_all_configuration(): void
    {
        SystemConfig::setValue('config1', 'value1', 'string');
        SystemConfig::setValue('config2', '100', 'number');

        $allConfig = SystemConfig::getAllConfig();

        $this->assertIsArray($allConfig);
        $this->assertArrayHasKey('config1', $allConfig);
        $this->assertArrayHasKey('config2', $allConfig);
    }

    public function test_configuration_not_found(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/config/nonexistent_key');

        $response->assertStatus(404);
    }
}
