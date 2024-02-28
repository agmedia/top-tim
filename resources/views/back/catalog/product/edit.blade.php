@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/dropzone/min/dropzone.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/plugins/slim/slim.css') }}">

    @stack('product_css')
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/products.artikl_edit') }}</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('products') }}">{{ __('back/products.artikli') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('back/products.novi_artikl') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- Page Content -->
    <div class="content content-full">
        @include('back.layouts.partials.session')

        <!--tabs start-->



        <!-- tabs end-->

        <form action="{{ isset($product) ? route('products.update', ['product' => $product]) : route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($product))
                {{ method_field('PATCH') }}
            @endif


            <!-- Block Tabs Default Style -->
            <div class="block block-rounded">
                <ul class="nav nav-tabs nav-tabs-block" data-toggle="tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#osnovno"><i class="si si-settings"></i> {{ __('back/products.info') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#atributi"><i class="si si-settings"></i> {{ __('back/products.atributi') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#slike"><i class="si si-picture"></i> {{ __('back/products.slike') }}</a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link" href="#seo">
                            <i class="si si-link"></i> {{ __('back/products.seo') }}
                        </a>
                    </li>
                </ul>
                <div class="block-content tab-content">
                    <div class="tab-pane active" id="osnovno" role="tabpanel">
                        <div class="block">
                            <div class="block-header block-header-default">
                                <a class="btn btn-light" href="{{ route('products') }}">
                                    <i class="fa fa-arrow-left mr-1"></i> {{ __('back/products.povratak') }}
                                </a>
                                <div class="block-options">
                                    <div class="dropdown">
                                        <div class="d-none custom-control custom-switch custom-control-info block-options-item ml-4">
                                            <input type="checkbox" class="custom-control-input" id="product-gift-switch" name="gift"{{ (isset($product->gift) and $product->gift) ? 'checked' : '' }}>
                                            <label class="custom-control-label pt-1" for="product-gift-switch">Poklon Bon</label>
                                        </div>

                                        <div class=" d-none custom-control custom-switch custom-control-info block-options-item ml-4">
                                            <input type="checkbox" class="custom-control-input" id="product-decrease-switch" name="decrease"{{ (isset($product->decrease) and $product->decrease) ? '' : 'checked' }}>
                                            <label class="custom-control-label pt-1" for="product-decrease-switch">Neograničena Količina</label>
                                        </div>

                                        <div class="custom-control custom-switch custom-control-success block-options-item ml-4">
                                            <input type="checkbox" class="custom-control-input" id="product-switch" name="status"{{ (isset($product->status) and $product->status) ? 'checked' : '' }}>
                                            <label class="custom-control-label pt-1" for="product-switch">{{ __('back/products.aktiviraj') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="block-content">
                                <div class="row justify-content-center push">
                                    <div class="col-md-12">
                                        <div class="form-group row items-push mb-3">
                                            <div class="col-md-9">
                                                <label for="dm-post-edit-title">{{ __('back/products.naziv') }} <span class="text-danger">*</span></label>
                                                <ul class="nav nav-pills float-right">
                                                    @foreach(ag_lang() as $lang)
                                                        <li @if ($lang->code == current_locale()) class="active" @endif>
                                                            <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#name-{{ $lang->code }}">
                                                                <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                                <div class="tab-content">
                                                    @foreach(ag_lang() as $lang)
                                                        <div id="name-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                            <input type="text" class="form-control" id="name-input-{{ $lang->code }}" name="name[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($product) ? $product->translation($lang->code)->title : old('name.*') }}" onkeyup="SetSEOPreview()">
                                                            @error('name')
                                                            <span class="text-danger font-italic">{{ __('back/products.naziv_je_potreban') }}</span>
                                                            @enderror
                                                        </div>
                                                    @endforeach
                                                </div>



                                            </div>

                                            <div class="col-md-3">
                                                <label for="polica-input">EAN </label>
                                                <input type="text" class="form-control" id="polica-input" name="isbn" placeholder="{{ __('back/products.upisite_ean') }}" value="{{ isset($product) ? $product->isbn : old('isbn') }}" >
                                            </div>
                                        </div>
                                        <div class="form-group row items-push mb-3">

                                            <div class="col-md-3">
                                                <label for="price-input">{{ __('back/products.cijena') }} <span class="text-danger">*</span> <span class="small text-gray">({{ __('back/products.s_pdvom') }})</span></label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="price-input" name="price" placeholder="00.00" value="{{ isset($product) ? $product->price : old('price') }}">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">€</span>
                                                    </div>
                                                </div>
                                                @error('price')
                                                <span class="text-danger font-italic">{{ __('back/products.cijena_je_potrebna') }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label for="quantity-input">{{ __('back/products.kolicina') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="quantity-input" name="quantity" placeholder="{{ __('back/products.upisite_kolicinu') }}" value="{{ isset($product) ? $product->quantity : ( ! isset($product) ? 1 : old('quantity')) }}">
                                                @error('quantity ')
                                                <span class="text-danger font-italic">{{ __('back/products.kolicina_je_potrebna') }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label for="sku-input">{{ __('back/products.sifra') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="sku-input" name="sku" placeholder="{{ __('back/products.upisite_sifru') }}" value="{{ isset($product) ? $product->sku : old('sku') }}">
                                                @error('sku')
                                                <span class="text-danger font-italic">{{ __('back/products.sifra_je_potrebna') }}</span>
                                                @enderror
                                                @error('sku_dupl')
                                                <span class="text-danger small font-italic">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label for="polica-input">{{ __('back/products.sifra_police') }}</label>
                                                <input type="text" class="form-control" id="polica-input" name="polica" placeholder="{{ __('back/products.upisite_sifru_police') }}" value="{{ isset($product) ? $product->polica : old('polica') }}" >
                                            </div>



                                        </div>

                                        <div class="form-group row items-push mb-3">
                                            <div class="col-md-3">
                                                <label for="special-input">{{ __('back/products.akcija') }}</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="special-input" name="special" placeholder="00.00" value="{{ isset($product) ? $product->special : old('special') }}">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">€</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="special-from-input">{{ __('back/products.akcija_vrijedi') }}</label>
                                                <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-week-start="1" data-autoclose="true" data-today-highlight="true">
                                                    <input type="text" class="form-control" id="special-from-input" name="special_from" placeholder="{{ __('back/products.od') }}" value="{{ (isset($product->special_from) && $product->special_from != '0000-00-00 00:00:00') ? \Carbon\Carbon::make($product->special_from)->format('d.m.Y') : '' }}" data-week-start="1" data-autoclose="true" data-today-highlight="true">
                                                    <div class="input-group-prepend input-group-append">
                                                        <span class="input-group-text font-w600"><i class="fa fa-fw fa-arrow-right"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="special-to-input" name="special_to" placeholder="{{ __('back/products.do') }}" value="{{ (isset($product->special_from) && $product->special_from != '0000-00-00 00:00:00') ? \Carbon\Carbon::make($product->special_to)->format('d.m.Y') : '' }}" data-week-start="1" data-autoclose="true" data-today-highlight="true">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="price-input">{{ __('back/products.porez') }}</label>
                                                <select class="js-select2 form-control" id="tax-select" name="tax_id" style="width: 100%;" data-placeholder="{{ __('back/products.odaberite_porez') }}">
                                                    <option></option>
                                                    @foreach ($data['taxes'] as $tax)
                                                        <option value="{{ $tax->id }}" {{ ((isset($product)) and ($tax->id == $product->tax_id)) ? 'selected' : (( ! isset($product) and ($tax->id == 1)) ? 'selected' : '') }}>{{ $tax->title }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <!-- CKEditor 5 Classic (js-ckeditor5-classic in Helpers.ckeditor5()) -->
                                        <!-- For more info and examples you can check out http://ckeditor.com -->
                                        <div class="form-group row mb-4">
                                            <div class="col-md-12">
                                                <label for="description-editor">{{ __('back/products.opis') }}</label>
                                                <textarea id="description-editor" name="description">{!! isset($product) ? $product->description : old('description') !!}</textarea>
                                            </div>
                                        </div>



                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="atributi" role="tabpanel">
                        <div class="block">

                            <div class="block-content">
                                <div class="row justify-content-center push">
                                    <div class="col-md-12">


                                        <div class="form-group row items-push mb-4">



                                            <div class="col-md-6">
                                                <label for="categories">{{ __('back/products.odaberi_kategorije') }} @include('back.layouts.partials.required-star')</label>
                                                <select class="form-control" id="category-select" name="category[]" style="width: 100%;" multiple>
                                                    <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                                                    @foreach ($data['categories'] as $group => $cats)
                                                        @foreach ($cats as $id => $category)
                                                            <option value="{{ $id }}" class="font-weight-bold small" {{ ((isset($product)) and (in_array($id, $product->categories()->pluck('id')->toArray()))) ? 'selected' : '' }}>{{ $category['title'] }}</option>
                                                            @if ( ! empty($category['subs']))
                                                                @foreach ($category['subs'] as $sub_id => $subcategory)
                                                                    <option value="{{ $sub_id }}" class="pl-3 text-sm" {{ ((isset($product) && $product->subcategory()) and ($sub_id == $product->subcategory()->id)) ? 'selected' : '' }}>{{ $category['title'] . ' >> ' . $subcategory['title'] }}</option>
                                                                @endforeach
                                                            @endif
                                                        @endforeach
                                                    @endforeach
                                                </select>
                                                @error('category')
                                                <span class="text-danger font-italic">{{ __('back/products.kategorija_je_obavezna') }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dm-post-edit-slug">Brand</label>
                                                @livewire('back.layout.search.author-search', ['author_id' => isset($product) ? $product->author_id : 0])
                                            </div>

                                        </div>

                                        <div class="form-group row items-push mb-4">
                                            <div class="col-md-12">
                                                <label for="sastojci-editor">{{ __('back/products.sastojci') }}</label>
                                                <textarea id="sastojci-editor" name="sastojci">{!! isset($product) ? $product->sastojci : old('sastojci') !!}</textarea>
                                            </div>

                                        </div>

                                        <div class="form-group row items-push mb-4">
                                            <div class="col-md-12">
                                                <label for="podaci-editor">{{ __('back/products.podaci_o_prehrani') }}</label>
                                                <textarea id="podaci-editor" name="podaci">{!! isset($product) ? $product->podaci : old('podaci') !!}</textarea>
                                            </div>

                                        </div>
                                        <div class="form-group row items-push mb-4">
                                            <div class="col-md-3">
                                                <div class="custom-control custom-switch custom-control-success block-options-item ml-4">
                                                    <input type="checkbox" class="custom-control-input" id="product-switch" name="vegan"{{ (isset($product->vegan) and $product->vegan) ? 'checked' : '' }}>
                                                    <label class="custom-control-label pt-1" for="product-switch">Vegan</label>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="custom-control custom-switch custom-control-success block-options-item ml-4">
                                                    <input type="checkbox" class="custom-control-input" id="product-switch" name="vegetarian"{{ (isset($product->vegetarian) and $product->vegetarian) ? 'checked' : '' }}>
                                                    <label class="custom-control-label pt-1" for="product-switch">Vegeterian</label>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="custom-control custom-switch custom-control-success block-options-item ml-4">
                                                    <input type="checkbox" class="custom-control-input" id="product-switch" name="glutenfree"{{ (isset($product->glutenfree) and $product->glutenfree) ? 'checked' : '' }}>
                                                    <label class="custom-control-label pt-1" for="product-switch">Glutein Free</label>
                                                </div>
                                            </div>
                                        </div>



                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="slike" role="tabpanel">
                        <div class="block">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">{{ __('back/products.slike') }}</h3>
                            </div>
                            <div class="block-content block-content-full">
                                <div class="row justify-content-center">
                                    <div class="col-md-12">
                                        <!-- Dropzone (functionality is auto initialized by the plugin itself in js/plugins/dropzone/dropzone.min.js) -->
                                        <!-- For more info and examples you can check out http://www.dropzonejs.com/#usage -->
                                        <!--                            <div class="dropzone">
                                                                        <div class="dz-message" data-dz-message><span>Klikni ovdje ili dovuci slike za uplad</span></div>
                                                                    </div>-->
                                        @include('back.catalog.product.edit-photos')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="seo" role="tabpanel">
                        <div class="block">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">{{ __('back/products.meta_data_seo') }}</h3>
                            </div>
                            <div class="block-content">
                                <div class="row justify-content-center">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="meta-title-input">{{ __('back/products.meta_naslov') }}</label>
                                            <input type="text" class="js-maxlength form-control" id="meta-title-input" name="meta_title" value="{{ isset($product) ? $product->meta_title : old('meta_title') }}" maxlength="70" data-always-show="true" data-placement="top">
                                            <small class="form-text text-muted">
                                                {{ __('back/products.70_znakova_max') }}
                                            </small>
                                        </div>
                                        <div class="form-group">
                                            <label for="meta-description-input">{{ __('back/products.meta_opis') }}</label>
                                            <textarea class="js-maxlength form-control" id="meta-description-input" name="meta_description" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ isset($product) ? $product->meta_description : old('meta_description') }}</textarea>
                                            <small class="form-text text-muted">
                                                {{ __('back/products.160_znakova_max') }}
                                            </small>
                                        </div>
                                        <div class="form-group">
                                            <label for="slug-input"> {{ __('back/products.seo_url') }}</label>
                                            <input type="text" class="form-control" id="slug-input" value="{{ isset($product) ? $product->slug : old('slug') }}" disabled>
                                            <input type="hidden" name="slug" value="{{ isset($product) ? $product->slug : old('slug') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- END Block Tabs Default Style -->


            <div class="block">
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/products.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            @if (isset($product))
                                <a href="{{ route('products.destroy', ['product' => $product]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši" onclick="event.preventDefault(); document.getElementById('delete-product-form{{ $product->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/products.obrisi') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

            </div>




        </form>

        @if (isset($product))
            <form id="delete-product-form{{ $product->id }}" action="{{ route('products.destroy', ['product' => $product]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/ckeditor5-classic/build/ckeditor.js') }}"></script>
    <script src="{{ asset('js/plugins/dropzone/min/dropzone.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/plugins/jquery.maskedinput/jquery.maskedinput.min.js') }}"></script>
    <script src="{{ asset('js/plugins/slim/slim.kickstart.js') }}"></script>

    <!-- Page JS Helpers (CKEditor 5 plugins) -->
    <script>jQuery(function(){Dashmix.helpers(['datepicker']);});</script>

    <script>
        $(() => {
            ClassicEditor
                .create(document.querySelector('#description-editor'))
                .then(editor => {
                    console.log(editor);
                })
                .catch(error => {
                    console.error(error);
                });

            ClassicEditor
                .create(document.querySelector('#podaci-editor'))
                .then(editor => {
                    console.log(editor);
                })
                .catch(error => {
                    console.error(error);
                });

            ClassicEditor
                .create(document.querySelector('#sastojci-editor'))
                .then(editor => {
                    console.log(editor);
                })
                .catch(error => {
                    console.error(error);
                });

            $('#category-select').select2({});
            $('#grupa-select').select2({
                placeholder: '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });
            $('#tax-select').select2({});
            $('#action-select').select2({
                placeholder: '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });
            $('#author-select').select2({
                tags: true
            });
            $('#publisher-select').select2({
                tags: true
            });
            $('#letter-select').select2({
                tags: true
            });
            $('#binding-select').select2({
                tags: true
            });
            $('#shipping_time-select').select2({
                tags: true
            });
            $('#condition-select').select2({
                tags: true
            });

            Livewire.on('success_alert', () => {

            });

            Livewire.on('error_alert', (e) => {

            });
        })
    </script>

    <script>
        function SetSEOPreview() {
            let title = $('#name-input').val();
            $('#slug-input').val(slugify(title));
        }
    </script>

    @stack('product_scripts')

@endpush
