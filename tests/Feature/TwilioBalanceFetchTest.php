<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityIntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwilioBalanceFetchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    #[Test]
    public function it_returns_twilio_balance_for_entity(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response(['balance' => '42.50', 'currency' => 'USD'], 200)]);

        $entity = $this->createEntityWithIntegration();
        $user = User::factory()->create(['entity_id' => $entity->id]);

        $response = $this->actingAs($user)
            ->getJson(route('integrations.twilio.balance', ['entity' => $entity->id]));

        $response->assertOk()
            ->assertJson([
                'balance' => '42.50',
                'currency' => 'USD',
            ]);

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_caches_balance_responses(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response(['balance' => '10.00', 'currency' => 'USD'], 200)]);

        $entity = $this->createEntityWithIntegration();
        $user = User::factory()->create(['entity_id' => $entity->id]);

        $this->actingAs($user)
            ->getJson(route('integrations.twilio.balance', ['entity' => $entity->id]))
            ->assertOk();

        $this->actingAs($user)
            ->getJson(route('integrations.twilio.balance', ['entity' => $entity->id]))
            ->assertOk();

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_returns_error_when_credentials_are_missing(): void
    {
        $entity = $this->createEntity();
        $user = User::factory()->create(['entity_id' => $entity->id]);

        $response = $this->actingAs($user)
            ->getJson(route('integrations.twilio.balance', ['entity' => $entity->id]));

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Twilio credentials are missing for this entity.',
            ]);
    }

    #[Test]
    public function it_returns_error_when_twilio_request_fails(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $entity = $this->createEntityWithIntegration();
        $user = User::factory()->create(['entity_id' => $entity->id]);

        $response = $this->actingAs($user)
            ->getJson(route('integrations.twilio.balance', ['entity' => $entity->id]));

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Twilio error: Unauthorized',
            ]);
    }

    private function createEntityWithIntegration(array $overrides = []): Entity
    {
        $entity = $this->createEntity();

        EntityIntegrationSetting::create(array_merge([
            'entity_id' => $entity->id,
            'twilio_account_sid' => 'AC00000000000000000000000000000000',
            'twilio_auth_token' => 'secret-token',
            'twilio_sms_enabled' => true,
        ], $overrides));

        return $entity;
    }

    private function createEntity(): Entity
    {
        return Entity::create([
            'name' => 'Test Entity',
            'slug' => Str::slug('entity-' . Str::uuid()),
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }
}
