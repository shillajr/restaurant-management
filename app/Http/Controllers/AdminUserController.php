<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsNotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    protected SmsNotificationService $smsNotifications;
    protected WhatsAppNotificationService $whatsAppNotifications;

    public function __construct(
        SmsNotificationService $smsNotifications,
        WhatsAppNotificationService $whatsAppNotifications
    )
    {
        $this->smsNotifications = $smsNotifications;
        $this->whatsAppNotifications = $whatsAppNotifications;
    }

    /**
     * Invite a new user to the platform.
     */
    public function invite(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => preg_replace('/\D+/', '', (string) $request->input('phone')),
        ]);

        $data = $request->validateWithBag('invite', [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:20', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
        $emailSeed = $data['phone'] !== '' ? $data['phone'] : Str::uuid()->toString();
        $placeholderEmail = sprintf('%s@users.local', $emailSeed); // satisfy unique email constraint without exposing email input

        $temporaryPassword = $data['password'];

        $user = User::create([
            'entity_id' => $request->user()->entity_id,
            'name' => $fullName,
            'email' => $placeholderEmail,
            'phone' => $data['phone'],
            'password' => Hash::make($temporaryPassword),
        ]);

        $user->syncRoles($data['roles']);

        $this->smsNotifications->sendUserOnboardingMessage($user, $temporaryPassword);

        return redirect()
            ->route('settings', ['tab' => 'users'])
            ->with('success', 'Employee added successfully.')
            ->with('activeTab', 'users');
    }

    /**
     * Broadcast a custom SMS or WhatsApp message to all users in the entity.
     */
    public function sendCommunication(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('communication', [
            'channel' => ['required', Rule::in(['sms', 'whatsapp'])],
            'message' => ['required', 'string', 'max:500'],
            'audience' => ['required', Rule::in(['all', 'selected'])],
            'recipients' => ['array'],
            'recipients.*' => ['integer'],
        ]);

        $entity = $request->user()->entity;

        if (! $entity) {
            return redirect()
                ->route('settings', ['tab' => 'users'])
                ->withErrors(['message' => 'Entity context missing. Please try again.'], 'communication')
                ->with('activeTab', 'users')
                ->withInput($data);
        }

        $notifications = $entity->notificationSettings;
        $integration = $entity->integrationSettings;

        if ($data['channel'] === 'sms') {
            if (! $notifications || ! $notifications->sms_enabled) {
                return redirect()
                    ->route('settings', ['tab' => 'users'])
                    ->withErrors(['channel' => 'Enable SMS alerts under Settings → Notifications before sending a broadcast.'], 'communication')
                    ->with('activeTab', 'users')
                    ->withInput($data);
            }

            if (! $integration || ! $integration->twilio_sms_enabled || ! $integration->twilio_sms_number) {
                return redirect()
                    ->route('settings', ['tab' => 'users'])
                    ->withErrors(['channel' => 'Provide a Twilio SMS number and enable the integration before broadcasting.'], 'communication')
                    ->with('activeTab', 'users')
                    ->withInput($data);
            }
        }

        if ($data['channel'] === 'whatsapp') {
            if (! $notifications || ! $notifications->whatsapp_enabled) {
                return redirect()
                    ->route('settings', ['tab' => 'users'])
                    ->withErrors(['channel' => 'Enable WhatsApp alerts under Settings → Notifications before sending a broadcast.'], 'communication')
                    ->with('activeTab', 'users')
                    ->withInput($data);
            }

            if (! $integration || ! $integration->twilio_whatsapp_enabled || ! $integration->twilio_whatsapp_number) {
                return redirect()
                    ->route('settings', ['tab' => 'users'])
                    ->withErrors(['channel' => 'Provide a Twilio WhatsApp number and enable the integration before broadcasting.'], 'communication')
                    ->with('activeTab', 'users')
                    ->withInput($data);
            }
        }

        $recipients = User::query()
            ->when(
                $data['audience'] === 'selected',
                fn ($query) => $query->whereIn('id', $this->sanitizeRecipientIds($data['recipients'] ?? [])),
                fn ($query) => $query
            )
            ->where('entity_id', $entity->id)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('phone')
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return redirect()
                ->route('settings', ['tab' => 'users'])
                ->withErrors(['message' => $data['audience'] === 'selected'
                    ? 'Selected users do not have valid phone numbers on file.'
                    : 'No users have a phone number on file.'
                ], 'communication')
                ->with('activeTab', 'users')
                ->withInput($data);
        }

        $sent = 0;
        $failed = 0;
        $firstError = null;

        foreach ($recipients as $phone) {
            $delivered = $data['channel'] === 'sms'
                ? $this->smsNotifications->sendDirectMessage($entity, $phone, $data['message'])
                : $this->whatsAppNotifications->sendDirectMessage($entity, $phone, $data['message']);

            if ($delivered) {
                $sent++;
            } else {
                $failed++;
                if (! $firstError) {
                    $firstError = $data['channel'] === 'sms'
                        ? $this->smsNotifications->getLastError()
                        : $this->whatsAppNotifications->getLastError();
                }
            }
        }

        if ($sent === 0) {
            $errorMessage = $firstError ?? 'Unable to send the message. Check your Twilio configuration and try again.';
            return redirect()
                ->route('settings', ['tab' => 'users'])
                ->withErrors(['message' => $errorMessage], 'communication')
                ->with('activeTab', 'users')
                ->withInput($data);
        }

        $feedback = $failed > 0
            ? sprintf(
                'Message sent to %d users, but %d deliveries failed%s.',
                $sent,
                $failed,
                $firstError ? sprintf(' (Example error: %s)', $firstError) : ''
            )
            : sprintf('Message sent to %d users.', $sent);

        return redirect()
            ->route('settings', ['tab' => 'users'])
            ->with($failed > 0 ? 'error' : 'success', $feedback)
            ->with('activeTab', 'users');
    }

    protected function sanitizeRecipientIds(?array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return collect($ids)
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
    /**
     * Update the primary phone number for an existing user.
     */
    public function updateContact(Request $request, User $user): RedirectResponse
    {
        $request->merge([
            'phone' => preg_replace('/\D+/', '', (string) $request->input('phone')),
        ]);

        $data = $request->validateWithBag('contact', [
            'phone' => ['required', 'string', 'min:7', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $updates = ['phone' => $data['phone']];

        if ($user->email && Str::endsWith($user->email, '@users.local')) {
            $updates['email'] = sprintf('%s@users.local', $data['phone']);
        }

        $user->forceFill($updates)->save();

        return redirect()
            ->route('settings', ['tab' => 'users'])
            ->with('success', 'Phone number updated successfully.')
            ->with('activeTab', 'users');
    }

    /**
     * Update the roles for an existing user.
     */
    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validateWithBag('roles', [
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $roles = collect($data['roles'] ?? [])->filter()->values();

        if ($user->id === $request->user()->id && !$roles->contains('admin')) {
            return redirect()
                ->back()
                ->withErrors(['roles' => 'You cannot remove your own admin role.'], 'roles')
                ->with('activeTab', 'users');
        }

        if (!$roles->contains('admin')) {
            $otherAdmins = User::role('admin')->where('id', '!=', $user->id)->count();
            if ($otherAdmins === 0) {
                return redirect()
                    ->back()
                    ->withErrors(['roles' => 'At least one admin must remain assigned.'], 'roles')
                    ->with('activeTab', 'users');
            }
        }

        $user->syncRoles($roles->all());

        return redirect()
            ->route('settings', ['tab' => 'users'])
            ->with('success', 'User roles updated successfully.')
            ->with('activeTab', 'users');
    }

    /**
     * Send a password reset link to the specified user.
     */
    public function resendInvite(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        $message = $status === Password::RESET_LINK_SENT
            ? 'Password reset link sent to ' . $user->email . '.'
            : trans($status);

        $flashBag = $status === Password::RESET_LINK_SENT ? 'success' : 'error';

        return redirect()
            ->route('settings', ['tab' => 'users'])
            ->with($flashBag, $message)
            ->with('activeTab', 'users');
    }
}
