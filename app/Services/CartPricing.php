<?php

namespace App\Services;

use App\Helpers\Currency;
use App\Helpers\Special;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\ProductOption;
use Darryldecode\Cart\CartCondition;

class CartPricing
{
    public function refresh($cart): void
    {
        $items = $cart->getContent();
        if ($items->isEmpty()) {
            return;
        }

        $products = Product::whereIn('id', $items->map(fn ($item) => $item->associatedModel->id))
            ->with('translation')->get()->keyBy('id');
        $options = ProductOption::whereIn('id', $items->map(fn ($item) => $item->attributes['options']['option']['id'] ?? null)
            ->filter())->get()->keyBy('id');
        $quantities = [];
        foreach ($items as $item) {
            $product = $products->get($item->associatedModel->id);
            if ($product) {
                $quantities[$product->brand_id] = ($quantities[$product->brand_id] ?? 0) + (int) $item->quantity;
            }
        }

        $discounts = app(QuantityDiscounts::class);
        foreach ($items as $item) {
            $product = $products->get($item->associatedModel->id);
            if (!$product) {
                $cart->remove($item->id);
                continue;
            }
            $option = $options->get($item->attributes['options']['option']['id'] ?? null);
            $optionPrice = $option && (int) $option->product_id === (int) $product->id ? (float) $option->price : 0;
            $regular = round((float) $product->price + $optionPrice, 2);
            $special = new Special($product, $optionPrice);
            $action = $special->resolveAction();
            $campaign = $action ? (float) $special->getDiscountPrice($action) : $regular;
            $quantity = $quantities[$product->brand_id] ?? 0;
            $price = $discounts->price($regular, $campaign, (int) $product->brand_id, $quantity);
            $personal = $price < round($campaign, 2);
            $percentage = $personal ? $discounts->percentage((int) $product->brand_id, $quantity) : 0;
            $conditions = [];

            if ($price < $regular) {
                $conditions[] = new CartCondition([
                    'name' => $personal ? 'Vaš količinski popust' : 'Akcija',
                    'type' => $personal ? 'quantity_discount' : ($action?->coupon ? 'coupon' : 'promo'),
                    'target' => '',
                    'value' => -round($regular - $price, 2),
                    'attributes' => ['percentage' => $percentage],
                ]);
            }

            $attributes = $item->attributes->all();
            $attributes['unit_price'] = $price;
            $attributes['original_price'] = $regular;
            $attributes['original_price_text'] = Currency::main($regular, true);
            $attributes['quantity_discount'] = $percentage;
            $cart->update($item->id, [
                'price' => $regular,
                'price_text' => Currency::main($price, true),
                'conditions' => $conditions,
                'associatedModel' => $product,
                'attributes' => $attributes,
            ]);
        }
    }
}
