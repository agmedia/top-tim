<?php

namespace App\Models\Front;

use App\Helpers\CartHelper;
use App\Helpers\Currency;
use App\Helpers\Helper;
use App\Helpers\ProductHelper;
use App\Models\Back\Marketing\Action;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\ProductAction;
use App\Models\Front\Catalog\ProductOption;
use App\Models\Front\Checkout\PaymentMethod;
use App\Models\Front\Checkout\ShippingMethod;
use App\Models\Back\Catalog\Product\ProductImage;
use App\Models\TagManager;
use Darryldecode\Cart\CartCondition;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Translation\t;

class AgCart extends Model
{
    /** @var string */
    private $cart_id;

    /** @var */
    private $cart;

    /** @var string */
    private $session_key;

    /** @var string */
    private $coupon;

    /** @var int */
    private $loyalty;

    public function __construct(string $id)
    {
        $this->cart_id     = $id;
        $this->cart        = Cart::session($id);
        $this->session_key = config('session.cart') ?: 'agm';
        $this->coupon      = session()->has($this->session_key . '_coupon') ? session($this->session_key . '_coupon') : '';
        $this->loyalty     = session()->has($this->session_key . '_loyalty') ? session($this->session_key . '_loyalty') : '';
    }

    /** @return array */
    public function get()
    {
        $eur = $this->getEur();

        return [
            'id'              => $this->cart_id,
            'coupon'          => $this->coupon,
            'loyalty'         => $this->loyalty,
            'has_loyalty'     => $this->hasLoyalty(),
            'items'           => $this->cart->getContent(),
            'count'           => $this->cart->getTotalQuantity(),
            'subtotal'        => $this->cart->getSubTotal(),
            'conditions'      => $this->cart->getConditions(),
            'detail_con'      => $this->setCartConditions(),
            'total'           => $this->cart->getTotal(),
            'eur'             => $eur,
            'secondary_price' => $eur,
        ];
    }

    /** @return Collection */
    public function getCartItems(bool $just_basic = false): Collection
    {
        $response = collect();
        foreach ($this->cart->getContent() as $item) {
            if ($just_basic) {
                $response->push(['id' => $item->id, 'quantity' => $item->quantity]);
            } else {
                $response->push($item);
            }
        }
        return $response;
    }

    /** @return null|float */
    public function getEur()
    {
        return isset(Currency::secondary()->value) ? Currency::secondary()->value : null;
    }

    /** @return string|null */
    public function check($request)
    {
        $products = Product::whereIn('id', $request['ids'])->pluck('quantity', 'id');
        $message  = null;

        foreach ($products as $id => $quantity) {
            if (!$quantity) {
                $this->remove((int) $id);
                $product = Product::where('id', (int) $id)->first();
                $message = 'Nažalost, artikl ' . substr($product->name, 0, 150) . ' više nije dostupan.';
            }
        }

        return $message;
    }

    /**
     * Dodavanje ili ažuriranje stavke u košarici.
     * Provjerava dostupnost na razini OPCIJE (ako postoji), inače na razini proizvoda.
     * Također sabira već postojeću količinu iste opcije u košarici.
     */
    public function add(Request $request, $id = null)
    {
        // 1) Resolve product / option iz requesta
        $product = Product::query()
            ->where('id', $request['item']['id'])
            ->orWhere('sku', $request['item']['id'])
            ->first();

        $product_option = null;
        if (!$product) {
            $product_option = ProductOption::query()->where('sku', $request['item']['id'])->first();
            if ($product_option) {
                $request->request->add(['options' => ['id' => $product_option->id]]);
                $product = $product_option->product()->first();
            }
        }

        if (!$product) {
            return ['error' => 'Došlo je do greške.!! Molimo pokušajte ponovo ili kontaktirajte administratora.'];
        }

        if (isset($request['item']['options'])) {
            $product_option = ProductOption::getFromCartData($request['item']['options']);
        }

        // 2) Dostupnost po opciji (ako postoji) ili po proizvodu
        $available = $product_option ? (int) $product_option->quantity : (int) $product->quantity;

        // 3) Pronađi postojeću stavku u košarici ZA ISTU OPCIJU / SKU
        $existingItemId = null;
        $existingQty    = 0;

        foreach ($this->cart->getContent() as $item) {
            $isSameOptionBySku = $product_option && $item->id === $product_option->sku;
            $optIdInCart = $item->attributes['options']['option']['id'] ?? null;
            $isSameOptionByAttr = $product_option && $optIdInCart && (int)$optIdInCart === (int)$product_option->id;
            $isSameProductNoOption = !$product_option && $item->id === $product->sku;

            if ($isSameOptionBySku || $isSameOptionByAttr || $isSameProductNoOption) {
                $existingItemId = $item->id;
                $existingQty    = (int) $item->quantity;
                break;
            }
        }

        $requestedQty = (int) ($request['item']['quantity'] ?? 0);
        $isRelative   = !empty($request['item']['relative']);

        // 4) Izračun nove ukupne količine ovisno o "relative"
        $newTotal = $isRelative
            ? $existingQty + $requestedQty      // delta (PDP dodavanje)
            : $requestedQty;                    // apsolutno (promjena u košarici)

        // 5) Validacija
        if ($newTotal > $available) {
            return ['error' => 'Nažalost nema dovoljnih količina artikla..!'];
        }

        // 6) Ažuriranje / dodavanje
        if ($existingItemId) {
            // Ako je relativno → proslijedi delta; ako je apsolutno → postavi apsolutnu vrijednost
            $valueForUpdate = $isRelative ? $requestedQty : $newTotal;
            return $this->updateCartItem($existingItemId, $valueForUpdate, $isRelative);
        }

        // Ako stavka ne postoji, dodaj novu (ovdje je quantity uvijek apsolutan)
        return $this->addToCart($request);
    }


    /** @return array */
    public function remove($id)
    {
        $this->cart->remove($id);
        return $this->get();
    }

    /** @return int */
    public function coupon($coupon): int
    {
        $items = $this->cart->getContent();

        // Refresh košarice s kuponom
        foreach ($items as $item) {
            $this->remove($item->id);
            $this->addToCart($this->resolveItemRequest($item));
        }

        $has_coupon = ProductAction::active()->where('coupon', $coupon)->get();
        return $has_coupon->count() ? 1 : 0;
    }

    /** @return int */
    public function hasLoyalty(): int
    {
        $loyalty = Loyalty::hasLoyalty();
        return $loyalty ? $loyalty : 0;
    }

    /** @return $this */
    public function flush()
    {
        if ($this->coupon != '') {
            $is_used = Helper::isCouponUsed($this->cart);
            if ($is_used != '') {
                $action = Action::query()->where('coupon', $is_used)->first();
                if ($action && $action->quantity == 1) {
                    $action->update(['status' => 0]);
                }
            }
        }

        $this->cart->clear();
        Helper::flushCache('cart', $this->cart_id);
        return $this;
    }

    /** @return Request */
    public function resolveItemRequest($item): Request
    {
        $request = [
            'item' => [
                'id'       => $item['id'],
                'quantity' => $item['quantity'],
                'options'  => $item['attributes']['options'] ?? [],
            ],
        ];

        $has_options = ProductHelper::hasOptionFromCartItem($item);
        if ($has_options) {
            $request['item']['options'] = $has_options;
        }

        return new Request($request);
    }

    /** Persist košaricu u DB ako je user prijavljen. */
    public function resolveDB(): void
    {
        $cart = $this->get();

        if (Auth::user()) {
            $has_cart = \App\Models\Cart::where('user_id', Auth::user()->id)->first();
            if ($has_cart) {
                \App\Models\Cart::edit($cart);
            } else {
                \App\Models\Cart::store($cart);
            }
        }
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    public function setCartConditions()
    {
        $this->cart->clearCartConditions();

        $shipping_method    = ShippingMethod::condition($this->cart);
        $payment_method     = PaymentMethod::condition($this->cart);
        $special_condition  = Helper::hasSpecialCartCondition($this->cart);
        $coupon_conditions  = Helper::hasCouponCartConditions($this->cart, $this->coupon);
        $loyalty_conditions = false; // Helper::hasLoyaltyCartConditions($this->cart, intval($this->loyalty));

        if ($payment_method) {
            $str = str_replace('+', '', $payment_method->getValue());
            if (number_format((float) $str, 2) > 0) {
                $this->cart->condition($payment_method);
            }
        }

        if ($shipping_method) {
            $this->cart->condition($shipping_method);
        }
        if ($special_condition) {
            $this->cart->condition($special_condition);
        }
        if ($coupon_conditions) {
            $this->cart->condition($coupon_conditions);
        }
        if ($loyalty_conditions) {
            $this->cart->condition($loyalty_conditions);
        }

        $response = [];
        foreach ($this->cart->getConditions() as $condition) {
            $value = $condition->getValue();
            $response[] = [
                'name'       => $condition->getName(),
                'type'       => $condition->getType(),
                'target'     => 'total',
                'value'      => $value,
                'attributes' => $condition->getAttributes(),
            ];
        }

        return $response;
    }

    /** @return array */
    private function addToCart(Request $request): array
    {
        $item = $this->structureCartItem($request);
        if (isset($item['error'])) {
            return $item;
        }
        $this->cart->add($item);
        return $this->get();
    }

    /** @return array */
    private function updateCartItem($id, $quantity, bool $relative): array
    {
        $this->cart->update($id, [
            'quantity' => [
                'relative' => $relative,
                'value'    => $quantity,
            ],
        ]);
        return $this->get();
    }

    /**
     * Sastavi stavku za košaricu + validiraj stock (opcija > proizvod)
     */
    private function structureCartItem(Request $request)
    {
        $product = Product::query()->where('id', $request['item']['id'])
            ->orWhere('sku', $request['item']['id'])
            ->first();

        $product_option = null;
        if (!$product) {
            $product_option = ProductOption::query()->where('sku', $request['item']['id'])->first();
            if ($product_option) {
                $product = $product_option->product()->first();
            }
        }

        if (!$product) {
            return ['error' => 'Došlo je do greške.!! Molimo pokušajte ponovo ili kontaktirajte administratora.'];
        }

        $product->dataLayer = TagManager::getGoogleProductDataLayer($product);

        if (isset($request['item']['options']) && isset($request['item']['options']['option_id'])) {
            $product_option = ProductOption::getFromCartData($request['item']['options']);
        }

        // VALIDACIJA DOSTUPNOSTI
        $available = $product_option ? (int) $product_option->quantity : (int) $product->quantity;
        if ($request['item']['quantity'] > $available) {
            return ['error' => 'Nažalost nema dovoljnih količina artikla..!'];
        }

        $item_data = $this->setItemData($product, $request); // cijena (uklj. opciju)

        $response = [
            'id'              => $item_data['id'],
            'name'            => $item_data['name'],
            'price'           => $item_data['price'],
            'price_text'      => Currency::main($item_data['price'], true),
            'sec_price'       => $product->secondary_price,
            'quantity'        => $request['item']['quantity'],
            'associatedModel' => $product,
            'attributes'      => $this->structureCartItemAttributes($product, $request),
        ];

        $conditions = $this->structureCartItemConditions($product, $request);
        foreach ($conditions as $condition) {
            $value = $condition->getValue();
            if ($value) {
                $response['price_text'] = Currency::main($item_data['price'] + $value, true);
            }
        }
        if ($conditions) {
            $response['conditions'] = $conditions;
        }

        if (empty($response)) {
            return ['error' => 'došlo je do greške'];
        }

        return $response;
    }

    /** @return array */
    private function setItemData(Product $product, Request $request): array
    {
        $data = [
            'id'   => $product->sku,
            'name' => $product->name,
            'price'=> $product->price,
        ];

        if (isset($request['item']['options']) && isset($request['item']['options']['option_id'])) {
            $product_option = ProductOption::getFromCartData($request['item']['options']);
            if ($product_option) {
                $data = [
                    'id'   => $product_option->sku,
                    'name' => $product->name . ', ' . CartHelper::resolveItemOptionName($product_option),
                    'price'=> $product->price + $product_option->price,
                ];
            }
        }

        return $data;
    }

    /** @return array */
    private function structureCartItemAttributes(Product $product, Request $request): array
    {
        $image   = $product->image;
        $options = [];

        if (isset($request['item']['options']) && isset($request['item']['options']['option_id'])) {
            $product_option = ProductOption::getFromCartData($request['item']['options']);

            if ($product_option) {
                $options       = $this->structureItemOptions($product_option->id, $request['item']['quantity']);
                $product_image = ProductImage::query()->where('product_id', $product->id);

                if ($product_option->parent_id) {
                    $product_image->where('option_id', $product_option->parent_id)
                        ->orWhere('option_id', $product_option->option_id);
                } else {
                    $product_image->where('option_id', $product_option->option_id);
                }

                $product_image = $product_image->first();
                if ($product_image) {
                    $image = $product_image->image;
                }
            }
        }

        return [
            'path'    => $product->url,
            'tax'     => $product->tax($product->tax_id),
            'options' => $options,
            'slika'   => $image,
        ];
    }

    /** @return array */
    private function structureCartItemConditions(Product $product, Request $request): array
    {
        $product_option_price = null;
        $conditions = [];

        if (isset($request['item']['options']) && isset($request['item']['options']['option_id'])) {
            $product_option = ProductOption::getFromCartData($request['item']['options']);
            if ($product_option && $product_option->price != 0) {
                $product_option_price = $product_option->price;
            }
        }

        $discount = Product::getSpecial($product->id, $product_option_price);
        if ($discount) {
            $coupon = $product->coupon();
            $price  = $product->price + ($product_option_price ?? 0);

            if ($coupon) {
                $conditions[] = new CartCondition([
                    'name'   => 'Kupon akcija',
                    'type'   => 'coupon',
                    'target' => $coupon,
                    'value'  => -($price - $discount),
                ]);
            } else {
                $conditions[] = new CartCondition([
                    'name'   => 'Akcija',
                    'type'   => 'promo',
                    'target' => '',
                    'value'  => -($price - $discount),
                ]);
            }
        }

        return $conditions;
    }

    /** @return array */
    private function structureItemOptions($product_option_id, $quantity): array
    {
        $options = [];

        if ($product_option_id) {
            $product_option = ProductOption::query()->find($product_option_id);

            if ($product_option) {
                $options['option'] = [
                    'id'       => $product_option->id,
                    'sku'      => $product_option->sku,
                    'name'     => CartHelper::resolveItemOptionName($product_option),
                    'quantity' => $quantity,               // količina u košarici za DODANU opciju
                    'price'    => $product_option->price,
                    'stock'    => (int) $product_option->quantity, // stock opcije (frontend može koristiti za :max)
                ];
            }
        }

        return $options;
    }
}
