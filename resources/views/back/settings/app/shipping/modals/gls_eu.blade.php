<div class="modal fade" id="shipment-modal-gls_eu" tabindex="-1" role="dialog" aria-labelledby="modal-shipment-modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">{{ __('back/app.shipping.cod') }}</h3>
                    <div class="block-options">
                        <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center">
                        <div class="col-md-10">

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    @include('back.layouts.partials.language-inputs', [
                                                    'type' => 'input',
                                                    'title' => __('back/app.shipping.input_title'),
                                                    'tab' => 'gls_eu-tab-title',
                                                    'input' => 'title',
                                                    'id' => 'gls_eu-title'
                                                    ])
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gls_eu-price">{{ __('back/app.shipping.trosak') }}</label>
                                        <input type="text" class="form-control" id="gls_eu-price" name="data['price']">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="dm-post-edit-slug">{{ __('back/app.shipping.geo_zone') }} <span class="small text-gray">{{ __('back/app.shipping.geo_zone_label') }}</span></label>
                                    <select class="js-select2 form-control" id="gls_eu-geo-zone" name="geo_zone" style="width: 100%;" data-placeholder="{{ __('back/app.shipping.select_geo') }}">
                                        <option></option>
                                        @foreach ($geo_zones as $geo_zone)
                                            <option value="{{ $geo_zone->id }}" {{ ((isset($shipping)) and ($shipping->geo_zone == $geo_zone->id)) ? 'selected' : '' }}>{{ isset($geo_zone->title->{current_locale()}) ? $geo_zone->title->{current_locale()} : $geo_zone->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="gls_eu-time">{{ __('back/app.shipping.trajanje') }} <span class="small text-gray">{{ __('back/app.shipping.trajanje_label') }}</span></label>
                                <input type="text" class="form-control" id="gls_eu-time" name="data['time']">
                            </div>

                            @include('back.layouts.partials.language-inputs', [
                                            'type' => 'textarea',
                                            'title' => __('back/app.shipping.short_desc') . '<span class="small text-gray">' . __('back/app.shipping.short_desc_label') . '</span>',
                                            'tab' => 'gls_eu-tab-short-description',
                                            'input' => 'short_description',
                                            'id' => 'gls_eu-short-description'
                                            ])

                            <div class="form-group mb-4 d-none">
                                <label for="gls_eu-description">{{ __('back/app.shipping.long_desc') }} <span class="small text-gray">{{ __('back/app.shipping.long_desc_label') }}</span></label>
                                <textarea class="form-control" id="gls_eu-description" name="data['description']" rows="4"></textarea>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gls_eu-price">{{ __('back/app.shipping.sort_order') }}</label>
                                        <input type="text" class="form-control" id="gls_eu-sort-order" name="sort_order">
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="padding-top: 37px;">
                                    <div class="form-group">
                                        <label class="css-control css-control-sm css-control-success css-switch res">
                                            <input type="checkbox" class="css-control-input" id="gls_eu-status" name="status">
                                            <span class="css-control-indicator"></span> {{ __('back/app.shipping.status_title') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="gls_eu-code" name="code" value="gls_eu">
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        {{ __('back/app.shipping.cancel') }} <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_gls_eu();">
                        {{ __('back/app.shipping.save') }} <i class="fa fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('shipment-modal-js')
    <script>
        $(() => {
            $('#gls_eu-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });
        /**
         *
         */
        function create_gls_eu() {
            let titles = {};
            let short = {};
            let desc = {};

            {!! ag_lang() !!}.forEach(function(lang) {
                titles[lang.code] = document.getElementById('gls_eu-title-' + lang.code).value;
                short[lang.code] = document.getElementById('gls_eu-short-description-' + lang.code).value;
                desc[lang.code] = null; //document.getElementById('flat-description-' + lang.code).value;
            });

            let item = {
                title: titles,
                code: $('#gls_eu-code').val(),
                data: {
                    price: $('#gls_eu-price').val(),
                    time: $('#gls_eu-time').val(),
                    short_description: short,
                    description: desc,
                },
                geo_zone: $('#gls_eu-geo-zone').val(),
                status: $('#gls_eu-status')[0].checked,
                sort_order: $('#gls_eu-sort-order').val()
            };

            axios.post("{{ route('api.shipping.store') }}", {data: item})
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
        function edit_gls_eu(item) {
            $('#gls_eu-price').val(item.data.price);
            $('#gls_eu-time').val(item.data.time);
            $('#gls_eu-sort-order').val(item.sort_order);
            $('#gls_eu-code').val(item.code);

            {!! ag_lang() !!}.forEach((lang) => {
                if (item.title && typeof item.title[lang.code] !== undefined) {
                    $('#gls_eu-title-' + lang.code).val(item.title[lang.code]);
                }
                if (item.data.short_description && typeof item.data.short_description[lang.code] !== undefined) {
                    $('#gls_eu-short-description-' + lang.code).val(item.data.short_description[lang.code]);
                }
                if (item.data.description && typeof item.data.description[lang.code] !== undefined) {
                    $('#gls_eu-description-' + lang.code).val(item.data.description[lang.code]);
                }
            });

            if (item.status) {
                $('#gls_eu-status')[0].checked = item.status ? true : false;
            }
        }
    </script>
@endpush
