<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
public function update(ProfileUpdateRequest $request): RedirectResponse
{
    // Fill the user with the validated data, ensuring first_name and last_name are included
    $user = $request->user();
    $user->fill([
        'first_name' => $request->input('first_name'),
        'last_name' => $request->input('last_name'),
        'email' => $request->input('email'),
    ]);

    if ($user->isDirty('email')) {
        $user->email_verified_at = null;  // Reset email verification status if email changed
    }

    $user->save();  // Save updated user data

    return Redirect::route('profile.edit')->with('status', 'profile-updated');
}


public function updateEmail(Request $request): RedirectResponse
{
    $user = $request->user();

    $validated = $request->validate([
        'email' => [
            'required',
            'string',
            'email',
            'max:255',
            
        ],
    ]);

    // Only update the email field
    $user->email = $validated['email'];

    // If you are NOT using email verification, this is enough:
    $user->save();

    // If later you decide to use email verification, you can do:
    // $user->email_verified_at = null;
    // $user->save();
    // $user->sendEmailVerificationNotification();

    return Redirect::route('profile.edit')->with('status', 'email-updated');
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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
