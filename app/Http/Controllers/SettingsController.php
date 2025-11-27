<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\EntityGeneralSetting;
use App\Models\EntityIntegrationSetting;
use App\Models\EntityNotificationSetting;
use App\Models\EntityProfileSetting;
use App\Models\EntitySecuritySetting;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $entity = $this->resolveEntity($request->user());

        $general = EntityGeneralSetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'timezone' => $entity->timezone ?: 'America/Los_Angeles',
                'currency' => $entity->currency ?: 'USD',
                'date_format' => 'm/d/Y',
                'language' => 'en',
            ]
        );

        $profile = EntityProfileSetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'restaurant_name' => $entity->name,
                'email' => $entity->contact_email,
                'phone' => $entity->contact_phone,
            ]
        );

        $notifications = EntityNotificationSetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'notify_requisitions' => true,
                'notify_expenses' => true,
                'notify_purchase_orders' => true,
                'notify_payroll' => false,
                'notify_email_daily' => false,
                'sms_enabled' => false,
                'whatsapp_enabled' => false,
                'sms_provider' => 'twilio',
            ]
        );

        $integrations = EntityIntegrationSetting::firstOrCreate(
            ['entity_id' => $entity->id]
        );

        $security = EntitySecuritySetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'two_factor_enabled' => false,
                'session_timeout_enabled' => false,
                'session_timeout_minutes' => 30,
                'password_expiry_enabled' => false,
                'password_expiry_days' => 90,
            ]
        );

        $items = Item::orderBy('name')->get();
        $users = User::query()
            ->when($entity->exists, fn ($query) => $query->where('entity_id', $entity->id))
            ->with('roles')
            ->get();

        $activeTab = session('activeTab', $request->query('tab', 'general'));

        return view('settings.index', [
            'entity' => $entity,
            'generalSettings' => $general,
            'profileSettings' => $profile,
            'notificationSettings' => $notifications,
            'integrationSettings' => $integrations,
            'securitySettings' => $security,
            'items' => $items,
            'users' => $users,
            'activeTab' => $activeTab,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $entity = $this->resolveEntity($request->user());
        $tab = $request->input('active_tab', 'general');

        $handler = match ($tab) {
            'general' => fn () => $this->updateGeneralSettings($request, $entity),
            'restaurant' => fn () => $this->updateProfileSettings($request, $entity),
            'notifications' => fn () => $this->updateNotificationSettings($request, $entity),
            'integration' => fn () => $this->updateIntegrationSettings($request, $entity),
            'security' => fn () => $this->updateSecuritySettings($request, $entity),
            default => fn () => null,
        };

        $handler();

        return redirect()
            ->route('settings')
            ->with('activeTab', $tab)
            ->with('success', 'Settings updated successfully.');
    }

    protected function resolveEntity(User $user): Entity
    {
        $user->loadMissing('entity');

        if ($user->entity) {
            return $user->entity;
        }

        $entity = Entity::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => config('app.name', 'RMS Default'),
                'timezone' => config('app.timezone', 'America/Los_Angeles'),
                'currency' => 'USD',
            ]
        );

        if (!$user->entity_id) {
            $user->entity()->associate($entity);
            $user->save();
        }

        return $entity;
    }

    protected function updateGeneralSettings(Request $request, Entity $entity): void
    {
        $data = $request->validate([
            'timezone' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'date_format' => ['required', 'string', 'max:20'],
            'language' => ['required', 'string', 'max:5'],
        ]);

        EntityGeneralSetting::updateOrCreate(
            ['entity_id' => $entity->id],
            $data
        );

        $entity->update([
            'timezone' => $data['timezone'],
            'currency' => $data['currency'],
        ]);
    }

    protected function updateProfileSettings(Request $request, Entity $entity): void
    {
        $data = $request->validate([
            'restaurant_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:25'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        EntityProfileSetting::updateOrCreate(
            ['entity_id' => $entity->id],
            $data
        );

        $entity->update([
            'name' => $data['restaurant_name'] ?? $entity->name,
            'contact_email' => $data['email'] ?? $entity->contact_email,
            'contact_phone' => $data['phone'] ?? $entity->contact_phone,
        ]);
    }

    protected function updateNotificationSettings(Request $request, Entity $entity): void
    {
        $data = [
            'notify_requisitions' => $request->boolean('notify_requisitions'),
            'notify_expenses' => $request->boolean('notify_expenses'),
            'notify_purchase_orders' => $request->boolean('notify_purchase_orders'),
            'notify_payroll' => $request->boolean('notify_payroll'),
            'notify_email_daily' => $request->boolean('notify_email_daily'),
            'sms_enabled' => $request->boolean('sms_enabled'),
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'sms_provider' => 'twilio',
            'notification_channels' => array_values(array_filter([
                $request->boolean('notify_requisitions') ? 'requisitions' : null,
                $request->boolean('notify_expenses') ? 'expenses' : null,
                $request->boolean('notify_purchase_orders') ? 'purchase_orders' : null,
                $request->boolean('notify_payroll') ? 'payroll' : null,
            ])),
        ];

        EntityNotificationSetting::updateOrCreate(
            ['entity_id' => $entity->id],
            $data
        );
    }

    protected function updateIntegrationSettings(Request $request, Entity $entity): void
    {
        $data = $request->validate([
            'loyverse_api_key' => ['nullable', 'string'],
            'twilio_account_sid' => ['nullable', 'string'],
            'twilio_auth_token' => ['nullable', 'string'],
            'twilio_sms_number' => ['nullable', 'string'],
            'twilio_whatsapp_number' => ['nullable', 'string'],
            'smtp_host' => ['nullable', 'string'],
            'smtp_port' => ['nullable', 'string'],
            'smtp_username' => ['nullable', 'string'],
            'smtp_password' => ['nullable', 'string'],
            'smtp_encryption' => ['nullable', 'string'],
        ]);

        EntityIntegrationSetting::updateOrCreate(
            ['entity_id' => $entity->id],
            [
                'loyverse_api_key' => $data['loyverse_api_key'] ?? null,
                'loyverse_auto_sync' => $request->boolean('loyverse_auto_sync'),
                'twilio_account_sid' => $data['twilio_account_sid'] ?? null,
                'twilio_auth_token' => $data['twilio_auth_token'] ?? null,
                'twilio_sms_number' => $data['twilio_sms_number'] ?? null,
                'twilio_whatsapp_number' => $data['twilio_whatsapp_number'] ?? null,
                'twilio_whatsapp_enabled' => $request->boolean('twilio_whatsapp_enabled'),
                'twilio_sms_enabled' => $request->boolean('twilio_sms_enabled'),
                'smtp_host' => $data['smtp_host'] ?? null,
                'smtp_port' => $data['smtp_port'] ?? null,
                'smtp_username' => $data['smtp_username'] ?? null,
                'smtp_password' => $data['smtp_password'] ?? null,
                'smtp_encryption' => $data['smtp_encryption'] ?? null,
            ]
        );
    }

    protected function updateSecuritySettings(Request $request, Entity $entity): void
    {
        EntitySecuritySetting::updateOrCreate(
            ['entity_id' => $entity->id],
            [
                'two_factor_enabled' => $request->boolean('two_factor'),
                'session_timeout_enabled' => $request->boolean('session_timeout'),
                'session_timeout_minutes' => 30,
                'ip_whitelist_enabled' => $request->boolean('ip_whitelist_enabled'),
                'ip_whitelist' => null,
                'password_expiry_enabled' => $request->boolean('password_expiry_enable'),
                'password_expiry_days' => 90,
            ]
        );
    }
}
