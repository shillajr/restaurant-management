<?php

namespace Tests\Feature;

use App\Models\ChefRequisition;
use App\Models\Entity;
use App\Models\EntityIntegrationSetting;
use App\Models\EntityNotificationSetting;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequisitionSmsNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'chef', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);
    }

    #[Test]
    public function it_sends_twilio_sms_when_a_requisition_is_submitted(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response([], 201)]);

        $entity = $this->createEntityWithTwilio();
        $this->seedNotificationSettings($entity);

        $chef = User::factory()->create([
            'entity_id' => $entity->id,
            'name' => 'Chef Alice',
            'email' => 'chef.alice@example.com',
        ]);
        $chef->assignRole('chef');

        $item = Item::factory()->create(['uom' => 'kg', 'price' => 12.50]);

        $payload = [
            'requested_for_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'items' => [[
                'item_id' => $item->id,
                'quantity' => 3,
                'uom' => $item->uom,
                'price' => $item->price,
            ]],
            'note' => 'Please stock soon',
        ];

        $response = $this->actingAs($chef)->post(route('chef-requisitions.store'), $payload);

        $response->assertRedirect(route('chef-requisitions.index'));

        $requisition = ChefRequisition::first();
        $this->assertNotNull($requisition);

        Http::assertSentCount(2);

        $sentTo = collect(Http::recorded())->map(fn ($interaction) => $interaction[0]['To'])->all();
        $this->assertEqualsCanonicalizing(['+15550001111', '+15550002222'], $sentTo);

        Http::assertSent(function ($request) use ($requisition) {
            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json'
                && $request['From'] === '+15005550006'
                && str_contains($request['Body'], '#REQ-' . str_pad((string) $requisition->id, 4, '0', STR_PAD_LEFT));
        });

        Http::assertSent(function ($request) {
            return str_contains($request['Body'], 'Chef Alice submitted requisition #');
        });
    }

    #[Test]
    public function it_sends_twilio_sms_with_custom_templates_on_approval(): void
    {
        Http::fake(['https://api.twilio.com/*' => Http::response([], 201)]);

        $entity = $this->createEntityWithTwilio();
        $this->seedNotificationSettings($entity, [
            'requisition_approved_templates' => [
                'chef' => null,
                'purchaser' => 'Requisition #{requisition_number} approved by {actor_name}. Notes: {approval_notes}.',
                'manager' => null,
            ],
        ]);

        $chef = User::factory()->create([
            'entity_id' => $entity->id,
            'name' => 'Chef Bruno',
        ]);
        $chef->assignRole('chef');

        $manager = User::factory()->create([
            'entity_id' => $entity->id,
            'name' => 'Manager Bob',
        ]);
        $manager->assignRole('manager');

        $item = Item::factory()->create(['uom' => 'case', 'price' => 34.25, 'name' => 'Tomatoes']);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => Carbon::now()->addDays(1),
            'items' => [[
                'item_id' => $item->id,
                'item' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => 5,
                'unit' => $item->uom,
                'uom' => $item->uom,
                'price' => $item->price,
                'defaultPrice' => $item->price,
                'priceEdited' => false,
                'originalPrice' => $item->price,
            ]],
            'note' => 'Urgent restock',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($manager)->post(route('chef-requisitions.approve', $requisition), [
            'approval_notes' => 'Restock quickly',
        ]);

        $response->assertRedirect(route('chef-requisitions.show', $requisition));

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            return $request['To'] === '+15550003333'
                && str_contains($request['Body'], 'Manager Bob approved your requisition');
        });

        Http::assertSent(function ($request) {
            return $request['To'] === '+15550004444'
                && str_contains($request['Body'], 'Notes: Restock quickly');
        });
    }

    #[Test]
    public function it_does_not_send_sms_when_sms_alerts_are_disabled(): void
    {
        Http::fake();

        $entity = $this->createEntityWithTwilio();
        $this->seedNotificationSettings($entity, [
            'sms_enabled' => false,
        ]);

        $chef = User::factory()->create([
            'entity_id' => $entity->id,
        ]);
        $chef->assignRole('chef');

        $item = Item::factory()->create(['price' => 10, 'uom' => 'pcs']);

        $payload = [
            'requested_for_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'items' => [[
                'item_id' => $item->id,
                'quantity' => 2,
                'uom' => $item->uom,
                'price' => $item->price,
            ]],
        ];

        $response = $this->actingAs($chef)->post(route('chef-requisitions.store'), $payload);

        $response->assertRedirect(route('chef-requisitions.index'));

        Http::assertNothingSent();
    }

    protected function createEntityWithTwilio(): Entity
    {
        $entity = Entity::create([
            'name' => 'Test Restaurant',
            'slug' => Str::slug('test-' . uniqid()),
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'contact_email' => 'ops@example.com',
            'contact_phone' => '+15550000000',
        ]);

        EntityIntegrationSetting::create([
            'entity_id' => $entity->id,
            'twilio_account_sid' => 'AC123',
            'twilio_auth_token' => 'super-secret-token',
            'twilio_sms_number' => '+15005550006',
            'twilio_sms_enabled' => true,
        ]);

        return $entity;
    }

    protected function seedNotificationSettings(Entity $entity, array $overrides = []): EntityNotificationSetting
    {
        $defaults = [
            'entity_id' => $entity->id,
            'notify_requisitions' => true,
            'notify_expenses' => false,
            'notify_purchase_orders' => false,
            'notify_payroll' => false,
            'notify_email_daily' => false,
            'sms_enabled' => true,
            'whatsapp_enabled' => false,
            'sms_provider' => 'twilio',
            'notification_channels' => ['requisitions'],
            'purchase_order_notification_emails' => [],
            'purchase_order_notification_phones' => [],
            'requisition_submitted_notification_phones' => [
                'chef' => ['+15550001111'],
                'purchaser' => ['+15550002222'],
                'manager' => [],
            ],
            'requisition_submitted_templates' => [
                'chef' => null,
                'purchaser' => null,
                'manager' => null,
            ],
            'requisition_approved_notification_phones' => [
                'chef' => ['+15550003333'],
                'purchaser' => ['+15550004444'],
                'manager' => [],
            ],
            'requisition_approved_templates' => [
                'chef' => null,
                'purchaser' => null,
                'manager' => null,
            ],
        ];

        return EntityNotificationSetting::create(array_replace_recursive($defaults, $overrides));
    }
}
