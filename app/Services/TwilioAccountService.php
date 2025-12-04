<?php

namespace App\Services;

use App\Exceptions\Twilio\TwilioBalanceFetchFailed;
use App\Exceptions\Twilio\TwilioCredentialsMissing;
use App\Models\Entity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TwilioAccountService
{
    private const BALANCE_CACHE_TTL_MINUTES = 5;

    /**
     * Fetch the Twilio account balance for the given entity.
     *
     * @return array{balance: string, currency: string}
     */
    public function fetchBalanceForEntity(Entity $entity): array
    {
        $entity->loadMissing('integrationSettings');

        $integration = $entity->integrationSettings;

        if (! $integration || ! $integration->twilio_account_sid || ! $integration->twilio_auth_token) {
            throw new TwilioCredentialsMissing('Twilio credentials are missing for this entity.');
        }

        $accountSid = $integration->twilio_account_sid;
        $authToken = $integration->twilio_auth_token;
        $cacheKey = $this->cacheKey($entity->id);

        return Cache::remember($cacheKey, now()->addMinutes(self::BALANCE_CACHE_TTL_MINUTES), function () use ($entity, $accountSid, $authToken) {
            try {
                $response = Http::withBasicAuth($accountSid, $authToken)
                    ->timeout(10)
                    ->get("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Balance.json");
            } catch (Throwable $exception) {
                Log::warning('Unable to reach Twilio balance endpoint.', [
                    'entity_id' => $entity->id,
                    'error' => $exception->getMessage(),
                ]);

                throw new TwilioBalanceFetchFailed('Unable to connect to Twilio: ' . $exception->getMessage(), previous: $exception);
            }

            if ($response->failed()) {
                Log::warning('Twilio balance API returned an error.', [
                    'entity_id' => $entity->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $message = $response->json('message') ?? 'Unexpected response from Twilio.';

                throw new TwilioBalanceFetchFailed('Twilio error: ' . $message);
            }

            $balance = $response->json('balance');
            $currency = $response->json('currency');

            if ($balance === null || $currency === null) {
                throw new TwilioBalanceFetchFailed('Twilio response is missing balance information.');
            }

            return [
                'balance' => (string) $balance,
                'currency' => (string) $currency,
            ];
        });
    }

    public function forgetCachedBalance(Entity $entity): void
    {
        Cache::forget($this->cacheKey($entity->id));
    }

    private function cacheKey(int $entityId): string
    {
        return "twilio:balance:entity:{$entityId}";
    }
}
