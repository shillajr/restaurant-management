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
use App\Models\ItemCategory;
use App\Models\Vendor;
use App\Models\User;
use App\Services\SmsNotificationService;
use App\Support\Currency;
use App\Support\Localization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $entity = $this->resolveEntity($request->user());

        $general = EntityGeneralSetting::firstOrCreate(
            ['entity_id' => $entity->id],
            [
                'timezone' => $entity->timezone ?: config('app.timezone'),
                'currency' => $entity->currency ?: Currency::defaultCode(),
                'date_format' => 'm/d/Y',
                'language' => Localization::default(),
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
                'requisition_submitted_notification_phones' => array_fill_keys(array_keys($this->requisitionNotificationRoles()), []),
                'requisition_submitted_templates' => $this->defaultRequisitionSubmittedTemplates(),
                'requisition_approved_notification_phones' => array_fill_keys(array_keys($this->requisitionNotificationRoles()), []),
                'requisition_approved_templates' => $this->defaultRequisitionApprovedTemplates(),
                'user_onboarding_sms_enabled' => false,
                'user_onboarding_sms_template' => SmsNotificationService::DEFAULT_USER_ONBOARDING_TEMPLATE,
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
        $vendors = Vendor::orderBy('name')->get();
        $vendorStats = [
            'total' => $vendors->count(),
            'active' => $vendors->where('is_active', true)->count(),
            'inactive' => $vendors->where('is_active', false)->count(),
        ];
        $editingVendor = null;
        if ($request->filled('edit_vendor')) {
            $editingVendor = $vendors->firstWhere('id', (int) $request->query('edit_vendor'));
        }
        $categories = ItemCategory::orderBy('name')->get();

        $editingItem = null;
        if ($request->filled('edit_item')) {
            $editingItem = $items->firstWhere('id', (int) $request->query('edit_item'));
        }

        $users = User::query()
            ->when($entity->exists, fn ($query) => $query->where('entity_id', $entity->id))
            ->with(['roles', 'entity'])
            ->orderBy('name')
            ->get();

        $roles = Role::orderBy('name')->get();

        $availableBusinessSections = ['general', 'restaurant', 'notifications'];
        $availableProductSections = ['vendors', 'items'];

        $activeTab = session('activeTab', $request->query('tab')) ?? 'business';
        $businessSection = session('businessSection', $request->query('business_section', 'general'));
        $productSection = session('productSection', $request->query('product_section', 'vendors'));

        if (! in_array($businessSection, $availableBusinessSections, true)) {
            $businessSection = 'general';
        }

        if (! in_array($productSection, $availableProductSections, true)) {
            $productSection = 'vendors';
        }

        if (in_array($activeTab, $availableBusinessSections, true)) {
            $businessSection = $activeTab;
            $activeTab = 'business';
        }

        if (in_array($activeTab, $availableProductSections, true)) {
            $productSection = $activeTab;
            $activeTab = 'products';
        }

        if (! in_array($activeTab, ['business', 'products', 'integration', 'security', 'users'], true)) {
            $activeTab = 'business';
        }

        $unitOptions = [
            'piece',
            'kg',
            'liters',
            'pack',
            'box',
            'tray',
            'crate',
        ];

        return view('settings.index', [
            'entity' => $entity,
            'generalSettings' => $general,
            'profileSettings' => $profile,
            'notificationSettings' => $notifications,
            'integrationSettings' => $integrations,
            'securitySettings' => $security,
            'items' => $items,
            'itemCategories' => $categories,
            'vendors' => $vendors,
            'vendorStats' => $vendorStats,
            'editingVendor' => $editingVendor,
            'users' => $users,
            'roles' => $roles,
            'activeTab' => $activeTab,
            'businessSection' => $businessSection,
            'productSection' => $productSection,
            'editingItem' => $editingItem,
            'supportedCurrencies' => Currency::all(),
            'supportedLocales' => Localization::all(),
            'unitOptions' => $unitOptions,
            'requisitionNotificationRoles' => $this->requisitionNotificationRoles(),
            'defaultRequisitionTemplates' => [
                'submitted' => $this->defaultRequisitionSubmittedTemplates(),
                'approved' => $this->defaultRequisitionApprovedTemplates(),
            ],
            'requisitionTemplatePlaceholders' => $this->requisitionTemplatePlaceholders(),
            'userOnboardingDefaultTemplate' => SmsNotificationService::DEFAULT_USER_ONBOARDING_TEMPLATE,
            'userOnboardingPlaceholders' => SmsNotificationService::USER_ONBOARDING_PLACEHOLDERS,
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

        return $this->redirectForTab($tab);
    }

    protected function redirectForTab(string $tab): RedirectResponse
    {
        $success = __('settings.updated_success');

        if (in_array($tab, ['general', 'restaurant', 'notifications'], true)) {
            return redirect()
                ->route('settings')
                ->with('activeTab', 'business')
                ->with('businessSection', $tab)
                ->with('success', $success);
        }

        return redirect()
            ->route('settings')
            ->with('activeTab', $tab)
            ->with('success', $success);
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
                'timezone' => config('app.timezone'),
                'currency' => Currency::defaultCode(),
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
            'currency' => ['required', 'string', 'max:10', Rule::in(array_keys(Currency::all()))],
            'date_format' => ['required', 'string', 'max:20'],
            'language' => ['required', 'string', 'max:5', Rule::in(Localization::codes())],
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
        $emailsInput = $request->input('purchase_order_notification_emails');
        $phonesInput = $request->input('purchase_order_notification_phones');
        $submittedPhonesInput = (array) $request->input('requisition_submitted_phones', []);
        $approvedPhonesInput = (array) $request->input('requisition_approved_phones', []);
        $submittedTemplateInput = (array) $request->input('requisition_submitted_templates', []);
        $approvedTemplateInput = (array) $request->input('requisition_approved_templates', []);
        $userOnboardingTemplateInput = trim((string) $request->input('user_onboarding_sms_template', ''));

        $roles = array_keys($this->requisitionNotificationRoles());

        $submittedPhones = [];
        foreach ($roles as $role) {
            $submittedPhones[$role] = $this->normalizeContactList($submittedPhonesInput[$role] ?? '');
        }

        $approvedPhones = [];
        foreach ($roles as $role) {
            $approvedPhones[$role] = $this->normalizeContactList($approvedPhonesInput[$role] ?? '');
        }

        $submittedTemplates = [];
        foreach ($roles as $role) {
            $template = trim((string) ($submittedTemplateInput[$role] ?? ''));
            $submittedTemplates[$role] = $template !== '' ? $template : null;
        }

        $approvedTemplates = [];
        foreach ($roles as $role) {
            $template = trim((string) ($approvedTemplateInput[$role] ?? ''));
            $approvedTemplates[$role] = $template !== '' ? $template : null;
        }

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
            'purchase_order_notification_emails' => $this->normalizeContactList($emailsInput),
            'purchase_order_notification_phones' => $this->normalizeContactList($phonesInput),
            'requisition_submitted_notification_phones' => $submittedPhones,
            'requisition_submitted_templates' => $submittedTemplates,
            'requisition_approved_notification_phones' => $approvedPhones,
            'requisition_approved_templates' => $approvedTemplates,
            'user_onboarding_sms_enabled' => $request->boolean('user_onboarding_sms_enabled'),
            'user_onboarding_sms_template' => $userOnboardingTemplateInput !== '' ? $userOnboardingTemplateInput : null,
        ];

        EntityNotificationSetting::updateOrCreate(
            ['entity_id' => $entity->id],
            $data
        );
    }

    /**
     * Normalize a multi-line or comma-delimited contact string into an array.
     */
    protected function normalizeContactList($value): array
    {
        if (is_array($value)) {
            $raw = $value;
        } else {
            $raw = preg_split('/[\n,;]+/', (string) $value ?? '', -1, PREG_SPLIT_NO_EMPTY);
        }

        return collect($raw)
            ->map(fn ($entry) => trim((string) $entry))
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    protected function requisitionNotificationRoles(): array
    {
        return [
            'chef' => 'Chef',
            'purchaser' => 'Purchasing Officer',
            'manager' => 'Manager',
        ];
    }

    protected function defaultRequisitionSubmittedTemplates(): array
    {
        return [
            'chef' => 'Your requisition #{requisition_number} has been submitted successfully.',
            'purchaser' => 'Chef {actor_name} submitted requisition #{requisition_number} for {requested_for_date}. Items: {item_count}.',
            'manager' => 'New requisition #{requisition_number} from {chef_name} is pending review for {requested_for_date}.',
        ];
    }

    protected function defaultRequisitionApprovedTemplates(): array
    {
        return [
            'chef' => 'Manager {actor_name} approved your requisition #{requisition_number}.',
            'purchaser' => 'Requisition #{requisition_number} approved by {actor_name}. Total quantity: {total_quantity}.',
            'manager' => '{actor_name} approved requisition #{requisition_number}. Ready for purchase order conversion.',
        ];
    }

    protected function requisitionTemplatePlaceholders(): array
    {
        return [
            '{actor_name}' => 'User who performed the action (chef or manager).',
            '{chef_name}' => 'Name of the chef who created the requisition.',
            '{requisition_id}' => 'Numeric requisition identifier.',
            '{requisition_number}' => 'Formatted requisition number (e.g., REQ-0005).',
            '{requested_for_date}' => 'Requested fulfillment date.',
            '{submitted_at}' => 'Submission timestamp.',
            '{approved_at}' => 'Approval timestamp.',
            '{status}' => 'Current requisition status.',
            '{item_count}' => 'Number of items in the requisition.',
            '{total_quantity}' => 'Sum of item quantities.',
            '{note}' => 'Requisition note, if provided.',
            '{approval_notes}' => 'Notes supplied during approval (if any).',
            '{entity_name}' => 'Restaurant/entity name.',
            '{requisition_url}' => 'Direct link to view the requisition.',
        ];
    }
}
