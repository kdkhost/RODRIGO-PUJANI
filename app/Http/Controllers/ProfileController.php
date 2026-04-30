<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'pageTitle' => 'Meu Perfil',
            'user' => $request->user(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        unset($validated['avatar']);
        $validated['address_state'] = filled($validated['address_state'] ?? null)
            ? strtoupper((string) $validated['address_state'])
            : null;

        $user->fill($validated);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = PublicUpload::store(
                $request->file('avatar'),
                'avatars',
                $user->avatar_path,
                $user->id,
            );
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return Redirect::route('profile.edit')
                ->with('error', 'A conta Super Admin é protegida e não pode ser excluída.');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
