@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">

    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/magnific-popup/magnific-popup.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/products.artikli') }}</h1>
                <span id="bulk_actions" style="display: none; margin-right: 20px;">
                    <a class="btn btn-alt-danger my-2 mx-2" href="javascript:bulkAction('delete');">
                        <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> Obriši selektirane</span>
                    </a>
                    {{--<a class="btn btn-alt-success my-2 mx-2" href="javascript:bulkAction('copy');">
                        <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> Kopiraj selektirane</span>
                    </a>--}}
                </span>
                <a class="btn btn-hero-success my-2" href="{{ route('products.create') }}">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/products.novi_artikl') }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="content">
    @include('back.layouts.partials.session')

    <!-- All Products -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('back/products.svi_artikli') }} {{ $products->total() }}</h3>
                <div class="block-options">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary mr-3" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <a class="btn btn-sm btn-outline-secondary btn-inline-block" href="{{route('products')}}"><i class=" ci-trash"></i> {{ __('back/products.ocisti_filtere') }}</a>
                    </div>
                </div>
            </div>
            <div class="collapse" id="collapseExample">
                <div class="block-content bg-body-dark">
                    <form action="{{ route('products') }}" method="get">

                        <div class="form-group row items-push mb-0">
                            <div class="col-md-9 mb-0">
                                <div class="form-group">
                                    <div class="input-group flex-nowrap">
                                        <input type="text" class="form-control py-3 text-center" name="search" id="search-input" value="{{ request()->input('search') }}" placeholder="{{ __('back/products.upisi_pojam_pretrazivanja') }}">
                                        <button type="submit" class="btn btn-primary fs-base" onclick="setURL('search', $('#search-input').val());"><i class="fa fa-search"></i> </button>
                                    </div>
                                    <div class="form-text small">{{ __('back/products.pretrazi_po_imenu') }}</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <select class="js-select2 form-control" id="category-select" name="category" style="width: 100%;" data-placeholder="{{ __('back/products.odaberi_kategoriju') }}">
                                        <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                                        @foreach ($categories as $group => $cats)
                                            @foreach ($cats as $id => $category)
                                                <option value="{{ $id }}" class="font-weight-bold small" {{ $id == request()->input('category') ? 'selected' : '' }}>{{ $group . ' >> ' . $category['title'] }}</option>
                                                @if ( ! empty($category['subs']))
                                                    @foreach ($category['subs'] as $sub_id => $subcategory)
                                                        <option value="{{ $sub_id }}" class="pl-3 text-sm" {{ $sub_id == request()->input('category') ? 'selected' : '' }}>{{ $subcategory['title'] }}</option>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="form-group row items-push mb-0">
                            <div class="col-md-4">
                                <div class="form-group">
                                    @livewire('back.layout.search.author-search', ['brand_id' => request()->input('brand') ?: '', 'list' => true])
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <select class="js-select2 form-control" id="status-select" name="status" style="width: 100%;" data-placeholder="{{ __('back/products.odaberi_status') }}">
                                        <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                                        <option value="all" {{ 'all' == request()->input('status') ? 'selected' : '' }}>{{ __('back/products.svi_artikli') }}</option>
                                        <option value="active" {{ 'active' == request()->input('status') ? 'selected' : '' }}>{{ __('back/products.aktivni') }}</option>
                                        <option value="inactive" {{ 'inactive' == request()->input('status') ? 'selected' : '' }}>{{ __('back/products.neaktivni') }}</option>
                                        <option value="with_action" {{ 'with_action' == request()->input('status') ? 'selected' : '' }}>{{ __('back/products.sa_akcijama') }}</option>
                                        <option value="without_action" {{ 'without_action' == request()->input('status') ? 'selected' : '' }}>{{ __('back/products.bez_akcija') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <select class="js-select2 form-control" id="sort-select" name="sort" style="width: 100%;" data-placeholder="{{ __('back/products.sortiraj_artikle') }}">
                                        <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                                        <option value="new" {{ 'new' == request()->input('sort') ? 'selected' : '' }}>{{ __('back/products.najnovije') }}</option>
                                        <option value="old" {{ 'old' == request()->input('sort') ? 'selected' : '' }}>{{ __('back/products.najstarije') }}</option>
                                        <option value="price_up" {{ 'price_up' == request()->input('sort') ? 'selected' : '' }}>{{ __('back/products.cijena_od_manje') }}</option>
                                        <option value="price_down" {{ 'price_down' == request()->input('sort') ? 'selected' : '' }}>{{ __('back/products.cijena_od_vise') }}</option>
                                        <option value="az" {{ 'az' == request()->input('sort') ? 'selected' : '' }}>{{ __('back/products.a_z') }}</option>
                                        <option value="za" {{ 'za' == request()->input('sort') ? 'selected' : '' }}>{{ __('back/products.z_a') }}</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter">
                        <thead>
                        <tr>
                            <th class="text-center" style="width: 30px;">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="checkAll" name="selected">
                                    </div>
                                </div>
                            </th>
                            <th class="text-center" style="width: 100px;">{{ __('back/products.slika') }}</th>
                            <th>{{ __('back/products.naziv') }}</th>
                            <th>{{ __('back/products.sifra') }}</th>
                            <th class="text-right">{{ __('back/products.cijena') }}</th>

                            <th class="text-center">{{ __('back/products.kol') }}</th>
                            <th>{{ __('back/products.dodano') }}</th>
                            <th>{{ __('back/products.zadnja_izmjena') }}</th>
                            <th class="text-center">{{ __('back/products.status') }}</th>
                            <th class="text-right" style="width: 15%;">{{ __('back/products.uredi') }}</th>
                        </tr>
                        </thead>
                        <tbody id="ag-table-with-input-fields" class="js-gallery" >
                        @forelse ($products as $product)
                            <tr>
                                <td class="text-center">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $product->id }}" id="selected[{{ $product->id }}]" name="selected">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center font-size-sm">
                                    <a class="img-link img-link-zoom-in img-lightbox" href="{{ $product->image ? asset($product->image) : asset('media/avatars/avatar0.jpg') }}">
                                        <img src="{{ $product->image ? asset($product->image) : asset('media/avatars/avatar0.jpg') }}" height="80px"/>
                                    </a>
                                </td>
                                <td class="font-size-sm">
                                    <a class="font-w600" href="{{ route('products.edit', ['product' => $product]) }}">{{ $product->translation->name }}</a><br>
                                    @if ($product->categories)
                                        @foreach ($product->categories as $cat)
                                            <span class="badge badge-secondary">{{ $cat->title }}</span>
                                        @endforeach
                                    @endif
                                    @if ($product->subcategory())
                                        <span class="badge badge-secondary">{{ $product->subcategory()->title }}</span>
                                    @endif
                                </td>
                                <td class="font-size-sm">{{ $product->sku }}</td>
                                <td class="font-size-sm text-right">
                                    <ag-input-field item="{{ $product }}" target="price"></ag-input-field>
                                </td>

                                <td class="font-size-sm text-center">{{ $product->quantity }}</td>
                                <td class="font-size-sm">{{ \Illuminate\Support\Carbon::make($product->created_at)->format('d.m.Y') }}</td>
                                <td class="font-size-sm">{{ \Illuminate\Support\Carbon::make($product->updated_at)->format('d.m.Y') }}</td>
                                <td class="text-center font-size-sm">
                                    {{--@include('back.layouts.partials.status', ['status' => $product->status])--}}
                                    <div class="custom-control custom-switch custom-control-success mb-1">
                                        <input type="checkbox" class="custom-control-input" id="status-{{ $product->id }}" onclick="setStatus({{ $product->id }})" name="status" @if ($product->status) checked="" @endif>
                                        <label class="custom-control-label" for="status-{{ $product->id }}"></label>
                                    </div>
                                </td>
                                <td class="text-right font-size-sm">
                                    <a class="btn btn-sm btn-alt-secondary" target="_blank" href="{{ url($product->translation->url) }}">
                                        <i class="fa fa-fw fa-eye"></i>
                                    </a>
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('products.edit', ['product' => $product]) }}">
                                        <i class="fa fa-fw fa-pencil-alt"></i>
                                    </a>
                                    <button class="btn btn-sm btn-alt-danger" onclick="event.preventDefault(); deleteItem({{ $product->id }}, '{{ route('products.destroy.api') }}');"><i class="fa fa-fw fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center font-size-sm" colspan="12">
                                    <label>{{ __('back/products.nema_proizvoda') }}</label>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection

@push('js_after')
    <script src="{{ asset('js/ag-input-field.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/magnific-popup/jquery.magnific-popup.min.js') }}"></script>

    <!-- Page JS Helpers (Magnific Popup Plugin) -->
    <script>jQuery(function(){Dashmix.helpers('magnific-popup');});</script>

    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(() => {
            $('#category-select').select2({
                placeholder: '{{ __('back/products.odaberi_kategoriju') }}',
                allowClear: true
            });
            $('#status-select').select2({
                placeholder: '{{ __('back/products.odaberi_status') }}',
                allowClear: true
            });
            $('#sort-select').select2({
                placeholder: '{{ __('back/products.sortiraj_artikle') }}',
                allowClear: true
            });

            //
            $('#category-select').on('change', (e) => {
                console.log(e.currentTarget.selectedOptions[0])
                setURL('category', e.currentTarget.selectedOptions[0]);
            });
            $('#status-select').on('change', (e) => {
                setURL('status', e.currentTarget.selectedOptions[0]);
            });
            $('#sort-select').on('change', (e) => {
                setURL('sort', e.currentTarget.selectedOptions[0]);
            });

            //
            Livewire.on('brandSelect', (e) => {
                setURL('brand', e.brand.id, true);
            });
            Livewire.on('publisherSelect', (e) => {
                setURL('publisher', e.publisher.id, true);
            });

            /*$('#btn-inactive').on('click', () => {
                setRegularURL('active', false);
            });
            $('#btn-today').on('click', () => {
                setRegularURL('today', true);
            });
            $('#btn-week').on('click', () => {
                setRegularURL('week', true);
            });*/

            $('input[name=selected]').on('change', (e) => {
                console.log(e.currentTarget.value)
                let checkedBoxes = document.querySelectorAll('input[name=selected]:checked');

                if (checkedBoxes.length) {
                    document.getElementById('bulk_actions').style.display = 'block';
                } else {
                    document.getElementById('bulk_actions').style.display = 'none';
                }
            })

            //
            $("#checkAll").click(function () {
                $('input[name=selected]:checkbox').not(this).prop('checked', this.checked);
            });
        });

        function bulkAction(type) {
            let products = getSelectedProducts();

            axios.get('{{ route('products.bulk.action') }}' + '?products=' + products + '&type=' + type)
            .then((response) => {
                location.reload();
            })
            .catch((e) => {
                return errorToast.fire();
            })
        }

        /**
         *
         * @returns {string}
         */
        function getSelectedProducts() {
            let orders = '[';
            let checkedBoxes = document.querySelectorAll('input[name=selected]:checked');

            for (let i = 0; i < checkedBoxes.length; i++) {
                if (checkedBoxes.length - 1 == i) {
                    orders += checkedBoxes[i].value + ']';
                } else {
                    orders += checkedBoxes[i].value + ','
                }
            }

            return orders;
        }

        /**
         *
         * @param type
         * @param search
         */
        function setURL(type, search, isValue = false) {
            let url = new URL(location.href);
            let params = new URLSearchParams(url.search);
            let keys = [];

            for(var key of params.keys()) {
                if (key === type) {
                    keys.push(key);
                }
            }

            keys.forEach((value) => {
                if (params.has(value)) {
                    params.delete(value);
                }
            })

            if (search.value) {
                params.append(type, search.value);
            }

            if (isValue && search) {
                params.append(type, search);
            }

            url.search = params;
            location.href = url;
        }

        /**
         *
         * @param type
         * @param search
         */
        function setRegularURL(type, search) {
            let searches = ['active', 'today', 'week'];
            let url = new URL(location.href);
            let params = new URLSearchParams(url.search);
            let keys = [];

            for(var key of params.keys()) {
                if (key === type) {
                    keys.push(key);
                }
            }

            keys.forEach((value) => {
                if (params.has(value)) {
                    params.delete(value);
                }
            })

            params.append(type, search);

            url.search = params;
            location.href = url;
        }

        /**
         *
         * @param id
         */
        function setStatus(id) {
            let val = $('#status-' + id)[0].checked;

            axios.post("{{ route('products.change.status') }}", { id: id, value: val })
            .then((response) => {
                successToast.fire()
            })
            .catch((error) => {
                errorToast.fire()
            });
        }
    </script>

@endpush
