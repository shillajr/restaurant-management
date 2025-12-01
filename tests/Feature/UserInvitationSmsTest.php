<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityIntegrationSetting;
use App\Models\EntityNotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserInvitationSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chef', 'guard_name' => 'web']);
    }

    #[Test]
    public function it_sends_onboarding_sms_when_enabled(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response([], 201)]);

        $entity = $this->createEntityWithSmsSettings(onboardingEnabled: true, template: 'Welcome {user_name}! Login with {login_email} / {temporary_password} via {login_url}.');

        $admin = User::factory()->create([
            'entity_id' => $entity->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $invitePassword = 'TempPass123!';
        $invitePhone = '+255757044821';
        $sanitizedPhone = preg_replace('/\D+/', '', $invitePhone);
        $expectedLoginEmail = sprintf('%s@users.local', $sanitizedPhone);

        $response = $this->actingAs($admin)->post(route('admin.users.invite'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => $invitePhone,
            'password' => $invitePassword,
            'password_confirmation' => $invitePassword,
            'roles' => ['chef'],
        ]);

        $response->assertRedirect(route('settings', ['tab' => 'users']));

        $invitedUser = User::where('phone', $sanitizedPhone)->first();
        $this->assertNotNull($invitedUser);
        $this->assertEquals($expectedLoginEmail, $invitedUser->email);

        Http::assertSentCount(1);

        Http::assertSent(function ($request) use ($sanitizedPhone, $expectedLoginEmail, $invitePassword) {
            return $request['To'] === '+' . $sanitizedPhone
                && Str::contains($request['Body'], $expectedLoginEmail)
                && Str::contains($request['Body'], $invitePassword);
        });
    }

    #[Test]
    public function it_skips_onboarding_sms_when_disabled(): void
    {
        Http::fake();

        $entity = $this->createEntityWithSmsSettings(onboardingEnabled: false);

        $admin = User::factory()->create([
            'entity_id' => $entity->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.invite'), [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '+255757088990',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'roles' => ['chef'],
        ]);

        $response->assertRedirect(route('settings', ['tab' => 'users']));

        Http::assertNothingSent();
    }

    protected function createEntityWithSmsSettings(bool $smsEnabled = true, bool $onboardingEnabled = true, ?string $template = null): Entity
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
            'twilio_auth_token' => 'test-token',
            'twilio_sms_number' => '+15005550006',
            'twilio_sms_enabled' => true,
        ]);

        EntityNotificationSetting::create([
            'entity_id' => $entity->id,
            'notify_requisitions' => false,
            'notify_expenses' => false,
            'notify_purchase_orders' => false,
            'notify_payroll' => false,
            'notify_email_daily' => false,
            'sms_enabled' => $smsEnabled,
            'whatsapp_enabled' => false,
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
            'user_onboarding_sms_enabled' => $onboardingEnabled,
            'user_onboarding_sms_template' => $template,
        ]);

        return $entity;
    }
}
