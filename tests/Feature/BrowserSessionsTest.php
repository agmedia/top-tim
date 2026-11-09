<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm;
use Tests\TestCase;

class BrowserSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_browser_sessions_can_be_logged_out()
    {
        config(['session.driver' => 'database']);
        $this->actingAs($user = User::factory()->create());
        DB::table('sessions')->insert([
            'id' => 'other-device', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0', 'payload' => '', 'last_activity' => time(),
        ]);

        $session = app('session')->driver('database');
        $session->start();
        app('request')->setLaravelSession($session);
        $form = app(LogoutOtherBrowserSessionsForm::class);
        $form->password = 'password';
        $form->confirmingLogout = true;
        $form->logoutOtherBrowserSessions(Auth::guard('web'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertFalse($form->confirmingLogout);
        $this->assertSame($user->fresh()->password, $session->get('password_hash_web'));
    }
}
