<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected ?string $lastError = null;
    /**
     * Send a WhatsApp message using the entity's Twilio configuration.
     */
    public function sendPurchaseOrderMessage(Entity $entity, string $phoneNumber, string $message): bool
    {
        return $this->sendWhatsAppMessage($entity, $phoneNumber, $message, true);
    }

    /**
     * Send a direct WhatsApp message without workflow-specific gating.
     */
    public function sendDirectMessage(Entity $entity, string $phoneNumber, string $message): bool
    {
        return $this->sendWhatsAppMessage($entity, $phoneNumber, $message, false);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    protected function sendWhatsAppMessage(Entity $entity, string $phoneNumber, string $message, bool $requirePurchaseOrderToggle): bool
    {
        $this->lastError = null;

        $integration = $entity->integrationSettings;
        $notifications = $entity->notificationSettings;

        if (! $integration || ! $integration->twilio_whatsapp_enabled) {
            $this->lastError = 'Twilio WhatsApp is disabled. Enable it in Settings → Integrations.';
            return false;
        }

        if (! $notifications || ! $notifications->whatsapp_enabled) {
            $this->lastError = 'WhatsApp alerts are disabled under Settings → Notifications.';
            return false;
        }

        if ($requirePurchaseOrderToggle && ! $notifications->notify_purchase_orders) {
            $this->lastError = 'Purchase order notifications are disabled for WhatsApp.';
            return false;
        }

        if (! $integration->twilio_account_sid || ! $integration->twilio_auth_token || ! $integration->twilio_whatsapp_number) {
            Log::warning('Missing Twilio WhatsApp configuration for entity.', ['entity_id' => $entity->id]);
            $this->lastError = 'Missing Twilio WhatsApp credentials or sender number.';
            return false;
        }

        $from = $this->formatWhatsAppNumber($integration->twilio_whatsapp_number);
        $to = $this->formatWhatsAppNumber($phoneNumber);

        if (! $from || ! $to) {
            Log::warning('Invalid WhatsApp numbers provided.', [
                'entity_id' => $entity->id,
                'from' => $integration->twilio_whatsapp_number,
                'to' => $phoneNumber,
            ]);
            $this->lastError = 'Invalid WhatsApp number format. Numbers must include country code.';
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
            Log::warning('Failed to send WhatsApp notification.', [
                'entity_id' => $entity->id,
                'phone' => $phoneNumber,
                'error' => $exception->getMessage(),
            ]);
            $this->lastError = 'Failed to contact Twilio: ' . $exception->getMessage();

            return false;
        }

        if ($response->failed()) {
            Log::warning('Twilio WhatsApp API responded with an error.', [
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
     * Normalize a phone number to Twilio's expected whatsapp:+E164 format.
     */
    protected function formatWhatsAppNumber(string $number): ?string
    {
        $number = trim($number);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, 'whatsapp:')) {
            return $number;
        }

        $normalized = preg_replace('/\s+/', '', $number);

        if ($normalized === '') {
            return null;
        }

        if (! str_starts_with($normalized, '+')) {
            $normalized = '+' . ltrim($normalized, '+');
        }

        return 'whatsapp:' . $normalized;
    }
}
