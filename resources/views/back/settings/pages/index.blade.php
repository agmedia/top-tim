@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/info.info_stranice') }}</h1>
                <a class="btn btn-hero-success my-2" href="{{ route('pages.create') }}">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/info.nova_post') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content content-full">
    @include('back.layouts.partials.session')


        <!-- Posts -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('back/info.objave') }}</h3>
                <div class="block-options">
                    <!-- Search Form -->
                    <form action="{{ route('pages') }}" method="GET">
                        <div class="block-options-item">
                            <input type="text" class="form-control" id="search-input" name="search" placeholder="{{ __('back/info.pretrazi_stranice') }}" value="{{ request()->query('search') }}">
                        </div>
                        <div class="block-options-item">
                            <a href="{{ route('pages') }}" class="btn btn-hero-sm btn-secondary"><i class="fa fa-search-minus"></i> {{ __('back/info.ocisti') }}</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="block-content">
                <table class="table table-striped table-borderless table-vcenter">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                      <!--  <th style="width: 27px;" class="text-center">Slika</th>-->
                        <th>{{ __('back/info.naziv') }}</th>

                        <th class="text-center">{{ __('back/info.status') }}</th>
                       <!-- <th class="text-center">Featured</th> -->
                        <th style="width: 100px;" class="text-center">{{ __('back/info.uredi') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}.</td>
                          <!--  <td class="text-center">
                                <a href="{{ route('pages.edit', ['page' => $page]) }}">
                                    <img src="{{ asset($page->image) }}" height="45px"/>
                                </a>
                            </td>-->
                            <td>
                                <i class="fa fa-eye text-success mr-1"></i>
                                <a class="font-size-sm" href="{{ route('pages.edit', ['page' => $page]) }}">{{ $page->translation->title }}</a>
                            </td>
                            <td class="text-center">
                                @if ($page->status)
                                    <i class="fa fa-check-circle text-success"></i>
                                @else
                                    <i class="fa fa-times-circle text-danger"></i>
                                @endif
                            </td>
                          <!--  <td class="text-center">
                                @if ($page->featured)
                                    <i class="fa fa-check-circle text-success"></i>
                                @else
                                    <i class="fa fa-times-circle text-danger"></i>
                                @endif
                            </td>-->
                            <td class="text-right font-size-sm">
                                <a class="btn btn-sm btn-alt-secondary" href="{{ route('pages.edit', ['page' => $page]) }}">
                                    <i class="fa fa-fw fa-pencil-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="5">{{ __('back/info.nema_objava') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $pages->links() }}
            </div>
        </div>
        <!-- END Posts -->
    </div>
    <!-- END Page Content -->

@endsection

@push('js_after')

@endpush
