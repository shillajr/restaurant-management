<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityIntegrationSetting;
use App\Models\EntityNotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chef', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    }

    #[Test]
    public function admin_can_broadcast_sms_to_all_users(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response([], 201)]);

        $entity = $this->createEntityWithCommunicationSettings();

        $admin = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000001',
        ]);
        $admin->assignRole('admin');

        $cook = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000002',
        ]);
        $cook->assignRole('chef');

        $manager = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000003',
        ]);
        $manager->assignRole('manager');

        $response = $this->actingAs($admin)
            ->from(route('settings', ['tab' => 'users']))
            ->post(route('admin.users.communication.send'), [
                'channel' => 'sms',
                'message' => 'Service window tonight at 10pm.',
                'audience' => 'all',
            ]);

        $response->assertRedirect(route('settings', ['tab' => 'users']));
        $response->assertSessionHas('success');

        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            return $request['Body'] === 'Service window tonight at 10pm.'
                && str_starts_with($request['To'], '+255700000');
        });
    }

    #[Test]
    public function admin_can_broadcast_whatsapp_message(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response([], 201)]);

        $entity = $this->createEntityWithCommunicationSettings();

        $admin = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000010',
        ]);
        $admin->assignRole('admin');

        $recipient = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000011',
        ]);
        $recipient->assignRole('chef');

        $response = $this->actingAs($admin)
            ->post(route('admin.users.communication.send'), [
                'channel' => 'whatsapp',
                'message' => 'Team meeting moved to 3 PM.',
                'audience' => 'all',
            ]);

        $response->assertRedirect(route('settings', ['tab' => 'users']));
        $response->assertSessionHas('success');

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request['Body'] === 'Team meeting moved to 3 PM.'
                && str_starts_with($request['From'], 'whatsapp:')
                && str_starts_with($request['To'], 'whatsapp:+255');
        });
    }

    #[Test]
    public function admin_can_target_selected_recipients(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response([], 201)]);

        $entity = $this->createEntityWithCommunicationSettings();

        $admin = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000020',
        ]);
        $admin->assignRole('admin');

        $first = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000021',
        ]);

        $second = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000022',
        ]);

        $ignored = User::factory()->create([
            'entity_id' => $entity->id,
            'phone' => '255700000099',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.communication.send'), [
                'channel' => 'sms',
                'message' => 'Kitchen huddle in 10 minutes.',
                'audience' => 'selected',
                'recipients' => [$first->id, $second->id],
            ]);

        $response->assertRedirect(route('settings', ['tab' => 'users']));
        $response->assertSessionHas('success');

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request['Body'] === 'Kitchen huddle in 10 minutes.'
                && in_array($request['To'], ['+255700000021', '+255700000022'], true);
        });
    }

    private function createEntityWithCommunicationSettings(): Entity
    {
        $entity = Entity::create([
            'name' => 'Test Restaurant',
            'slug' => Str::slug('test-' . Str::uuid()),
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        EntityIntegrationSetting::create([
            'entity_id' => $entity->id,
            'twilio_account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'twilio_auth_token' => 'secret-token',
            'twilio_sms_number' => '+15005550006',
            'twilio_sms_enabled' => true,
            'twilio_whatsapp_number' => '+15005550006',
            'twilio_whatsapp_enabled' => true,
        ]);

        EntityNotificationSetting::create([
            'entity_id' => $entity->id,
            'notify_requisitions' => false,
            'notify_expenses' => false,
            'notify_purchase_orders' => true,
            'notify_payroll' => false,
            'notify_email_daily' => false,
            'sms_enabled' => true,
            'whatsapp_enabled' => true,
            'sms_provider' => 'twilio',
            'notification_channels' => [],
            'purchase_order_notification_emails' => [],
            'purchase_order_notification_phones' => [],
            'requisition_submitted_notification_phones' => [
                'chef' => [],
                'purchaser' => [],
                'manager' => [],
            ],
            'requisition_submitted_templates' => [],
            'requisition_approved_notification_phones' => [
                'chef' => [],
                'purchaser' => [],
                'manager' => [],
            ],
            'requisition_approved_templates' => [],
            'user_onboarding_sms_enabled' => true,
            'user_onboarding_sms_template' => null,
        ]);

        return $entity;
    }
}
