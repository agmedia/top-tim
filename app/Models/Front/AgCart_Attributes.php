<?php

namespace App\Models\Front;

use App\Helpers\CartHelper;
use App\Helpers\Currency;
use App\Helpers\Helper;
use App\Models\Back\Marketing\Action;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\ProductAction;
use App\Models\Front\Catalog\ProductOption;
use App\Models\Front\Checkout\PaymentMethod;
use App\Models\Front\Checkout\ShippingMethod;
use App\Models\TagManager;
use Darryldecode\Cart\CartCondition;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgCart_Attributes extends Model
{

    /**
     * @var string
     */
    private $cart_id;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var string
     */
    private $session_key;

    /**
     * @var string
     */
    private $coupon;

    /**
     * @var int
     */
    private $loyalty;


    /**
     * AgCart constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->cart_id     = $id;
        $this->cart        = Cart::session($id);
        $this->session_key = config('session.cart') ?: 'agm';
        $this->coupon      = session()->has($this->session_key . '_coupon') ? session($this->session_key . '_coupon') : '';
        $this->loyalty     = session()->has($this->session_key . '_loyalty') ? session($this->session_key . '_loyalty') : '';
    }


    /**
     * @return array
     */
    public function get()
    {
        $eur = $this->getEur();

        $response = [
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
            'secondary_price' => $eur
        ];

        return $response;
    }


    /**
     * @param bool $just_basic
     *
     * @return Collection
     */
    public function getCartItems(bool $just_basic = false): Collection
    {
        $response = collect();

        foreach ($this->cart->getContent() as $item) {
            if ($just_basic) {
                $data = ['id' => $item->id, 'quantity' => $item->quantity];
                $response->push($data);
            } else {
                $response->push($item);
            }
        }

        return $response;
    }


    /**
     * @return null
     */
    public function getEur()
    {
        if (isset(Currency::secondary()->value)) {
            return Currency::secondary()->value;
        }

        return null;
    }


    /**
     * @param      $request
     * @param null $id
     *
     * @return array
     */
    public function check($request)
    {
        //$message = Helper::resolveCache('cart')->remember($this->cart_id, config('cache.cart_life'), function () use ($request) {
        $products = Product::whereIn('id', $request['ids'])->pluck('quantity', 'id');
        $message  = null;

        foreach ($products as $id => $quantity) {
            if ( ! $quantity) {
                $this->remove(intval($id));

                $product = Product::where('id', intval($id))->first();

                $message = 'Nažalost, knjiga ' . substr($product->name, 0, 150) . ' više nije dostupna.';
            }
        }

        return $message;

        //});

        return [
            'cart'    => $this->get(),
            'message' => $message
        ];
    }


    /**
     * @param Request $request
     * @param         $id
     *
     * @return array|string[]
     */
    public function add(Request $request, $id = null)
    {
        //Log::info('$request->toArray() i $id iz add()');
       // Log::info($request->toArray());
       // Log::info($id);
        // Updejtaj artikl sa apsolutnom količinom.
        foreach ($this->cart->getContent() as $item) {
            if ($item->id == $request['item']['id']) {
                $quantity = $request['item']['quantity'];
                $product  = Product::where('id', $request['item']['id'])->first();

                if ($quantity > $product->quantity) {
                    return ['error' => 'Nažalost nema dovoljnih količina artikla..!'];
                }

                if ($quantity == 1 && ($item->quantity == 1 || $item->quantity > $quantity)) {
                    if ( ! $id) {
                        $quantity = $item->quantity + 1;
                    }
                }

                $relative = false;

                if (isset($request['item']['relative']) && $request['item']['relative']) {
                    $relative = true;
                }

                return $this->updateCartItem($item->id, $quantity, $relative, $request);
            }
        }

        return $this->addToCart($request);
    }


    /**
     * @param $id
     *
     * @return array
     */
    public function remove($id)
    {
        $this->cart->remove($id);

        return $this->get();
    }


    /**
     * Provjeriti metodu da li se koristi negdje.
     * ?????????????????????????????????????????
     *
     * @param $coupon
     *
     * @return int
     */
    public function coupon($coupon): int
    {
        $items = $this->cart->getContent();

        // Refreshaj košaricu sa upisanim kuponom.
        foreach ($items as $item) {
            $this->remove($item->id);
            $this->addToCart($this->resolveItemRequest($item));
        }

        $has_coupon = ProductAction::active()->where('coupon', $coupon)->get();

        if ($has_coupon->count()) {
            return 1;
        }

        return 0;
    }


    /**
     * @return int
     */
    public function hasLoyalty(): int
    {
        $loyalty = Loyalty::hasLoyalty();

        if ($loyalty) {
            return $loyalty;
        }

        return 0;
    }


    /**
     * @return $this
     */
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


    /**
     * @param $item
     *
     * @return array[]
     */
    public function resolveItemRequest($item)
    {

        return new Request([
            'item' => [
                'id'       => $item['id'],
                'quantity' => $item['quantity']
            ]
        ]);
    }


    /**
     * If user is logged store or update the DB session.
     *
     * @return void
     */
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
        $loyalty_conditions = Helper::hasLoyaltyCartConditions($this->cart, intval($this->loyalty));

        if ($payment_method) {
            $str = str_replace('+', '', $payment_method->getValue());
            if (number_format(floatval($str), 2) > 0) {
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

        // Style response array
        $response = [];

        foreach ($this->cart->getConditions() as $condition) {
            $value = $condition->getValue();

            $response[] = [
                'name'       => $condition->getName(),
                'type'       => $condition->getType(),
                'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                'value'      => $value,
                'attributes' => $condition->getAttributes()
            ];
        }

        return $response;
    }


    /**
     * @param $request
     *
     * @return array
     */
    private function addToCart($request): array
    {
        $this->cart->add($this->structureCartItem($request));

        return $this->get();
    }


    /**
     * @param      $id
     * @param      $quantity
     * @param bool $relative
     * @param      $request
     *
     * @return array
     */
    private function updateCartItem($id, $quantity, bool $relative, $request): array
    {
        $this->cart->update($id, [
            'quantity' => [
                'relative' => $relative,
                'value'    => $quantity
            ],
        ]);

        // Options
        if (isset($request['item']['options']['id'])) {
            $item = json_decode($this->cart->get($id), true);
            $option_id = $request['item']['options']['id'];
            $product_option = ProductOption::query()->find($option_id);

            if ($product_option) {
                $attributes = $item['attributes'];
                $name = CartHelper::resolveItemOptionName($product_option);
                $qty  = ($attributes['options'][$option_id]['quantity'] ?? 0) + $quantity;

              //  Log::info('Item iz updateCartItem(...)');
              //  Log::info($item);

                $attributes['options'][$option_id] = [
                    'id'       => $product_option->id,
                    'sku'      => $product_option->sku,
                    'name'     => $name,
                    'quantity' => $qty,
                ];

                $this->cart->update($id, [
                    'attributes' => $attributes,
                ]);

                //
                if ($product_option->price != 0) {
                    $this->cart->removeItemCondition($id, $name);

                    $condition = new CartCondition([
                        'name'   => CartHelper::resolveItemOptionName($product_option),
                        'type'   => 'option',
                        'value'  => $product_option->price * $qty
                    ]);

                    $this->cart->addItemCondition($id, $condition);
                }
            }
        }

        return $this->get();
    }


    /**
     * @param Request $request
     *
     * @return array
     */
    private function structureCartItem(Request $request): array
    {
        $product = Product::where('id', $request['item']['id'])->first();

        $product->dataLayer = TagManager::getGoogleProductDataLayer($product);

        if ($request['item']['quantity'] > $product->quantity) {
            return ['error' => 'Nažalost nema dovoljnih količina artikla..!'];
        }

        $response = [
            'id'              => $product->id,
            'name'            => $product->name,
            'price'           => $product->price,
            'sec_price'       => $product->secondary_price,
            'quantity'        => $request['item']['quantity'],
            'associatedModel' => $product,
            'attributes'      => $this->structureCartItemAttributes($product, $request)
        ];

        $conditions = $this->structureCartItemConditions($product, $request);

        if ($conditions) {
            $response['conditions'] = $conditions;
        }

        if (empty($response)) {
            return ['error' => 'došlo je do greške'];
        }

        return $response;
    }


    /**
     * @param         $product
     * @param Request $request
     *
     * @return array
     */
    private function structureCartItemAttributes(Product $product, Request $request): array
    {
        $options = [];

        if (isset($request['item']['options']['id'])) {
            $options = $this->structureItemOptions($request['item']['options']['id'], $request['item']['quantity']);
        }

        return [
            'path'    => $product->url,
            'tax'     => $product->tax($product->tax_id),
            'options' => $options
        ];
    }


    /**
     * @param Product $product
     *
     * @return array
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    private function structureCartItemConditions(Product $product, Request $request): array
    {
        $conditions = [];

        // Ako artikl ima akciju.
        if ($product->special()) {
            $coupon = $product->coupon();

            if ($coupon) {
                $conditions[] = new CartCondition([
                    'name'   => 'Kupon akcija',
                    'type'   => 'coupon',
                    'target' => $coupon,
                    'value'  => -($product->price - $product->special())
                ]);
            } else {
                $conditions[] = new CartCondition([
                    'name'   => 'Akcija',
                    'type'   => 'promo',
                    'target' => '',
                    'value'  => -($product->price - $product->special())
                ]);
            }
        }

        if (isset($request['item']['options']['id'])) {
            $option_id = $request['item']['options']['id'];
            $product_option = ProductOption::query()->find($option_id);

            if ($product_option && $product_option->price != 0) {
                $conditions[] = new CartCondition([
                    'name'   => CartHelper::resolveItemOptionName($product_option),
                    'type'   => 'option',
                    'value'  => $product_option->price
                ]);
            }
        }

        return $conditions;
    }


    /**
     * @param $product_option_id
     *
     * @return array
     */
    private function structureItemOptions($product_option_id, $quantity): array
    {
        $options = [];

        if ($product_option_id) {
            $product_option = ProductOption::query()->find($product_option_id);

            if ($product_option) {
                $options[$product_option->id] = [
                    'id'       => $product_option->id,
                    'sku'      => $product_option->sku,
                    'name'     => CartHelper::resolveItemOptionName($product_option),
                    'quantity' => $quantity,
                ];
            }
        }

        return $options;
    }

}
