<?php

namespace App\Actions\Fortify;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    use PasswordValidationRules;

    public function showForgetPasswordForm()
    {
        return view('auth.forgetPassword');
    }

    public function submitForgetPasswordForm(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $user = User::where('email', $validated['email'])->first();

        if ($user && $user->isActive()) {
            Password::sendResetLink(['email' => $validated['email']]);
        }

        return back()->with('status', 'Ako postoji aktivan račun s tom adresom, poslat ćemo poveznicu za resetiranje lozinke.');
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        return view('auth.forgetPasswordResetLink', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function submitResetPasswordForm(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ]);

        $record = DB::table(config('auth.passwords.users.table'))->where('email', $validated['email'])->first();
        $user = User::where('email', $validated['email'])->first();
        // Old custom reset rows stored raw tokens. They must never be accepted as broker hashes.
        if (! $record || password_get_info((string) $record->token)['algoName'] === 'unknown'
            || ! $user || ! $user->isActive()) {
            return $this->invalidToken($request);
        }

        $status = Password::reset($validated, function (User $user, string $password) {
            app(ResetUserPassword::class)->reset($user, [
                'password' => $password,
                'password_confirmation' => $password,
            ]);
            $user->forceFill(['remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return $this->invalidToken($request);
        }

        return redirect()->route('login')->with('status', 'Vaša lozinka je promijenjena!');
    }

    private function invalidToken(Request $request)
    {
        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Poveznica za resetiranje nije valjana ili je istekla. Zatražite novu poveznicu.',
        ]);
    }
}
