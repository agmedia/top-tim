@extends('back.layouts.backend')
@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/orders.narudzba_edit') }} <small class="font-weight-light">#_</small><strong>{{ $order->id }}</strong></h1>
            </div>
        </div>
    </div>


    <!-- Page Content -->
    <div class="content">
        @include('back.layouts.partials.session')

        <form action="{{ isset($order) ? route('orders.update', ['order' => $order]) : route('orders.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if (isset($order))
            {{ method_field('PATCH') }}
        @endif

        <!-- Products -->
            <div class="block block-rounded" id="ag-order-products-app">
                <div class="block-header block-header-default">
                    <h3 class="block-title">{{ __('back/orders.artikli') }}</h3>
                </div>
                <div class="block-content">
                    <ag-order-products
                            products="{{ isset($order) ? json_encode($order->products) : '' }}"
                            totals="{{ isset($order) ? json_encode($order->totals) : '' }}"
                            products_autocomplete_url="{{ route('products.autocomplete') }}">
                    </ag-order-products>
                </div>
            </div>
            <!-- END Products -->

            <!-- Customer -->
            <div class="row">
                <div class="col-sm-7">
                    <!-- Billing Address -->
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">{{ __('back/orders.kupac') }}</h3>
                            <div class="block-options">
                                @if (isset($order) && $order->user_id)
                                    <span class="small text-gray mr-3">{{ __('back/orders.kupac_je_registriran') }} </span><i class="fa fa-user text-success"></i>
                                @else
                                    <span class="small font-weight-light mr-3">{{ __('back/orders.kupac_nije_registriran') }} </span><i class="fa fa-user text-danger-light"></i>
                                @endif
                            </div>
                        </div>
                        <div class="block-content">
                            <div class="row justify-content-center push">
                                <div class="col-md-11">
                                    <div class="form-group row items-push">
                                        <div class="col-md-6">
                                            <label for="fname-input">{{ __('back/orders.ime') }}</label>
                                            <input type="text" class="form-control" id="fname-input" name="fname" placeholder="{{ __('back/orders.upisite_ime_kupca') }}" value="{{ isset($order) ? $order->shipping_fname : old('fname') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lname-input">{{ __('back/orders.prezime') }}</label>
                                            <input type="text" class="form-control" id="lname-input" name="lname" placeholder="{{ __('back/orders.upisite_prezime_kupca') }}" value="{{ isset($order) ? $order->shipping_lname : old('lname') }}">
                                        </div>

                                        <div class="col-md-12">
                                            <label for="address-input">{{ __('back/orders.adresa') }}</label>
                                            <input type="text" class="form-control" id="address-input" name="address" placeholder="{{ __('back/orders.upisite_adresu_kupca') }}" value="{{ isset($order) ? $order->shipping_address : old('address') }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label for="zip-input">{{ __('back/orders.zip') }}</label>
                                            <input type="text" class="form-control" id="zip-input" name="zip" placeholder="{{ __('back/orders.upisite_zip_kupca') }}" value="{{ isset($order) ? $order->shipping_zip : old('zip') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="city-input">{{ __('back/orders.grad') }}>/label>
                                            <input type="text" class="form-control" id="city-input" name="city" placeholder="{{ __('back/orders.upisite_grad_kupca') }}" value="{{ isset($order) ? $order->shipping_city : old('city') }}">
                                        </div>
                                        <div class="col-md-5">
                                            <label for="state-input">{{ __('back/orders.drzava') }}</label>
                                            <input type="text" class="form-control" id="state-input" name="state" placeholder="{{ __('back/orders.upisite_drzavu_kupca') }}" value="{{ isset($order) ? $order->shipping_state : old('state') }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="phone-input">{{ __('back/orders.telefon') }}</label>
                                            <input type="text" class="form-control" id="phone-input" name="phone" placeholder="{{ __('back/orders.upisite_telefon_kupca') }}" value="{{ isset($order) ? $order->shipping_phone : old('phone') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email-input">{{ __('back/orders.email') }}</label>
                                            <input type="text" class="form-control" id="email-input" name="email" placeholder="{{ __('back/orders.upisite_email_kupca') }}" value="{{ isset($order) ? $order->shipping_email : old('email') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END Billing Address -->
                </div>
                <div class="col-sm-5">
                    <!-- Shipping -->
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">{{ __('back/orders.nacin_dostave') }}</h3>
                        </div>
                        <div class="block-content">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label for="shipping-select">{{ __('back/orders.dostava') }}</label>
                                    <select class="js-select2 form-control" id="shipping-select" name="shipping" style="width: 100%;" data-placeholder="{{ __('back/orders.odaberite_nacin_dostave') }}">
                                        <option></option>
                                        @foreach ($shippings as $shipping)
                                            <option value="{{ $shipping->code }}" {{ ((isset($order)) and ($order->shipping_code == $shipping->code)) ? 'selected' : '' }}>{{ $shipping->title->{ current_locale() } }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="shipping-amount-input">{{ __('back/orders.iznos') }}</label>
                                    <input type="text" class="form-control" id="shipping-amount-input" name="shipping_amount" placeholder="{{ __('back/orders.upisite_iznos') }}" value="{{ isset($order) ? $order->totals()->where('code', 'shipping')->first()->value : old('shipping_amount') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Payments -->
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">{{ __('back/orders.nacin_placanja') }}</h3>
                        </div>
                        <div class="block-content">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label for="payment-select">{{ __('back/orders.placanje') }}</label>
                                    <select class="js-select2 form-control" id="payment-select" name="payment" style="width: 100%;" data-placeholder="{{ __('back/orders.odaberite_nacin_placanja') }}">
                                        <option></option>
                                        @foreach ($payments as $payment)
                                            <option value="{{ $payment->code }}" {{ ((isset($order)) and ($order->payment_code == $payment->code)) ? 'selected' : '' }}>{{ $payment->title->{ current_locale() } }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="payment-amount-input">{{ __('back/orders.iznos') }}</label>
                                    <input type="text" class="form-control" id="payment-amount-input" name="payment_amount" placeholder="{{ __('back/orders.upisite_iznos') }}" value="{{ (isset($order) && $order->totals()->where('code', 'payment')->first()) ? $order->totals()->where('code', 'payment')->first()->value : old('payment_amount') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Customer -->

            <!-- Log Messages -->
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">{{ __('back/orders.povijest_narudzbe') }}</h3>
                    <div class="block-options">
                        <div class="dropdown">
                            <button type="button" class="btn btn-alt-secondary" id="btn-add-comment">
                                {{ __('back/orders.dodaj_komentar') }}
                            </button>
                            <button type="button" class="btn btn-light" id="dropdown-ecom-filters" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('back/orders.promjeni_status') }}
                                <i class="fa fa-angle-down ml-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-ecom-filters">
                                @foreach ($statuses as $status)
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="javascript:setStatus({{ $status->id }});">
                                        <span class="badge badge-pill badge-{{ $status->color }}">{{ $status->title->{ current_locale() } }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block-content">
                    <table class="table table-borderless table-striped table-vcenter font-size-sm">
                        <tbody>
                        @foreach ($order->history as $record)
                            <tr>
                                <td class="font-size-base">
                                    @if ($record->status)
                                        <span class="badge badge-pill badge-{{ $record->status->color }}">{{ $record->status->title->{ current_locale() } }}</span>
                                    @else
                                        <small>{{ __('back/orders.komentar') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-w600">{{ \Illuminate\Support\Carbon::make($record->created_at)->locale('hr_HR')->diffForHumans() }}</span> /
                                    <span class="font-weight-light">{{ \Illuminate\Support\Carbon::make($record->created_at)->format('d.m.Y - h:i') }}</span>
                                </td>
                                <td>
                                    <a href="javascript:void(0)">{{ $record->user ? $record->user->name : $record->order->shipping_fname . ' ' . $record->order->shipping_lname }}</a>
                                </td>
                                <td>{{ $record->comment }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="block">
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-hero-success mb-3">
                                <i class="fas fa-save mr-1"></i> {{ __('back/orders.snimi') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
    <!-- END Page Content -->

@endsection

@push('modals')
    <div class="modal fade" id="comment-modal" tabindex="-1" role="dialog" aria-labelledby="comment--modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-popout" role="document">
            <div class="modal-content rounded">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary">
                        <h3 class="block-title">{{ __('back/orders.dodaj_komentar') }}</h3>
                        <div class="block-options">
                            <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row justify-content-center mb-3">
                            <div class="col-md-10">
                                <div class="form-group mb-4">
                                    <label for="status-select">{{ __('back/orders.promjeni_status') }}</label>
                                    <select class="js-select2 form-control" id="status-select" name="status" style="width: 100%;" data-placeholder="{{ __('back/orders.promjeni_status_narudzbe') }}">
                                        <option value="0">{{ __('back/orders.bez_promjene_statusa') }}</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->title->{ current_locale() } }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="comment-input">{{ __('back/orders.komentar') }}</label>
                                    <textarea class="form-control" name="comment" id="comment-input" rows="7"></textarea>
                                </div>

                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                            {{ __('back/orders.odustani') }} <i class="fa fa-times ml-2"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); changeStatus();">
                            {{ __('back/orders.snimi') }} <i class="fa fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('js_after')
    <script src="{{ asset('js/vue.js') }}"></script>
    <script src="{{ asset('js/components/ag-order-products.js') }}"></script>

    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(() => {
            $('#shipping-select').select2({});
            $('#payment-select').select2({});

            $('#status-select').select2({});

            $('#btn-add-comment').on('click', () => {
                $('#comment-modal').modal('show');
                $('#status-select').val(0);
                $('#status-select').trigger('change');
            });
        })

        /**
         *
         * @param status
         */
        function setStatus(status) {
            $('#comment-modal').modal('show');
            $('#status-select').val(status);
            $('#status-select').trigger('change');
        }

        /**
         *
         */
        function changeStatus() {
            let item = {
                order_id: {{ $order->id }},
                comment: $('#comment-input').val(),
                status: $('#status-select').val()
            };

            axios.post("{{ route('api.order.status.change') }}", item)
            .then(response => {
                console.log(response.data)
                if (response.data.message) {
                    $('#comment-modal').modal('hide');

                    successToast.fire({
                        timer: 1500,
                        text: response.data.message,
                    }).then(() => {
                        location.reload();
                    })

                } else {
                    return errorToast.fire(response.data.error);
                }
            });
        }
    </script>

@endpush
