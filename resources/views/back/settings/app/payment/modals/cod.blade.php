<div class="modal fade" id="payment-modal-cod" tabindex="-1" role="dialog" aria-labelledby="modal-payment-modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">{{ __('back/app.payments.cod') }}</h3>
                    <div class="block-options">
                        <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center">
                        <div class="col-md-10">

                            <div class="row mb-3">
                                <div class="col-md-8">
                                    @include('back.layouts.partials.language-inputs', [
                                                    'type' => 'input',
                                                    'title' => __('back/app.payments.input_title'),
                                                    'tab' => 'cod-tab-title',
                                                    'input' => 'title',
                                                    'id' => 'cod-title'
                                                    ])
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="cod-min">{{ __('back/app.payments.min_order_amount') }}</label>
                                        <input type="text" class="form-control" id="cod-min" name="min">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="cod-price">{{ __('back/app.payments.fee_amount') }}</label>
                                        <input type="text" class="form-control" id="cod-price" name="data['price']">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="cod-geo-zone">{{ __('back/app.payments.geo_zone') }} <span class="small text-gray">{{ __('back/app.payments.geo_zone_label') }}</span></label>
                                    <select class="js-select2 form-control" id="cod-geo-zone" name="geo_zone" style="width: 100%;" data-placeholder="{{ __('back/app.payments.select_geo') }}">
                                        <option></option>
                                        @foreach ($geo_zones as $geo_zone)
                                            <option value="{{ $geo_zone->id }}" {{ ((isset($payment)) and ($payment->geo_zone == $geo_zone->id)) ? 'selected' : '' }}>{{ isset($geo_zone->title->{current_locale()}) ? $geo_zone->title->{current_locale()} : $geo_zone->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @include('back.layouts.partials.language-inputs', [
                                            'type' => 'textarea',
                                            'title' => __('back/app.payments.short_desc') . '<span class="small text-gray">' . __('back/app.payments.short_desc_label') . '</span>',
                                            'tab' => 'cod-tab-short-description',
                                            'input' => 'short_description',
                                            'id' => 'cod-short-description'
                                            ])

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cod-price">{{ __('back/app.payments.sort_order') }}</label>
                                        <input type="text" class="form-control" id="cod-sort-order" name="sort_order">
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="padding-top: 37px;">
                                    <div class="form-group">
                                        <label class="css-control css-control-sm css-control-success css-switch res">
                                            <input type="checkbox" class="css-control-input" id="cod-status" name="status">
                                            <span class="css-control-indicator"></span> {{ __('back/app.payments.status_title') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="cod-code" name="code" value="cod">
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        {{ __('back/app.payments.cancel') }} <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_cod();">
                        {{ __('back/app.payments.save') }} <i class="fa fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('payment-modal-js')
    <script>
        $(() => {
            $('#cod-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });
        /**
         *
         */
        function create_cod() {
            let titles = {};
            let short = {};
            let desc = {};

            {!! ag_lang() !!}.forEach(function(lang) {
                titles[lang.code] = document.getElementById('cod-title-' + lang.code).value;
                short[lang.code] = document.getElementById('cod-short-description-' + lang.code).value;
                desc[lang.code] = null; //document.getElementById('wspay-description-' + lang.code).value;
            });

            let item = {
                title: titles,
                code: $('#cod-code').val(),
                min: $('#cod-min').val(),
                data: {
                    price: $('#cod-price').val(),
                    short_description: short,
                    description: desc,
                },
                geo_zone: $('#cod-geo-zone').val(),
                status: $('#cod-status')[0].checked,
                sort_order: $('#cod-sort-order').val()
            };

            axios.post("{{ route('api.payment.store') }}", {data: item})
            .then(response => {
                if (response.data.success) {
                    location.reload();
                } else {
                    return errorToast.fire(response.data.message);
                }
            });
        }

        /**
         *
         * @param item
         */
        function edit_cod(item) {
            $('#cod-min').val(item.min);
            $('#cod-price').val(item.data.price);
            $('#cod-sort-order').val(item.sort_order);
            $('#cod-code').val(item.code);

            {!! ag_lang() !!}.forEach((lang) => {
                if (item.title && typeof item.title[lang.code] !== undefined) {
                    $('#cod-title-' + lang.code).val(item.title[lang.code]);
                }
                if (item.data.short_description && typeof item.data.short_description[lang.code] !== undefined) {
                    $('#cod-short-description-' + lang.code).val(item.data.short_description[lang.code]);
                }
                if (item.data.description && typeof item.data.description[lang.code] !== undefined) {
                    $('#cod-description-' + lang.code).val(item.data.description[lang.code]);
                }
            });

            if (item.status) {
                $('#cod-status')[0].checked = item.status ? true : false;
            }
        }
    </script>
@endpush
