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
                <input type="submit" class="btn btn-outline-success my-2 mr-3" form="product-form" value="{{ __('back/products.snimi') }} {{ __('Artikl') }}" />
                @if (isset($product))
                    <a class="flex btn btn-outline-primary" target="_blank" href="{{ url($product->translation->url) }}">
                        <i class="fa fa-fw fa-eye"></i>
                    </a>
                @endif

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
        <form id="product-form" action="{{ isset($product) ? route('products.update', ['product' => $product]) : route('products.store') }}" method="POST" enctype="multipart/form-data">
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
                        <a class="nav-link" href="#opcije"><i class="si si-settings"></i> {{ __('back/products.opcije') }}</a>
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
                                                                <img src="{{ asset('media/flags/' . $lang->code . '.png') }}"/>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <div class="tab-content">
                                                    @foreach(ag_lang() as $lang)
                                                        <div id="name-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                            <input type="text" class="form-control" id="name-input-{{ $lang->code }}" name="name[{{ $lang->code }}]" placeholder="{{ $lang->code }}"
                                                                   value="{{ (isset($product) && isset($product->translation($lang->code)->name)) ? $product->translation($lang->code)->name : old('name.*') }}" onkeyup="SetSEOPreview()">
                                                            @error('name')
                                                            <span class="text-danger font-italic">{{ __('back/products.naziv_je_potreban') }}</span>
                                                            @enderror
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="polica-input">EAN </label>
                                                <input type="text" class="form-control" id="polica-input" name="isbn" placeholder="{{ __('back/products.upisite_ean') }}" value="{{ isset($product) ? $product->isbn : old('isbn') }}">
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
                                                <input type="text" class="form-control" id="quantity-input" name="quantity" placeholder="{{ __('back/products.upisite_kolicinu') }}"
                                                       value="{{ isset($product) ? $product->quantity : ( ! isset($product) ? 1 : old('quantity')) }}">
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
                                                <input type="text" class="form-control" id="polica-input" name="polica" placeholder="{{ __('back/products.upisite_sifru_police') }}" value="{{ isset($product) ? $product->polica : old('polica') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row items-push mb-3">
                                            <div class="col-md-3">
                                                <label for="special-input">{{ __('back/products.akcija') }}</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="special-input" name="special" placeholder="00.00" value="{{ isset($product) ? $product->special : old('special') }}" readonly>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">€</span>

                                                        @php
                                                            use App\Models\Back\Marketing\Action;

                                                            // Naziv za title param
                                                            $prodName = optional($product->translation(current_locale()))->name
                                                                ?? optional($product->translation('hr'))->name
                                                                ?? $product->sku;
                                                            $titleParam = rawurlencode('Akcija za: ' . $prodName);

                                                            // Ako koristiš products.action_id, prvo probaj njega:
                                                            $actionIdFromProduct = (int) ($product->action_id ?? 0);
                                                            $activeAction = $actionIdFromProduct > 0
                                                                ? Action::find($actionIdFromProduct)
                                                                : Action::findActiveForProduct($product); // helper iz ranijeg primjera

                                                            $isUserGroupLimited = $activeAction && (int) ($activeAction->user_group_id ?? 0) > 0;
                                                        @endphp

                                                        @if ($activeAction)
                                                            {{-- Uredi postojeću akciju --}}
                                                            <a href="{{ route('actions.edit', ['action' => $activeAction->id]) }}"
                                                               target="_blank"
                                                               class="btn  btn-outline-warning js-tooltip-enabled"
                                                               data-toggle="tooltip"
                                                               title="{{ __('back/action.edit_action_for_product') }}">
                                                                <i class="fa fa-edit"></i>
                                                            </a>

                                                            @if ($isUserGroupLimited)
                                                                {{-- Ako je akcija ograničena na grupu korisnika, ponudi i kreiranje nove (globalne ili druge) --}}
                                                                <a href="{{ route('actions.create') }}?group=product&action_list[]={{ $product->id }}&title={{ $titleParam }}"
                                                                   target="_blank"
                                                                   class="btn  btn-outline-success js-tooltip-enabled ml-1"
                                                                   data-toggle="tooltip"
                                                                   title="{{ __('back/action.new_action_for_product') }}">
                                                                    <i class="fa fa-bolt"></i>
                                                                </a>
                                                            @endif
                                                        @else
                                                            {{-- Nema aktivne akcije -> Kreiraj novu --}}
                                                            <a href="{{ route('actions.create') }}?group=product&action_list[]={{ $product->id }}&title={{ $titleParam }}"
                                                               target="_blank"
                                                               class="btn  btn-outline-success js-tooltip-enabled"
                                                               data-toggle="tooltip"
                                                               title="{{ __('back/action.new_action_for_product') }}">
                                                                <i class="fa fa-bolt"></i>
                                                            </a>
                                                        @endif
                                                    </div>


                                                </div>
                                            </div>


                                            <div class="col-md-6">
                                                <label for="special-from-input">{{ __('back/products.akcija_vrijedi') }}</label>
                                                <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-week-start="1" data-autoclose="true" data-today-highlight="true">
                                                    <input type="text" class="form-control" id="special-from-input" name="special_from" placeholder="{{ __('back/products.od') }}"
                                                           value="{{ (isset($product->special_from) && $product->special_from != '0000-00-00 00:00:00') ? \Carbon\Carbon::make($product->special_from)->format('d.m.Y') : '' }}" data-week-start="1" data-autoclose="true"
                                                           data-today-highlight="true">
                                                    <div class="input-group-prepend input-group-append">
                                                        <span class="input-group-text font-w600"><i class="fa fa-fw fa-arrow-right"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="special-to-input" name="special_to" placeholder="{{ __('back/products.do') }}"
                                                           value="{{ (isset($product->special_from) && $product->special_from != '0000-00-00 00:00:00') ? \Carbon\Carbon::make($product->special_to)->format('d.m.Y') : '' }}" data-week-start="1" data-autoclose="true"
                                                           data-today-highlight="true">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="price-input">{{ __('back/products.porez') }}</label>
                                                <select class="js-select2 form-control" id="tax-select" name="tax_id" style="width: 100%;" data-placeholder="{{ __('back/products.odaberite_porez') }}">
                                                    <option></option>
                                                    @foreach ($data['taxes'] as $tax)
                                                        <option value="{{ $tax->id }}" {{ ((isset($product)) and ($tax->id == $product->tax_id)) ? 'selected' : (( ! isset($product) and ($tax->id == 1)) ? 'selected' : '') }}>{{ isset($tax->title->{current_locale()}) ? $tax->title->{current_locale()} : $tax->title }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <!-- CKEditor 5 Classic (js-ckeditor5-classic in Helpers.ckeditor5()) -->
                                        <!-- For more info and examples you can check out http://ckeditor.com -->
                                        <div class="form-group row mb-4">
                                            <div class="col-md-12">
                                                <label for="description-editor">{{ __('back/products.opis') }}</label>
                                                <ul class="nav nav-pills float-right">
                                                    @foreach(ag_lang() as $lang)
                                                        <li @if ($lang->code == current_locale()) class="active" @endif>
                                                            <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#description-{{ $lang->code }}">
                                                                <img src="{{ asset('media/flags/' . $lang->code . '.png') }}"/>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                                <div class="tab-content">
                                                    @foreach(ag_lang() as $lang)
                                                        <div id="description-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                            <textarea id="description-editor-{{ $lang->code }}" name="description[{{ $lang->code }}]"
                                                                      placeholder="{{ $lang->code }}">{!! (isset($product) && isset($product->translation($lang->code)->description)) ? $product->translation($lang->code)->description : old('description.*') !!}</textarea>
                                                        </div>
                                                    @endforeach
                                                </div>
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
                                                                    <option value="{{ $sub_id }}"
                                                                            class="pl-3 text-sm" {{ ((isset($product) && $product->subcategory()) and in_array($sub_id, $product->subcategories()->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $category['title'] . ' >> ' . $subcategory['title'] }}</option>
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
                                                @livewire('back.layout.search.author-search', ['brand_id' => isset($product) ? $product->brand_id : 0])
                                            </div>

                                            <div class="col-md-6">
                                                <label for="dm-post-edit-slug">Tablica veličina</label>
                                                @livewire('back.layout.search.sizeguide-search', ['sizeguide_id' => isset($product) ? $product->sizeguide_id : 0])
                                            </div>
                                        </div>

                                        {{--@if ( ! empty($data['attributes']))
                                            <div class="form-group row items-push mb-4">
                                                @foreach ($data['attributes'] as $attribute)
                                                    <div class="col-md-4">
                                                        <label for="spol">{{ $attribute['group'] }}</label>
                                                        <select class="js-select2 form-control" id="spol" name="attributes[{{ \Illuminate\Support\Str::slug($attribute['group']) }}]" style="width: 100%;" data-placeholder="Odaberite opciju">
                                                            <option></option>
                                                            @foreach ($attribute['items'] as $item)
                                                                <option value="{{ $item['id'] }}" {{ ((isset($product)) and (in_array($item['id'], $product->attributes()->pluck('id')->toArray()))) ? 'selected' : '' }}>{{ $item['title'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif--}}

                                        @if ( ! empty($data['attributes']))
                                            <div class="form-group row items-push mb-4">
                                                @foreach ($data['attributes'] as $attribute)
                                                    <div class="col-md-4">
                                                        @php
                                                            $attribute_id = 0;
                                                            foreach ($attribute['items'] as $item) {
                                                                if (isset($product) && in_array($item['id'], $product->attributes()->pluck('id')->toArray())) {
                                                                    $attribute_id = $item['id'];
                                                                }
                                                            }
                                                        @endphp
                                                        <label for="dm-post-edit-slug">{{ $attribute['group'] }}</label>
                                                        @livewire('back.catalog.attribute-search-new-input', ['group' => $attribute['group'], 'attribute_id' => $attribute_id])
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="opcije" role="tabpanel">
                        <div class="block">
                            <div class="block-content">
                                <div class="row justify-content-center push">
                                    <div class="col-md-12">
                                        @error('sku_opt')
                                        <span class="text-danger small font-italic">{{ $message }}</span>
                                        @enderror
                                        <div id="addition">
                                            @livewire('back.catalog.product-options-selection', ['product' => isset($product) ? $product : null])
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
                                        @include('back.catalog.product.edit-photos', ['resource' => isset($product) ? $product : null, 'existing' => $data['images'], 'delete_url' => route('products.destroy.image')])
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
                                            <ul class="nav nav-pills float-right">
                                                @foreach(ag_lang() as $lang)
                                                    <li @if ($lang->code == current_locale()) class="active" @endif>
                                                        <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#meta_title-{{ $lang->code }}">
                                                            <img src="{{ asset('media/flags/' . $lang->code . '.png') }}"/>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="tab-content">
                                                @foreach(ag_lang() as $lang)
                                                    <div id="meta_title-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                        <input type="text" class="js-maxlength form-control" id="meta-title-input-{{ $lang->code }}" name="meta_title[{{ $lang->code }}]" placeholder="{{ $lang->code }}"
                                                               value="{{ (isset($product) && isset($product->translation($lang->code)->meta_title)) ? $product->translation($lang->code)->meta_title : old('meta_title.*') }}" maxlength="70" data-always-show="true"
                                                               data-placement="top">
                                                        <small class="form-text text-muted">
                                                            {{ __('back/products.70_znakova_max') }}
                                                        </small>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="meta-description-input">{{ __('back/products.meta_opis') }}</label>
                                            <ul class="nav nav-pills float-right">
                                                @foreach(ag_lang() as $lang)
                                                    <li @if ($lang->code == current_locale()) class="active" @endif>
                                                        <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#meta-description-{{ $lang->code }}">
                                                            <img src="{{ asset('media/flags/' . $lang->code . '.png') }}"/>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="tab-content">
                                                @foreach(ag_lang() as $lang)
                                                    <div id="meta-description-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                        <textarea class="js-maxlength form-control" id="meta-description-input-{{ $lang->code }}" name="meta_description[{{ $lang->code }}]" placeholder="{{ $lang->code }}" rows="4" maxlength="160" data-always-show="true"
                                                                  data-placement="top">{{ (isset($product) && isset($product->translation($lang->code)->meta_description)) ? $product->translation($lang->code)->meta_description : old('meta_description.*') }}</textarea>
                                                        <small class="form-text text-muted">
                                                            {{ __('back/products.160_znakova_max') }}
                                                        </small>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="slug-input"> {{ __('back/products.seo_url') }}</label>
                                            <ul class="nav nav-pills float-right">
                                                @foreach(ag_lang() as $lang)
                                                    <li @if ($lang->code == current_locale()) class="active" @endif>
                                                        <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#slug-input-{{ $lang->code }}">
                                                            <img src="{{ asset('media/flags/' . $lang->code . '.png') }}"/>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="tab-content">
                                                @foreach(ag_lang() as $lang)
                                                    <div id="slug-input-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                        <input type="text" class="form-control" id="slug-input-{{ $lang->code }}" placeholder="{{ $lang->code }}"
                                                               value="{{(isset($product) && isset($product->translation($lang->code)->slug)) ? $product->translation($lang->code)->slug : old('slug.*') }}" disabled>
                                                        <input type="hidden" name="slug[{{ $lang->code }}]" value="{{ (isset($product) && isset($product->translation($lang->code)->slug)) ? $product->translation($lang->code)->slug : old('slug.*') }}">
                                                    </div>
                                                @endforeach
                                            </div>
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
                                {{--<a href="{{ route('products.destroy', ['product' => $product]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši" onclick="event.preventDefault(); document.getElementById('delete-product-form{{ $product->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/products.obrisi') }}
                                </a>--}}
                                <button class="btn btn-hero-danger my-2 js-tooltip-enabled"
                                        data-toggle="tooltip"
                                        title=""
                                        data-original-title="Obriši"
                                        onclick="event.preventDefault(); deleteSingleProduct({{ $product->id }}, '{{ route('products.destroy.api') }}');">
                                    <i class="fa fa-fw fa-trash-alt"></i> {{ __('back/products.obrisi') }}
                                </button>
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



    <!-- END Vertically Centered Block Modal -->


    <!-- Modal -->
    <div class="modal fade" id="addoption" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Dodaj novu opciju</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">


                    <label for="opcije">Odaberi opciju</label>
                    <select class="js-select2 form-control" id="opcije" name="opcije" style="width: 100%;" data-placeholder="Odaberite opciju">
                        <option></option>
                        <option value="1">Boja</option>
                        <option value="1">Veličina</option>

                    </select>


                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn btn-hero-success"><i class="fas fa-save mr-1"></i> Snimi</button>
                </div>
            </div>
        </div>
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
    <script>jQuery(function () {
            Dashmix.helpers(['datepicker']);
        });</script>
    <script>
        $(() => {
            {!! ag_lang() !!}.forEach(function (item) {
                ClassicEditor
                .create(document.querySelector('#description-editor-' + item.code))
                .then(editor => {
                    console.log(editor);
                })
                .catch(error => {
                    console.error(error);
                });
            });

            $('#category-select').select2({});
            $('#grupa-select').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });
            $('#tax-select').select2({});
            $('#action-select').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });

            $('#spol').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });

            $('#kroj').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });

            $('#tip-rukava').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });

            $('#tekstil').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });

            $('#color').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });

            $('#color1').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
                minimumResultsForSearch: Infinity
            });

            $('#color2').select2({
                placeholder:             '{{ __('back/products.odaberite') }}',
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

        //
        function deleteSingleProduct(id, url) {
            Swal.fire({
                title:              'Obriši..!',
                text:               "Jeste li sigurni da želite obrisati artikl?",
                icon:               'warning',
                showCancelButton:   true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor:  '#d33',
                confirmButtonText:  'Da, obriši!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post(url, {id: id})
                    .then(response => {
                        if (response.data.success) {
                            successToast.fire({
                                timer: 2160
                            });

                            setTimeout(() => {
                                window.history.back();
                            }, 2000);
                        } else {
                            return errorToast.fire(response.data.message);
                        }
                    });


                }
            });
        }
    </script>
    @stack('product_scripts')
@endpush
