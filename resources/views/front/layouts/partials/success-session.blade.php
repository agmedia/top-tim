@if(session('success'))
    <!-- Success alert -->
    <div class="alert alert-success d-flex" role="alert">
        <div class="alert-icon">
            <i class="ci-check-circle"></i>
        </div>
        <div>{{ __('front/cart.uspjeh') }}..! {{ session('success') }}</div>
    </div>
@endif
