<form name="pay" class="needs-validation w-100" action="{{ route('checkout') }}" novalidate  method="GET">
    @csrf
    <input type="hidden" name="provjera" value="{{ $data['order_id'] }}">


    <div class="d-block pt-0 pb-2  text-start" >
        <div class="alert alert-info  d-flex"  role="alert">
            <div class="alert-icon">
                <i class="ci-announcement"></i>
            </div>
            <div><small>{{ __('front/cart.prihvati') }}</small></div>
        </div>

    </div>

    <div class="form-check form-check-inline">
        <label class="form-check-label" for="ex-check-4">{{ __('front/cart.slazem_se_sa') }} {!! __(' :terms_of_service', [
                                                'terms_of_service' => '<a data-bs-toggle="modal" data-bs-target="#exampleModal" class="link-fx">'.__('front/cart.uvijetima_kupovine').'</a>',
                                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="link-fx">'.__('Privacy Policy').'</a>',
                                        ]) !!}</label>
        <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
        <div class="invalid-feedback" id="terms">{{ __('front/cart.morate_se_sloziti') }}</div>
    </div>

    <div class="d-flex mt-3">
    <div class="w-50 pe-3">
        <a class="btn btn-outline-primary d-block w-100" href="{{ route('naplata') }}"><i class="ci-arrow-left  me-1"></i><span class="d-none d-sm-inline">{{ __('front/cart.povratak_na_placanje') }}</span><span class="d-inline d-sm-none">{{ __('front/cart.povratak') }}</span></a>
    </div>
    <div class="w-50 ps-2">
        <button class="btn btn-primary w-100" type="submit"><span>{{ __('front/cart.dovrsi_kupnju') }}</span><i class="ci-arrow-right  ms-1"></i></button>
    </div>

    </div>

</form>




