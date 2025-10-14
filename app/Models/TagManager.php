<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Models\Back\Orders\Order;
use App\Models\Front\Catalog\Product;
use Darryldecode\Cart\CartCollection;

/**
 * Centralizirani GA4 TagManager
 */
class TagManager
{
    // Ako su cijene u bazi spremljene u DECIMALAMA (5.5000 => 5.50), stavi false.
    private const PRICES_ARE_IN_CENTS = false;

    /* =========================================================
     |  JAVNE METODE — VIEW / LIST / CLICK / CART / CHECKOUT
     |========================================================= */

    public static function viewItem(Product $product): array
    {
        return [
            'event'     => 'view_item',
            'ecommerce' => [
                'items' => [ static::getGoogleProductDataLayer($product) ],
            ],
        ];
    }

    public static function viewItemList(iterable $products, string $listName = ''): array
    {
        $items    = [];
        $position = 1;

        foreach ($products as $p) {
            $item = static::getGoogleProductDataLayer($p);
            if ($listName !== '') {
                $item['item_list_name'] = $listName;
            }
            $item['index']   = $position++;
            $items[]         = $item;
        }

        return [
            'event'     => 'view_item_list',
            'ecommerce' => [
                'item_list_name' => $listName,
                'items'          => $items,
            ],
        ];
    }

    public static function selectItem(Product $product, string $listName = '', int $position = 1): array
    {
        $item = static::getGoogleProductDataLayer($product);
        if ($listName !== '') {
            $item['item_list_name'] = $listName;
        }
        $item['index'] = $position;

        return [
            'event'     => 'select_item',
            'ecommerce' => [
                'item_list_name' => $listName,
                'items'          => [ $item ],
            ],
        ];
    }

    public static function addToCart(Product $product, int $qty = 1): array
    {
        $item = static::getGoogleProductDataLayer($product, $qty);

        $unit  = static::normalizeMoney($item['price']);
        $value = $unit * $qty;

        return [
            'event'     => 'add_to_cart',
            'ecommerce' => [
                'currency' => 'EUR',
                'value'    => static::fmt($value),
                'items'    => [ $item ],
            ],
        ];
    }

    public static function removeFromCart(Product $product, int $qty = 1): array
    {
        $item = static::getGoogleProductDataLayer($product, $qty);

        $unit  = static::normalizeMoney($item['price']);
        $value = $unit * $qty;

        return [
            'event'     => 'remove_from_cart',
            'ecommerce' => [
                'currency' => 'EUR',
                'value'    => static::fmt($value),
                'items'    => [ $item ],
            ],
        ];
    }

    public static function beginCheckout(array $cart_collection, float $cart_total): array
    {
        $items = static::getGoogleCartDataLayer($cart_collection);

        return [
            'event'     => 'begin_checkout',
            'ecommerce' => [
                'currency' => 'EUR',
                'value'    => static::fmt(static::normalizeMoney($cart_total)),
                'items'    => $items,
            ],
        ];
    }

    public static function getGoogleSuccessDataLayer(Order $order)
    {
        $products = [];
        $shipping = 0.0;
        $tax      = 0.0;

        foreach ($order->products as $product) {
            $qty = (int) ($product->quantity ?? 1);
            $products[] = static::getGoogleProductDataLayer($product->real, $qty);
        }

        foreach ($order->totals()->get() as $total) {
            if ($total->code == 'subtotal') {
                $v = static::normalizeMoney($total->value);
                $tax += $v - ($v / 1.05);
            }
            if ($total->code == 'shipping') {
                $v = static::normalizeMoney($total->value);
                $tax      += $v - ($v / 1.25);
                $shipping = $v;
            }
        }

        return [
            'event'     => 'purchase',
            'ecommerce' => [
                'transaction_id' => (string) $order->id,
                'affiliation'    => 'Top Tim webshop',
                'value'          => static::fmt(static::normalizeMoney($order->total)),
                'tax'            => static::fmt($tax),
                'shipping'       => static::fmt($shipping),
                'currency'       => 'EUR',
                'items'          => $products,
            ],
        ];
    }

    /* =========================================================
     |  MAPIRANJE ARTIKLA (koristi se svugdje)
     |========================================================= */

    public static function getGoogleProductDataLayer(Product $product, int $qty = 1): array
    {
        $base    = static::normalizeMoney($product->main_price);
        $special = static::normalizeMoney($product->main_special ?? 0);

        $unitPrice     = $base;
        $discountValue = 0.0;

        if ($special > 0 && $base > $special) {
            $unitPrice     = $special;
            $discountValue = max(0.0, $base - $special);
        }

        return [
            'item_id'        => $product->sku ?: (string) $product->id,
            'item_name'      => $product->name,
            'price'          => static::fmt($unitPrice),
            'currency'       => 'EUR',
            'discount'       => static::fmt($discountValue),
            'item_category'  => $product->category() ? $product->category()->title : '',
            'item_category2' => $product->subcategory() ? $product->subcategory()->title : '',
            'quantity'       => max(1, (int)$qty),
        ];
    }

    public static function getGoogleCartDataLayer($cart_collection): array
    {
        $items = [];

        // Odredi što iterirati
        if (is_array($cart_collection) && isset($cart_collection['items'])) {
            $iterable = $cart_collection['items'];
        } else {
            // Darryldecode Cart::get() vraća kolekciju koja je iterabilna
            $iterable = $cart_collection;
        }

        if (!is_iterable($iterable)) {
            return $items;
        }

        foreach ($iterable as $item) {
            // Ako postoji već pripremljen GA4 item na modelu — uzmi ga, ali popravi qty/price
            if (isset($item->associatedModel->dataLayer) && is_array($item->associatedModel->dataLayer)) {
                $dl = $item->associatedModel->dataLayer;

                $dl['quantity'] = (int) ($item->quantity ?? ($dl['quantity'] ?? 1));

                // price/discount normalizacija
                if (isset($dl['price'])) {
                    $dl['price'] = static::fmt(static::normalizeMoney($dl['price']));
                } else {
                    $dl['price'] = static::fmt(
                        static::normalizeMoney($item->associatedModel->main_special ?? $item->associatedModel->main_price)
                    );
                }
                if (isset($dl['discount'])) {
                    $dl['discount'] = static::fmt(static::normalizeMoney($dl['discount']));
                }

                $items[] = $dl;
                continue;
            }

            // Inače izgradi item iz Product modela
            /** @var Product|null $product */
            $product = $item->associatedModel ?? null;
            $qty     = (int) ($item->quantity ?? 1);

            if ($product instanceof Product) {
                $items[] = static::getGoogleProductDataLayer($product, $qty);
            }
        }

        return $items;
    }



    /* =========================================================
     |  POMOĆNE — sigurno rukovanje brojevima
     |========================================================= */

    private static function normalizeMoney($value): float
    {
        // 1) Broj već je broj
        if (is_int($value) || is_float($value)) {
            $num = (float) $value;
        }
        // 2) Stringovi (podržava i "1.234,56")
        elseif (is_string($value)) {
            $v = preg_replace('/\s+/', '', $value);
            $hasDot   = strpos($v, '.') !== false;
            $hasComma = strpos($v, ',') !== false;

            if ($hasDot && $hasComma) {
                // EU format: . = tisućice, , = decimale
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } elseif ($hasComma && !$hasDot) {
                // Samo zarez -> pretvori u točku
                $v = str_replace(',', '.', $v);
            }
            $num = is_numeric($v) ? (float) $v : 0.0;
        }
        // 3) stdClass / objekt — pokušaj tipična polja
        elseif (is_object($value)) {
            // Ako se objekt može pretvoriti u string
            if (method_exists($value, '__toString')) {
                return self::normalizeMoney((string) $value);
            }

            foreach (['amount','value','price','gross','net','total'] as $prop) {
                if (isset($value->$prop)) {
                    return self::normalizeMoney($value->$prop);
                }
            }
            // često zna biti u ugniježđenom polju
            if (isset($value->data)) {
                return self::normalizeMoney($value->data);
            }

            // Nepoznata struktura — ne ruši app, logiraj i vrati 0

            $num = 0.0;
        }
        // 4) Array — potraži tipična polja
        elseif (is_array($value)) {
            foreach (['amount','value','price','gross','net','total'] as $key) {
                if (array_key_exists($key, $value)) {
                    return self::normalizeMoney($value[$key]);
                }
            }
            // uzmi prvo brojčano
            foreach ($value as $v) {
                $n = self::normalizeMoney($v);
                if ($n !== 0.0) {
                    $num = $n;
                    break;
                }
            }
            $num = $num ?? 0.0;
        }
        // 5) Drugo — fallback
        else {
            $num = 0.0;
        }

        if (self::PRICES_ARE_IN_CENTS) {
            $num = $num / 100.0;
        }
        return $num;
    }



    private static function fmt(float $n): float
    {
        return round($n, 2);
    }
}
