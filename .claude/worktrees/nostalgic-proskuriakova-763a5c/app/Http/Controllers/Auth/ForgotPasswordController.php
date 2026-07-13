<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('users.forgot_password');
    }

    public function showResetForm($token, $email){
        return view('auth.passwords.email', compact('token', 'email'));
    }

    /**
     * Handle a password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Validate the email address
        $request->validate(['email' => 'required|email']);

        // Attempt to send the password reset email
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Handle the response
        if ($status === Password::RESET_LINK_SENT) {
            //dd('Je tombe ici');
            return redirect()->back()->with('success', 'Nous avons envoyer une email de recuperation de mot de passe');
        }

        // Handle the case where the email was not sent
        throw ValidationException::withMessages([
            'error' => [trans($status)],
        ]);
    }

    public function reset(Request $request){
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:64|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Mot de passe réinitialisé. Connectez-vous.');
        }

        return back()->with('error', trans($status));
    }
}
