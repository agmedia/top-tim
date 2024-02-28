<div class="modal fade" id="shipment-modal-pickup" tabindex="-1" role="dialog" aria-labelledby="modal-shipment-modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">{{ __('back/app.shipping.store_pickup') }}</h3>
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
                                    <div class="form-group">
                                        <label for="pickup-title">{{ __('back/app.shipping.input_title') }}</label>
                                        <input type="text" class="form-control" id="pickup-title" name="title">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pickup-price">{{ __('back/app.shipping.trosak') }}</label>
                                        <input type="text" class="form-control" id="pickup-price" name="data['price']">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="pickup-geo-zone">{{ __('back/app.shipping.geo_zone') }} <span class="small text-gray">{{ __('back/app.shipping.geo_zone_label') }}</span></label>
                                    <select class="js-select2 form-control" id="pickup-geo-zone" name="geo_zone" style="width: 100%;" data-placeholder="{{ __('back/app.shipping.select_geo') }}">
                                        <option></option>
                                        @foreach ($geo_zones as $geo_zone)
                                            <option value="{{ $geo_zone->id }}" {{ ((isset($shipping)) and ($shipping->geo_zone == $geo_zone->id)) ? 'selected' : '' }}>{{ $geo_zone->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="pickup-time">{{ __('back/app.shipping.trajanje') }} <span class="small text-gray">{{ __('back/app.shipping.trajanje_label') }}</span></label>
                                <input type="text" class="form-control" id="pickup-time" name="data['time']">
                            </div>

                            <div class="form-group mb-4">
                                <label for="pickup-short-description">{{ __('back/app.shipping.short_desc') }} <span class="small text-gray">{{ __('back/app.shipping.short_desc_label') }}</span></label>
                                <textarea class="js-maxlength form-control" id="pickup-short-description" name="data['short_description']" rows="2" maxlength="160" data-always-show="true" data-placement="top"></textarea>
                                <small class="form-text text-muted">
                                    {{ __('back/app.shipping.160_max') }}
                                </small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="pickup-description">{{ __('back/app.shipping.long_desc') }} <span class="small text-gray">{{ __('back/app.shipping.long_desc_label') }}</span></label>
                                <textarea class="form-control" id="pickup-description" name="data['description']" rows="4"></textarea>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pickup-price">{{ __('back/app.shipping.sort_order') }}</label>
                                        <input type="text" class="form-control" id="pickup-sort-order" name="sort_order">
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="padding-top: 37px;">
                                    <div class="form-group">
                                        <label class="css-control css-control-sm css-control-success css-switch res">
                                            <input type="checkbox" class="css-control-input" id="pickup-status" name="status">
                                            <span class="css-control-indicator"></span> {{ __('back/app.shipping.status_title') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="pickup-code" name="code" value="pickup">
                            <input type="hidden" id="pickup-geo-zone" name="geo_zone" value="1">
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        {{ __('back/app.shipping.cancel') }}  <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_pickup();">
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
            $('#pickup-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });
        /**
         *
         */
        function create_pickup() {
            let item = {
                title: $('#pickup-title').val(),
                code: $('#pickup-code').val(),
                data: {
                    price: $('#pickup-price').val(),
                    time: $('#pickup-time').val(),
                    short_description: $('#pickup-short-description').val(),
                    description: $('#pickup-description').val(),
                },
                geo_zone: $('#pickup-geo-zone').val(),
                status: $('#pickup-status')[0].checked,
                sort_order: $('#pickup-sort-order').val()
            };

            axios.post("{{ route('api.shipping.store') }}", {data: item})
            .then(response => {
                console.log(response.data)
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
            $('#pickup-title').val(item.title);
            $('#pickup-price').val(item.data.price);
            $('#pickup-time').val(item.data.time);
            $('#pickup-short-description').val(item.data.short_description);
            $('#pickup-description').val(item.data.description);
            $('#pickup-sort-order').val(item.sort_order);
            $('#pickup-code').val(item.code);

            if (item.status) {
                $('#pickup-status')[0].checked = item.status ? true : false;
            }
        }
    </script>
@endpush
