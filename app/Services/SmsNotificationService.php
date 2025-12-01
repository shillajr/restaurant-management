<?php

namespace App\Services;

use App\Models\ChefRequisition;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsNotificationService
{
    protected const REQUISITION_ROLES = ['chef', 'purchaser', 'manager'];

    public const DEFAULT_USER_ONBOARDING_TEMPLATE = 'Welcome to {entity_name}, {user_name}! Your login email is {login_email} and your temporary password is {temporary_password}. Sign in at {login_url}.';

    public const USER_ONBOARDING_PLACEHOLDERS = [
        '{user_name}' => 'Full name of the invited employee.',
        '{login_email}' => 'Auto-generated login email for the employee.',
        '{temporary_password}' => 'Temporary password entered during invitation.',
        '{entity_name}' => 'Name of the entity/restaurant.',
        '{login_url}' => 'Link to the login page.',
    ];

    protected ?string $lastError = null;

    /**
     * Send an SMS message using the entity's Twilio configuration.
     */
    public function sendPurchaseOrderMessage(Entity $entity, string $phoneNumber, string $message): bool
    {
        $notifications = $entity->notificationSettings;

        if (! $notifications || ! $notifications->notify_purchase_orders || ! $notifications->sms_enabled) {
            return false;
        }

        return $this->sendSms($entity, $phoneNumber, $message);
    }

    /**
     * Send a direct SMS message without tying it to a specific workflow.
     */
    public function sendDirectMessage(Entity $entity, string $phoneNumber, string $message): bool
    {
        return $this->sendSms($entity, $phoneNumber, $message);
    }

    /**
     * Send an onboarding SMS to a newly invited user.
     */
    public function sendUserOnboardingMessage(User $user, string $plainTextPassword): void
    {
        $entity = $user->entity;

        if (! $entity) {
            return;
        }

        $notifications = $entity->notificationSettings;

        if (! $notifications || ! $notifications->sms_enabled || ! ($notifications->user_onboarding_sms_enabled ?? false)) {
            return;
        }

        $phoneNumber = $user->phone;

        if (! $phoneNumber) {
            return;
        }

        $template = (string) ($notifications->user_onboarding_sms_template ?? '') ?: self::DEFAULT_USER_ONBOARDING_TEMPLATE;

        $context = [
            'user_name' => $user->name,
            'login_email' => $user->email,
            'temporary_password' => $plainTextPassword,
            'entity_name' => $entity->name ?? config('app.name'),
            'login_url' => route('login'),
        ];

        $message = $this->renderTemplate($template, $context);

        $this->sendSms($entity, $phoneNumber, $message);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Dispatch SMS alerts when a requisition is submitted.
     */
    public function sendRequisitionSubmittedNotifications(ChefRequisition $requisition, User $actor): void
    {
        $entity = $actor->entity ?? $requisition->chef?->entity;

        if (! $entity) {
            return;
        }

        $notifications = $entity->notificationSettings;

        if (! $notifications || ! $notifications->notify_requisitions || ! $notifications->sms_enabled) {
            return;
        }

        $phonesByRole = (array) ($notifications->requisition_submitted_notification_phones ?? []);
        $templates = (array) ($notifications->requisition_submitted_templates ?? []);

        $context = $this->buildRequisitionContext($requisition, $actor, ['approval_notes' => '']);
        $defaultTemplates = $this->defaultRequisitionSubmittedTemplates();

        $this->dispatchRequisitionMessages($entity, $phonesByRole, $templates, $defaultTemplates, $context);
    }

    /**
     * Dispatch SMS alerts when a requisition is approved.
     */
    public function sendRequisitionApprovedNotifications(ChefRequisition $requisition, User $actor, ?string $approvalNotes = null): void
    {
        $entity = $actor->entity ?? $requisition->chef?->entity;

        if (! $entity) {
            return;
        }

        $notifications = $entity->notificationSettings;

        if (! $notifications || ! $notifications->notify_requisitions || ! $notifications->sms_enabled) {
            return;
        }

        $phonesByRole = (array) ($notifications->requisition_approved_notification_phones ?? []);
        $templates = (array) ($notifications->requisition_approved_templates ?? []);

        $context = $this->buildRequisitionContext($requisition, $actor, [
            'approval_notes' => $approvalNotes ?? '',
        ]);
        $defaultTemplates = $this->defaultRequisitionApprovedTemplates();

        $this->dispatchRequisitionMessages($entity, $phonesByRole, $templates, $defaultTemplates, $context);
    }

    /**
     * Handle Twilio SMS transmission once event-level checks pass.
     */
    protected function sendSms(Entity $entity, string $phoneNumber, string $message): bool
    {
        $this->lastError = null;

        $integration = $entity->integrationSettings;

        if (! $integration || ! $integration->twilio_sms_enabled) {
            $this->lastError = 'Twilio SMS is disabled. Enable it under Settings → Integrations.';
            return false;
        }

        if (! $integration->twilio_account_sid || ! $integration->twilio_auth_token || ! $integration->twilio_sms_number) {
            Log::warning('Missing Twilio SMS configuration for entity.', ['entity_id' => $entity->id]);
            $this->lastError = 'Missing Twilio SMS credentials or sender number.';
            return false;
        }

        $from = $this->formatSmsNumber($integration->twilio_sms_number);
        $to = $this->formatSmsNumber($phoneNumber);

        if (! $from || ! $to) {
            Log::warning('Invalid SMS numbers provided.', [
                'entity_id' => $entity->id,
                'from' => $integration->twilio_sms_number,
                'to' => $phoneNumber,
            ]);
            $this->lastError = 'Invalid phone number format. Numbers should include country code (e.g. 2557…).';

            return false;
        }

        try {
            $response = Http::withBasicAuth($integration->twilio_account_sid, $integration->twilio_auth_token)
                ->asForm()
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$integration->twilio_account_sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to send SMS notification.', [
                'entity_id' => $entity->id,
                'phone' => $phoneNumber,
                'error' => $exception->getMessage(),
            ]);
            $this->lastError = 'Failed to contact Twilio: ' . $exception->getMessage();

            return false;
        }

        if ($response->failed()) {
            Log::warning('Twilio SMS API responded with an error.', [
                'entity_id' => $entity->id,
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $twilioMessage = $response->json('message');
            if (! $twilioMessage && $json = $response->json()) {
                $twilioMessage = $json['message'] ?? null;
            }
            $this->lastError = $twilioMessage
                ? sprintf('Twilio error: %s', $twilioMessage)
                : sprintf('Twilio error (status %d).', $response->status());

            return false;
        }

        return true;
    }

    /**
     * Deliver requisition notifications to configured contacts.
     */
    protected function dispatchRequisitionMessages(Entity $entity, array $phonesByRole, array $templates, array $defaultTemplates, array $context): void
    {
        foreach (self::REQUISITION_ROLES as $role) {
            $numbers = collect($phonesByRole[$role] ?? [])
                ->map(fn ($number) => trim((string) $number))
                ->filter()
                ->unique()
                ->values();

            if ($numbers->isEmpty()) {
                continue;
            }

            $template = $templates[$role] ?? null;

            if ($template === null || trim($template) === '') {
                $template = $defaultTemplates[$role] ?? null;
            }

            if (! $template) {
                continue;
            }

            $message = $this->renderTemplate($template, $context);

            foreach ($numbers as $phoneNumber) {
                $this->sendSms($entity, $phoneNumber, $message);
            }
        }
    }

    /**
     * Build the template context for requisition messaging.
     */
    protected function buildRequisitionContext(ChefRequisition $requisition, User $actor, array $extra = []): array
    {
        $requisition->loadMissing('chef.entity');

        $entity = $actor->entity ?? $requisition->chef?->entity;

        $items = is_array($requisition->items) ? $requisition->items : [];
        $itemCount = count($items);
        $totalQuantity = collect($items)->sum(function ($item) {
            if (is_array($item) && isset($item['quantity'])) {
                return (float) $item['quantity'];
            }

            return 0;
        });

        return [
            'actor_name' => $actor->name,
            'chef_name' => $requisition->chef?->name ?? '',
            'requisition_id' => (string) $requisition->id,
            'requisition_number' => sprintf('REQ-%04d', $requisition->id),
            'requested_for_date' => optional($requisition->requested_for_date)->format('M d, Y') ?? '',
            'submitted_at' => optional($requisition->created_at)->format('M d, Y h:i A') ?? '',
            'approved_at' => optional($requisition->checked_at)->format('M d, Y h:i A') ?? '',
            'status' => ucfirst((string) $requisition->status),
            'item_count' => (string) $itemCount,
            'total_quantity' => $this->formatQuantity($totalQuantity),
            'note' => (string) ($requisition->note ?? ''),
            'approval_notes' => (string) ($extra['approval_notes'] ?? ''),
            'entity_name' => $entity?->name ?? '',
            'requisition_url' => route('chef-requisitions.show', $requisition->id),
        ];
    }

    /**
     * Replace template placeholders with context values.
     */
    protected function renderTemplate(string $template, array $context): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{' . $key . '}'] = is_scalar($value) ? (string) $value : (string) json_encode($value);
        }

        return strtr($template, $replacements);
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

    protected function formatQuantity(float $quantity): string
    {
        if (floor($quantity) == $quantity) {
            return (string) (int) $quantity;
        }

        $formatted = number_format($quantity, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * Normalize SMS phone numbers to +E164 format.
     */
    protected function formatSmsNumber(string $number): ?string
    {
        $number = trim($number);

        if ($number === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $number);

        if ($normalized === '') {
            return null;
        }

        if (! str_starts_with($normalized, '+')) {
            $normalized = '+' . ltrim($normalized, '+0');
        }

        $normalized = preg_replace('/[^\d+]/', '', $normalized);

        if (! Str::startsWith($normalized, '+')) {
            $normalized = '+' . ltrim($normalized, '+');
        }

        return $normalized;
    }
}
