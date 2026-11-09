<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        ResetPassword::toMailUsing(function (User $user, string $token) {
            return (new MailMessage())->subject('Reset Password')->view('emails.forget-password', [
                'token' => $token,
                'email' => $user->email,
            ]);
        });

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->input('email'))->first();

            return $user && $user->isActive() && Hash::check((string) $request->input('password'), $user->password)
                ? $user : null;
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(Str::lower((string) $request->email).'|'.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('password-email', function (Request $request) {
            return [
                Limit::perMinute(10)->by('password-email:ip:'.$request->ip()),
                Limit::perMinute(3)->by('password-email:email:'.hash('sha256', Str::lower((string) $request->email))),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by('password-reset:'.$request->ip());
        });
    }
}
