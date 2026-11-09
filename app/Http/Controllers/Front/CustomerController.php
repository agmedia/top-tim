<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Country;
use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontBaseController;
use App\Models\Front\AgCart;
use App\Models\Front\Checkout\Order;
use App\Models\Front\Loyalty;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends FrontBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (session()->has(config('session.cart'))) {
            //dd($request->session()->previousUrl());
            /*if ($request->session()->previousUrl() == config('app.url') . 'login') {
                $cart = new AgCart(session(config('session.cart')));

                if ($cart->get()['count'] > 0) {
                    return redirect()->route('kosarica');
                }
            }*/
        }

        $user = auth()->user();
        $countries = Country::list();

        CheckoutSession::forgetAddress();

        return view('front.customer.index', compact('user', 'countries'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function orders(Request $request)
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)->paginate(config('settings.pagination.front'));

        return view('front.customer.moje-narudzbe', compact('user', 'orders'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function loyalty(Request $request)
    {
        $user = auth()->user();

        $loyalty = Loyalty::where('user_id', $user->id)->get();

        $points = Loyalty::hasLoyaltyTotal($user->id);

        return view('front.customer.loyalty', compact('user', 'loyalty', 'points'));
    }


    /**
     * @param Request $request
     * @param User    $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request, User $user)
    {
        abort_unless((int) $request->user()->getAuthIdentifier() === (int) $user->getKey(), 403);

        // Customer profile updates must never enter the administrative user editor.
        $profile = $request->validate([
            'fname'   => ['required', 'string', 'max:255'],
            'lname'   => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city'    => ['required', 'string', 'max:255'],
            'zip'     => ['required', 'string', 'max:255'],
            'state'   => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'oib'     => ['nullable', 'string', 'max:255'],
        ]);

        $updated = $user->details()->update($profile);

        if ($updated) {
            return redirect()->route('moj-racun')->with(['success' => (__('front/cart.korisnik_snimljen'))]);
        }

        return redirect()->back()->with(['error' => (__('front/cart.greska_prilikom_snimanja'))]);
    }

}
