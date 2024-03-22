<div class="modal fade" id="payment-modal-wspay" tabindex="-1" role="dialog" aria-labelledby="modal-payment-modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">{{ __('back/app.payments.wspay') }}</h3>
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
                                                    'tab' => 'wspay-tab-title',
                                                    'input' => 'title',
                                                    'id' => 'wspay-title'
                                                    ])

                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wspay-min">{{ __('back/app.payments.min_order_amount') }}</label>
                                        <input type="text" class="form-control" id="wspay-min" name="min">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="wspay-geo-zone">{{ __('back/app.payments.geo_zone') }} <span class="small text-gray">{{ __('back/app.payments.geo_zone_label') }}</span></label>
                                    <select class="js-select2 form-control" id="wspay-geo-zone" name="wspay_geo_zone" style="width: 100%;" data-placeholder="{{ __('back/app.payments.select_geo') }}">
                                        <option></option>
                                        @foreach ($geo_zones as $geo_zone)
                                            <option value="{{ $geo_zone->id }}" {{ ((isset($shipping)) and ($shipping->geo_zone == $geo_zone->id)) ? 'selected' : '' }}>{{ isset($geo_zone->title->{current_locale()}) ? $geo_zone->title->{current_locale()} : $geo_zone->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wspay-price">{{ __('back/app.payments.fee_amount') }}</label>
                                        <input type="text" class="form-control" id="wspay-price" name="data['price']">
                                    </div>
                                </div>
                            </div>

                            @include('back.layouts.partials.language-inputs', [
                                            'type' => 'textarea',
                                            'title' => __('back/app.payments.short_desc') . '<span class="small text-gray">' . __('back/app.payments.short_desc_label') . '</span>',
                                            'tab' => 'wspay-tab-short-description',
                                            'input' => 'short_description',
                                            'id' => 'wspay-short-description'
                                            ])


                            <div class="block block-themed block-transparent mb-4">
                                <div class="block-content bg-body pb-3">
                                    <div class="row justify-content-center">
                                        <div class="col-md-11">
                                            <div class="form-group">
                                                <label for="wspay-shop-id">ShopID:</label>
                                                <input type="text" class="form-control" id="wspay-shop-id" name="data['shop_id']">
                                            </div>
                                            <div class="form-group">
                                                <label for="wspay-secret-key">SecretKey:</label>
                                                <input type="text" class="form-control" id="wspay-secret-key" name="data['secret_key']">
                                            </div>

                                            <div class="form-group">
                                                <label for="wspay-callback">CallbackURL: </label>
                                                <input type="text" class="form-control" id="wspay-callback" name="data['callback']" value="{{ url('/') }}">
                                            </div>

                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label class="d-block">Test mod.</label>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <div class="custom-control custom-radio custom-control-inline custom-control-success custom-control-lg">
                                                            <input type="radio" class="custom-control-input" id="wspay-test-on" name="test" checked="" value="1">
                                                            <label class="custom-control-label" for="wspay-test-on">On</label>
                                                        </div>
                                                        <div class="custom-control custom-radio custom-control-inline custom-control-danger custom-control-lg">
                                                            <input type="radio" class="custom-control-input" id="wspay-test-off" name="test" value="0">
                                                            <label class="custom-control-label" for="wspay-test-off">Off</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="wspay-price">{{ __('back/app.payments.sort_order') }}</label>
                                        <input type="text" class="form-control" id="wspay-sort-order" name="sort_order">
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="padding-top: 37px;">
                                    <div class="form-group">
                                        <label class="css-control css-control-sm css-control-success css-switch res">
                                            <input type="checkbox" class="css-control-input" id="wspay-status" name="status">
                                            <span class="css-control-indicator"></span> {{ __('back/app.payments.status_title') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="wspay-code" name="code" value="wspay">
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        {{ __('back/app.payments.cancel') }} <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_wspay();">
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
            $('#wspay-type').select2({
                minimumResultsForSearch: Infinity
            });

            $('#wspay-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });
        /**
         *
         */
        function create_wspay() {
            let titles = {};
            let short = {};
            let desc = {};

            {!! ag_lang() !!}.forEach(function(lang) {
                titles[lang.code] = document.getElementById('wspay-title-' + lang.code).value;
                short[lang.code] = document.getElementById('wspay-short-description-' + lang.code).value;
                desc[lang.code] = null; //document.getElementById('wspay-description-' + lang.code).value;
            });

            let item = {
                title: titles,
                code: $('#wspay-code').val(),
                min: $('#wspay-min').val(),
                data: {
                    price: $('#wspay-price').val(),
                    short_description: short,
                    description: desc,
                    shop_id: $('#wspay-shop-id').val(),
                    secret_key: $('#wspay-secret-key').val(),
                    type: $('#wspay-type').val(),
                    callback: $('#wspay-callback').val(),
                    test: $("input[name='test']:checked").val(),
                },
                geo_zone: $('#wspay-geo-zone').val(),
                status: $('#wspay-status')[0].checked,
                sort_order: $('#wspay-sort-order').val()
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
        function edit_wspay(item) {
            $('#wspay-min').val(item.min);
            $('#wspay-price').val(item.data.price);

            $('#wspay-shop-id').val(item.data.shop_id);
            $('#wspay-secret-key').val(item.data.secret_key);
            $('#wspay-callback').val(item.data.callback);

            $("input[name=test][value='" + item.data.test + "']").prop("checked",true);

            $('#wspay-type').val(item.data.type);
            $('#wspay-type').trigger('change');
            $('#wspay-geo-zone').val(item.geo_zone);
            $('#wspay-geo-zone').trigger('change');

            $('#wspay-sort-order').val(item.sort_order);
            $('#wspay-code').val(item.code);

            {!! ag_lang() !!}.forEach((lang) => {
                if (item.title && typeof item.title[lang.code] !== undefined) {
                    $('#wspay-title-' + lang.code).val(item.title[lang.code]);
                }
                if (item.data.short_description && typeof item.data.short_description[lang.code] !== undefined) {
                    $('#wspay-short-description-' + lang.code).val(item.data.short_description[lang.code]);
                }
                if (item.data.description && typeof item.data.description[lang.code] !== undefined) {
                    $('#wspay-description-' + lang.code).val(item.data.description[lang.code]);
                }
            });

            if (item.status) {
                $('#wspay-status')[0].checked = item.status ? true : false;
            }
        }
    </script>
@endpush
