<?php

namespace App\Services;

use App\Models\Back\Settings\Settings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoNewsletter
{
    public static function settings(): array
    {
        return Settings::get('brevo', 'newsletter')->all();
    }

    public function subscribe(string $email): bool
    {
        $settings = self::settings();

        if (empty($settings['enabled'])) {
            return true;
        }

        try {
            $response = Http::withHeaders(['api-key' => Crypt::decryptString($settings['api_key'])])
                ->timeout(5)
                ->post('https://api.brevo.com/v3/contacts', [
                    'email' => $email,
                    'listIds' => [(int) $settings['list_id']],
                    'updateEnabled' => true,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Brevo newsletter subscription failed', ['status' => $response->status()]);
        } catch (\Exception $exception) {
            Log::warning('Brevo newsletter subscription failed', ['exception' => get_class($exception)]);
        }

        return false;
    }
}
