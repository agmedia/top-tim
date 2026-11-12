<?php

namespace App\Http\Controllers\Back\Settings;

use App\Http\Controllers\Controller;
use App\Models\Back\Settings\Settings;
use App\Services\BrevoNewsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class BrevoController extends Controller
{
    public function index()
    {
        $settings = BrevoNewsletter::settings();

        return view('back.settings.brevo', [
            'enabled' => $settings['enabled'] ?? false,
            'listId' => $settings['list_id'] ?? null,
            'hasKey' => !empty($settings['api_key']),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'list_id' => ['required_if:enabled,1', 'nullable', 'integer', 'min:1'],
            'brevo_api_key' => ['nullable', 'string', 'max:512'],
        ]);

        $key = BrevoNewsletter::settings()['api_key'] ?? null;
        if ($request->filled('brevo_api_key')) {
            $key = Crypt::encryptString($data['brevo_api_key']);
        }

        if ($request->boolean('enabled') && !$key) {
            return back()->withErrors(['brevo_api_key' => 'Unesite Brevo API ključ.']);
        }

        Settings::updateOrCreate(['code' => 'brevo', 'key' => 'newsletter'], [
            'json' => true,
            'value' => json_encode([
                'enabled' => $request->boolean('enabled'),
                'list_id' => $data['list_id'] ?? null,
                'api_key' => $key,
            ]),
        ]);

        return back()->with('success', 'Brevo postavke su spremljene.');
    }
}
