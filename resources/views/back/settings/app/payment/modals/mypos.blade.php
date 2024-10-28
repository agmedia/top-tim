<div class="modal fade" id="payment-modal-mypos" tabindex="-1" role="dialog" aria-labelledby="modal-payment-modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">{{ __('back/app.payments.mypos') }}</h3>
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
                                                    'tab' => 'mypos-tab-title',
                                                    'input' => 'title',
                                                    'id' => 'mypos-title'
                                                    ])

                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="mypos-min">{{ __('back/app.payments.min_order_amount') }}</label>
                                        <input type="text" class="form-control" id="mypos-min" name="min">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="mypos-geo-zone">{{ __('back/app.payments.geo_zone') }} <span class="small text-gray">{{ __('back/app.payments.geo_zone_label') }}</span></label>
                                    <select class="js-select2 form-control" id="mypos-geo-zone" name="mypos_geo_zone" style="width: 100%;" data-placeholder="{{ __('back/app.payments.select_geo') }}">
                                        <option></option>
                                        @foreach ($geo_zones as $geo_zone)
                                            <option value="{{ $geo_zone->id }}" {{ ((isset($shipping)) and ($shipping->geo_zone == $geo_zone->id)) ? 'selected' : '' }}>{{ isset($geo_zone->title->{current_locale()}) ? $geo_zone->title->{current_locale()} : $geo_zone->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="mypos-price">{{ __('back/app.payments.fee_amount') }}</label>
                                        <input type="text" class="form-control" id="mypos-price" name="data['price']">
                                    </div>
                                </div>
                            </div>

                            @include('back.layouts.partials.language-inputs', [
                                            'type' => 'textarea',
                                            'title' => __('back/app.payments.short_desc') . '<span class="small text-gray">' . __('back/app.payments.short_desc_label') . '</span>',
                                            'tab' => 'mypos-tab-short-description',
                                            'input' => 'short_description',
                                            'id' => 'mypos-short-description'
                                            ])





                            <div class="block block-themed block-transparent mb-4">
                                <div class="block-content bg-body pb-3">
                                    <div class="row justify-content-center">
                                        <div class="col-md-11">


                                            <div class="form-group">
                                                <label for="mypos_virtual_configuration_package_test">Configuration Package Test:</label>
                                                <textarea class="form-control" id="mypos_virtual_configuration_package_test" name="mypos_virtual_configuration_package_test"> </textarea>
                                            </div>

                                            <div class="form-group">
                                                <label for="mypos_virtual_configuration_package_live">Configuration Package Live:</label>
                                                <textarea class="form-control" id="mypos_virtual_configuration_package_live" name="mypos_virtual_configuration_package_live"> </textarea>
                                            </div>




                                            <div class="form-group">
                                                <label for="mypos-shop-id">Set Url Cancel:</label>
                                                <input type="text" class="form-control" id="mypos_set_url_cancel" name="data[' mypos_set_url_cancel']">
                                            </div>
                                            <div class="form-group">
                                                <label for="mypos-secret-key">Set Url Ok:</label>
                                                <input type="text" class="form-control" id="mypos_set_url_ok" name="data['mypos_set_url_ok']">
                                            </div>

                                            <div class="form-group">
                                                <label for="mypos-callback">Set Url Notify: </label>
                                                <input type="text" class="form-control" id="mypos_set_url_notify" name="data['mypos_set_url_notify']">
                                            </div>

                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label class="d-block">Test mod.</label>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <div class="custom-control custom-radio custom-control-inline custom-control-success custom-control-lg">
                                                            <input type="radio" class="custom-control-input" id="mypos-test-on" name="my-pos-test" checked="checked" value="1">
                                                            <label class="custom-control-label" for="mypos-test-on">On</label>
                                                        </div>
                                                        <div class="custom-control custom-radio custom-control-inline custom-control-danger custom-control-lg">
                                                            <input type="radio" class="custom-control-input" id="mypos-test-off" name="my-pos-test" value="0">
                                                            <label class="custom-control-label" for="mypos-test-off">Off</label>
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
                                        <label for="mypos-price">{{ __('back/app.payments.sort_order') }}</label>
                                        <input type="text" class="form-control" id="mypos-sort-order" name="sort_order">
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="padding-top: 37px;">
                                    <div class="form-group">
                                        <label class="css-control css-control-sm css-control-success css-switch res">
                                            <input type="checkbox" class="css-control-input" id="mypos-status" name="status">
                                            <span class="css-control-indicator"></span> {{ __('back/app.payments.status_title') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="mypos-code" name="code" value="mypos">
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        {{ __('back/app.payments.cancel') }} <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_mypos();">
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
            $('#mypos-type').select2({
                minimumResultsForSearch: Infinity
            });

            $('#mypos-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });
        /**
         *
         */
        function create_mypos() {
            let titles = {};
            let short = {};
            let desc = {};

            {!! ag_lang() !!}.forEach(function(lang) {
                titles[lang.code] = document.getElementById('mypos-title-' + lang.code).value;
                short[lang.code] = document.getElementById('mypos-short-description-' + lang.code).value;
                desc[lang.code] = null; //document.getElementById('mypos-description-' + lang.code).value;
            });

            let item = {
                title: titles,
                code: $('#mypos-code').val(),
                min: $('#mypos-min').val(),
                data: {
                    price: $('#mypos-price').val(),
                    short_description: short,
                    description: desc,
                    mypos_virtual_configuration_package_test:$('#mypos_virtual_configuration_package_test').val(),
                    mypos_virtual_configuration_package_live:$('#mypos_virtual_configuration_package_live').val(),
                    mypos_set_url_cancel: $('#mypos_set_url_cancel').val(),
                    mypos_set_url_ok: $('#mypos_set_url_ok').val(),
                    mypos_set_url_notify: $('#mypos_set_url_notify').val(),
                    type: $('#mypos-type').val(),
                    callback: $('#mypos-callback').val(),
                    test: $("input[name='my-pos-test']:checked").val(),
                },
                geo_zone: $('#mypos-geo-zone').val(),
                status: $('#mypos-status')[0].checked,
                sort_order: $('#mypos-sort-order').val()
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
        function edit_mypos(item) {
            $('#mypos-min').val(item.min);
            $('#mypos-price').val(item.data.price);

            $('#mypos_virtual_configuration_package_test').val(item.data.mypos_virtual_configuration_package_test);
            $('#mypos_virtual_configuration_package_live').val(item.data.mypos_virtual_configuration_package_live);
            $('#mypos_set_url_cancel').val(item.data.mypos_set_url_cancel);
            $('#mypos_set_url_ok').val(item.data.mypos_set_url_ok);
            $('#mypos_set_url_notify').val(item.data.mypos_set_url_notify);

            $("input[name=my_pos_test][value='" + item.data.test + "']").prop("checked",true);

            $('#mypos-type').val(item.data.type);
            $('#mypos-type').trigger('change');
            $('#mypos-geo-zone').val(item.geo_zone);
            $('#mypos-geo-zone').trigger('change');

            $('#mypos-sort-order').val(item.sort_order);
            $('#mypos-code').val(item.code);

            {!! ag_lang() !!}.forEach((lang) => {
                if (item.title && typeof item.title[lang.code] !== undefined) {
                    $('#mypos-title-' + lang.code).val(item.title[lang.code]);
                }
                if (item.data.short_description && typeof item.data.short_description[lang.code] !== undefined) {
                    $('#mypos-short-description-' + lang.code).val(item.data.short_description[lang.code]);
                }
                if (item.data.description && typeof item.data.description[lang.code] !== undefined) {
                    $('#mypos-description-' + lang.code).val(item.data.description[lang.code]);
                }
            });

            if (item.status) {
                $('#mypos-status')[0].checked = item.status ? true : false;
            }
        }
    </script>
@endpush
