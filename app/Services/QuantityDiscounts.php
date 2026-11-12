<?php

namespace App\Services;

use App\Models\CustomerDiscount;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuantityDiscounts
{
    public function rules(): Collection
    {
        if (!Auth::check()) {
            return collect();
        }

        // Reuse one query on a catalog page, never share customer prices between requests.
        $key = 'quantity-discounts.' . Auth::id();
        if (!request()->attributes->has($key)) {
            $rules = CustomerDiscount::query()->where('active', true)
                ->where(function ($query) {
                    $query->where('user_id', Auth::id())
                        ->orWhereIn('user_group_id', DB::table('user_details')
                            ->join('user_group', 'user_group.id', '=', 'user_details.user_group_id')
                            ->where('user_details.user_id', Auth::id())
                            ->where('user_details.status', 1)->where('user_group.status', 1)
                            ->select('user_group.id'));
                })->get();
            request()->attributes->set($key, $rules);
        }

        return request()->attributes->get($key);
    }

    public function tiers(int $brandId): array
    {
        return $this->rules()->where('brand_id', $brandId)->sortBy('min_quantity')
            ->groupBy('min_quantity')->map(fn ($rules, $minimum) => [
                'min_quantity' => (int) $minimum,
                'percentage' => (float) $rules->max('percentage'),
            ])->values()->all();
    }

    public function percentage(int $brandId, int $quantity): float
    {
        return (float) ($this->rules()->where('brand_id', $brandId)
            ->where('min_quantity', '<=', $quantity)->max('percentage') ?? 0);
    }

    public function price(float $regular, float $campaign, int $brandId, int $quantity): float
    {
        $personal = round($regular * (1 - $this->percentage($brandId, $quantity) / 100), 2);

        return round(max(0, min($regular, $campaign, $personal)), 2);
    }

    public function cartQuantity(int $brandId): int
    {
        $session = session(config('session.cart'));
        if (!$session || !Auth::check()) {
            return 0;
        }

        return (int) Cart::session($session)->getContent()
            ->filter(fn ($item) => (int) ($item->associatedModel->brand_id ?? 0) === $brandId)
            ->sum('quantity');
    }
}
