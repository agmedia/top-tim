<div class="modal fade" id="payment-modal-pickup" tabindex="-1" role="dialog" aria-labelledby="modal-payment-modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">Isporuka pouzećem</h3>
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
                                                    'tab' => 'pickup-tab-title',
                                                    'input' => 'title',
                                                    'id' => 'pickup-title'
                                                    ])
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pickup-min">Min. iznos narudžbe</label>
                                        <input type="text" class="form-control" id="pickup-min" name="min">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pickup-price">Iznos naknade</label>
                                        <input type="text" class="form-control" id="pickup-price" name="data['price']">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="pickup-geo-zone">Geo zona <span class="small text-gray">(Geo zona na koju se odnosi dostava..)</span></label>
                                    <select class="js-select2 form-control" id="pickup-geo-zone" name="geo_zone" style="width: 100%;" data-placeholder="Odaberite geo zonu">
                                        <option></option>
                                        @foreach ($geo_zones as $geo_zone)
                                            <option value="{{ $geo_zone->id }}" {{ ((isset($payment->geo_zone)) and ($payment->geo_zone == $geo_zone->id)) ? 'selected' : '' }}>{{ isset($geo_zone->title->{current_locale()}) ? $geo_zone->title->{current_locale()} : $geo_zone->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @include('back.layouts.partials.language-inputs', [
                                            'type' => 'textarea',
                                            'title' => __('back/app.payments.short_desc') . '<span class="small text-gray">' . __('back/app.payments.short_desc_label') . '</span>',
                                            'tab' => 'pickup-tab-short-description',
                                            'input' => 'short_description',
                                            'id' => 'pickup-short-description'
                                            ])

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pickup-price">Poredak</label>
                                        <input type="text" class="form-control" id="pickup-sort-order" name="sort_order">
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="padding-top: 37px;">
                                    <div class="form-group">
                                        <label class="css-control css-control-sm css-control-success css-switch res">
                                            <input type="checkbox" class="css-control-input" id="pickup-status" name="status">
                                            <span class="css-control-indicator"></span> Status načina plaćanja
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="pickup-code" name="code" value="pickup">
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        Odustani <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_pickup();">
                        Snimi <i class="fa fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('payment-modal-js')
    <script>
        $(() => {
            $('#pickup-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });
        /**
         *
         */
        function create_pickup() {
            let titles = {};
            let short = {};
            let desc = {};

            {!! ag_lang() !!}.forEach(function(lang) {
                titles[lang.code] = document.getElementById('pickup-title-' + lang.code).value;
                short[lang.code] = document.getElementById('pickup-short-description-' + lang.code).value;
                desc[lang.code] = null; //document.getElementById('wspay-description-' + lang.code).value;
            });

            let item = {
                title: titles,
                code: $('#pickup-code').val(),
                min: $('#pickup-min').val(),
                data: {
                    price: $('#pickup-price').val(),
                    short_description: short,
                    description: desc,
                },
                geo_zone: $('#pickup-geo-zone').val(),
                status: $('#pickup-status')[0].checked,
                sort_order: $('#pickup-sort-order').val()
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
        function edit_pickup(item) {
            $('#pickup-min').val(item.min);
            $('#pickup-price').val(item.data.price);
            $('#pickup-sort-order').val(item.sort_order);
            $('#pickup-code').val(item.code);

            {!! ag_lang() !!}.forEach((lang) => {
                if (item.title && typeof item.title[lang.code] !== undefined) {
                    $('#pickup-title-' + lang.code).val(item.title[lang.code]);
                }
                if (item.data.short_description && typeof item.data.short_description[lang.code] !== undefined) {
                    $('#pickup-short-description-' + lang.code).val(item.data.short_description[lang.code]);
                }
                if (item.data.description && typeof item.data.description[lang.code] !== undefined) {
                    $('#pickup-description-' + lang.code).val(item.data.description[lang.code]);
                }
            });

            if (item.status) {
                $('#pickup-status')[0].checked = item.status ? true : false;
            }
        }
    </script>
@endpush
