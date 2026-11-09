<?php

namespace App\Services;

class GlsAvailability
{
    public function ensureAvailable(): void
    {
        abort_unless(config('services.gls.labels_enabled', false) && $this->supportsSoap(), 503,
            'GLS izrada naljepnica trenutačno nije dostupna.');
    }

    public function supportsSoap(): bool
    {
        return extension_loaded('soap') && class_exists(\SoapClient::class);
    }
}
