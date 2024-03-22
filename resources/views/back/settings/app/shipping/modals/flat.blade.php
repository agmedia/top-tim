<div class="modal fade" id="shipment-modal-flat" tabindex="-1" role="dialog" aria-labelledby="modal-shipment-modal" aria-hidden="true">
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
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="flat-title" class="w-100">{{ __('back/app.payments.input_title') }}
                                            <ul class="nav nav-pills float-right">
                                                @foreach(ag_lang() as $lang)
                                                    <li @if ($lang->code == current_locale()) class="active" @endif ">
                                                    <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#title-{{ $lang->code }}">
                                                        <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                    </a>
                                                    </li>
                                                @endforeach
                                            </ul>

                                        </label>
                                        <div class="tab-content">
                                            @foreach(ag_lang() as $lang)
                                                <div id="title-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                    <input type="text" class="form-control" id="flat-title-{{ $lang->code }}" name="flat-title[{{ $lang->code }}]" placeholder="{{ $lang->code }}"  >
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="flat-price">{{ __('back/app.shipping.trosak') }}</label>
                                        <input type="text" class="form-control" id="flat-price" name="data['price']">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="dm-post-edit-slug">{{ __('back/app.shipping.geo_zone') }} <span class="small text-gray">{{ __('back/app.shipping.geo_zone_label') }}</span></label>
                                    <select class="js-select2 form-control" id="flat-geo-zone" name="geo_zone" style="width: 100%;" data-placeholder="{{ __('back/app.shipping.select_geo') }}">
                                        <option></option>
                                        @foreach ($geo_zones as $geo_zone)
                                            <option value="{{ $geo_zone->id }}" {{ ((isset($shipping)) and ($shipping->geo_zone == $geo_zone->id)) ? 'selected' : '' }}>{{ isset($geo_zone->title->{current_locale()}) ? $geo_zone->title->{current_locale()} : $geo_zone->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="flat-time">{{ __('back/app.shipping.trajanje') }} <span class="small text-gray">{{ __('back/app.shipping.trajanje_label') }}</span></label>
                                <input type="text" class="form-control" id="flat-time" name="data['time']">
                            </div>

                            <div class="form-group mb-4">
                                <label for="bank-short-description" class="w-100">{{ __('back/app.payments.short_desc') }} <span class="small text-gray">{{ __('back/app.payments.short_desc_label') }}</span>
                                    <div class="float-right">
                                        <ul class="nav nav-pills float-right">
                                            @foreach(ag_lang() as $lang)
                                                <li @if ($lang->code == current_locale()) class="active" @endif ">
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#flat-description-{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </label>
                                <div class="tab-content">
                                    @foreach(ag_lang() as $lang)
                                        <div id="flat-description-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                            <textarea id="flat-short-description-{{ $lang->code }}" class=" form-control"  name="data['short_description'][{{ $lang->code }}]" placeholder="{{ $lang->code }}" ></textarea>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="form-text text-muted">
                                    160 {{ __('back/app.payments.chars') }} max
                                </small>
                            </div>

                            <div class="form-group mb-4 d-none">
                                <label for="flat-description">{{ __('back/app.shipping.long_desc') }}<span class="small text-gray"> {{ __('back/app.shipping.long_desc_label') }}</span></label>
                                <textarea class="form-control" id="flat-description" name="data['description']" rows="4"></textarea>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="flat-price">{{ __('back/app.shipping.sort_order') }}</label>
                                        <input type="text" class="form-control" id="flat-sort-order" name="sort_order">
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="padding-top: 37px;">
                                    <div class="form-group">
                                        <label class="css-control css-control-sm css-control-success css-switch res">
                                            <input type="checkbox" class="css-control-input" id="flat-status" name="status">
                                            <span class="css-control-indicator"></span> {{ __('back/app.shipping.status_title') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="flat-code" name="code" value="flat">
                            <input type="hidden" id="flat-geo-zone" name="geo_zone" value="1">
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        {{ __('back/app.shipping.cancel') }} <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_flat();">
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
            $('#flat-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });
        /**
         *
         */
        function create_flat() {
            let titles = {};
            let short = {};
            let desc = {};

            {!! ag_lang() !!}.forEach(function(lang) {
                titles[lang.code] = document.getElementById('flat-title-' + lang.code).value;
                short[lang.code] = document.getElementById('flat-short-description-' + lang.code).value;
                desc[lang.code] = document.getElementById('flat-description-' + lang.code).value;
            });

            let item = {
                title: titles,
                code: $('#flat-code').val(),
                data: {
                    price: $('#flat-price').val(),
                    time: $('#flat-time').val(),
                    short_description: short,
                    description: desc,
                },
                geo_zone: $('#flat-geo-zone').val(),
                status: $('#flat-status')[0].checked,
                sort_order: $('#flat-sort-order').val()
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
        function edit_flat(item) {
            $('#flat-price').val(item.data.price);
            $('#flat-time').val(item.data.time);
            $('#flat-sort-order').val(item.sort_order);
            $('#flat-code').val(item.code);

            {!! ag_lang() !!}.forEach((lang) => {
                if (item.title && typeof item.title[lang.code] !== undefined) {
                    $('#flat-title-' + lang.code).val(item.title[lang.code]);
                }
                if (item.data.short_description && typeof item.data.short_description[lang.code] !== undefined) {
                    $('#flat-short-description-' + lang.code).val(item.data.short_description[lang.code]);
                }
                if (item.data.description && typeof item.data.description[lang.code] !== undefined) {
                    $('#flat-description-' + lang.code).val(item.data.description[lang.code]);
                }
            });

            if (item.status) {
                $('#flat-status')[0].checked = item.status ? true : false;
            }
        }
    </script>
@endpush
