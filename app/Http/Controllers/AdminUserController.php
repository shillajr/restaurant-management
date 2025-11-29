<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Invite a new user to the platform.
     */
    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('invite', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'send_reset' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'entity_id' => $request->user()->entity_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
        ]);

        $user->syncRoles($data['roles']);

        $message = 'User invited successfully.';

        if ($request->boolean('send_reset', true)) {
            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status === Password::RESET_LINK_SENT) {
                $message = 'Invitation email sent to ' . $user->email . '.';
            } else {
                $message = trans($status);
            }
        }

        return redirect()
            ->route('settings', ['tab' => 'users'])
            ->with('success', $message)
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
     * Resend an invitation email via password reset link.
     */
    public function resendInvite(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        $message = $status === Password::RESET_LINK_SENT
            ? 'Invitation email resent to ' . $user->email . '.'
            : trans($status);

        $flashBag = $status === Password::RESET_LINK_SENT ? 'success' : 'error';

        return redirect()
            ->route('settings', ['tab' => 'users'])
            ->with($flashBag, $message)
            ->with('activeTab', 'users');
    }
}
