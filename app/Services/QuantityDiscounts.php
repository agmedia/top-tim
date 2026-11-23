<?php

namespace App\Services;

use App\Models\CustomerDiscount;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuantityDiscounts
{
    public function rules(): Collection
    {
        // Reuse one query on a catalog page, never share customer prices between requests.
        $userId = Auth::id();
        if ($userId === null) {
            return $this->publicRules();
        }
        $key = 'quantity-discounts.' . $userId;
        if (!request()->attributes->has($key)) {
            $rules = CustomerDiscount::query()->where('active', true)
                ->where(function ($query) use ($userId) {
                    $query->where(fn ($public) => $public->where('applies_to_everyone', true)
                        ->whereNull('user_id')->whereNull('user_group_id'));
                    $query->orWhere(function ($private) use ($userId) {
                        $private->where('applies_to_everyone', false)->where(function ($target) use ($userId) {
                            $target->where('user_id', $userId)
                                ->orWhereIn('user_group_id', DB::table('user_details')
                                    ->join('user_group', 'user_group.id', '=', 'user_details.user_group_id')
                                    ->where('user_details.user_id', $userId)
                                    ->where('user_details.status', 1)->where('user_group.status', 1)
                                    ->select('user_group.id'));
                        });
                    });
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

    /** Public single-offer pricing, independent of staff/customer login and cart contents. */
    public function publicPrice(float $regular, float $campaign, int $brandId, int $quantity = 1): float
    {
        $percentage = (float) ($this->publicRules()->where('brand_id', $brandId)
            ->where('min_quantity', '<=', $quantity)->max('percentage') ?? 0);

        // Preserve existing four-decimal public snapshots when no public discount is applicable.
        return $percentage > 0
            ? max(0, min($campaign, round($regular * (1 - $percentage / 100), 2)))
            : $campaign;
    }

    private function publicRules(): Collection
    {
        $key = 'quantity-discounts.public';
        if (!request()->attributes->has($key)) {
            $available = Schema::hasTable('customer_discounts')
                && Schema::hasColumn('customer_discounts', 'applies_to_everyone');
            $rules = $available ? CustomerDiscount::query()->where('active', true)
                ->where('applies_to_everyone', true)->whereNull('user_id')->whereNull('user_group_id')->get()
                : collect();
            request()->attributes->set($key, $rules);
        }
        return request()->attributes->get($key);
    }

    public function cartQuantity(int $brandId): int
    {
        $session = session(config('session.cart'));
        if (!$session) {
            return 0;
        }

        return (int) Cart::session($session)->getContent()
            ->filter(fn ($item) => (int) ($item->associatedModel->brand_id ?? 0) === $brandId)
            ->sum('quantity');
    }
}
