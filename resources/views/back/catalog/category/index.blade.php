@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/categories.kategorije') }} </h1>
                <a class="btn btn-hero-success my-2" href="{{ route('category.create') }}">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/categories.nova_kategorija') }} </span>
                </a>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="row no-gutters flex-md-10-auto">
        <div class="col-md-12 order-md-0 bg-body-dark">
            <!-- Main Content -->
            <div class="content content-full">
            @include('back.layouts.partials.session')
                <div id="accordion" role="tablist" aria-multiselectable="true">
                    @forelse($categoriess->sortBy('sort_order') as $group => $categories)
                        <h3 class="{{ ! $loop->first ? 'mt-5' : '' }}"><small class="font-weight-light">{{ __('back/categories.grupa_kategorija') }}: </small><small>{{ $group }}</small> <small class="font-weight-light">{{ $categories->count() }}</small></h3>

                        @forelse($categories as $category)
                            <div class="block block-rounded mb-1">
                                <div class="block-header block-header-default" role="tab" id="accordion_h{{ $category->id }}">
                                    <a class="h3 block-title" data-toggle="collapse" data-parent="#accordion" href="#accordion_q{{ $category->id }}" aria-expanded="@if($loop->first) true @else false @endif" aria-controls="accordion_q{{ $category->id }}">{{ $category->translation->title }}</a>
                                    <div class="block-options">
                                        <div class="btn-group">
                                            <a  class="btn btn-sm btn-secondary js-tooltip-enabled me-2" data-toggle="tooltip" title="" data-original-title="{{ __('back/categories.uredi') }}"> {{ $category->products_count }}
                                            </a>
                                            <a href="{{ route('category.edit', ['category' => $category]) }}" class="btn btn-sm btn-secondary js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="{{ __('back/categories.uredi') }}">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @if ($category->subcategories)
                                    <div id="accordion_q{{ $category->id }}" class="collapse @if($loop->first) false @endif" role="tabpanel" aria-labelledby="accordion_h{{ $category->id }}" data-parent="#accordion">
                                        <div class="block-content pb-4">
                                            @foreach($category->subcategories()->get() as $subcategory)
                                                <a href="{{ route('category.edit', ['category' => $subcategory]) }}" class="btn btn-sm mb-3  @if ($subcategory->products()->count() == 0) btn-warning @else btn-success @endif js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="{{ __('back/categories.uredi') }}">
                                                    {{ $subcategory->translation->title }} - {{ $subcategory->products()->count() }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <h3>{{ __('back/categories.kategorije_prazne_1') }} <a href="{{ route('category.create') }}">{{ __('back/categories.kategorije_prazne_2') }} </a></h3>
                        @endforelse
                    @empty
                        <h3>{{ __('back/categories.grupa_prazne_1') }} <a href="{{ route('category.create') }}">{{ __('back/categories.grupa_prazne_2') }}</a> {{ __('back/categories.grupa_prazne_3') }}</h3>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('js_after')
    <script src="{{ asset('js/pages/be_pages_projects_tasks.min.js') }}"></script>
@endpush
