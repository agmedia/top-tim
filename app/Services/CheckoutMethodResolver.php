<?php

namespace App\Services;

use App\Models\Front\Checkout\GeoZone;
use App\Models\Front\Checkout\PaymentMethod;
use App\Models\Front\Checkout\ShippingMethod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CheckoutMethodResolver
{
    public function shipping(string $state, array $cart = []): Collection
    {
        $zone = $this->zone($state);

        return $zone === null ? collect() : (new ShippingMethod())->findGeo($zone)->checkCart($cart)->resolve();
    }

    public function payments(string $state, string $shipping, array $cart = []): Collection
    {
        $zone = $this->zone($state);

        return $zone === null ? collect() : (new PaymentMethod())->findGeo($zone)
            ->checkShipping($shipping)->checkCart($cart)->resolve();
    }

    public function resolve(string $state, string $shippingCode, string $paymentCode, array $cart = []): array
    {
        $shipping = $this->shipping($state, $cart)->firstWhere('code', $shippingCode);
        if (! $shipping) {
            throw ValidationException::withMessages(['shipping' => 'Odaberite dostupnu dostavu za navedenu državu.']);
        }

        $payment = $this->payments($state, $shippingCode, $cart)->firstWhere('code', $paymentCode);
        if (! $payment) {
            throw ValidationException::withMessages(['payment' => 'Odaberite dostupan način plaćanja.']);
        }

        return [$shipping, $payment];
    }

    private function zone(string $state): ?int
    {
        $state = $state === 'Croatia' ? 'Hrvatska' : $state;
        if ($state === '') {
            return null;
        }
        $zone = (new GeoZone())->findState($state);

        return isset($zone->id) ? (int) $zone->id : null;
    }
}
