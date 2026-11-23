@extends('back.layouts.backend')
@push('css_before')

    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') }}">


@endpush

@section('content')
    <div data-bulk-delete data-bulk-url="{{ route('actions.bulk.destroy') }}" data-bulk-kind="akcije">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/action.action_title') }}</h1>
                <div class="d-flex align-items-center mr-sm-3">
                    <span class="mr-2" role="status" aria-live="polite">Označeno: <span data-bulk-count>0</span></span>
                    <button type="button" class="btn btn-alt-danger my-2" data-bulk-submit disabled>
                        <i class="fa fa-fw fa-trash-alt"></i><span class="ml-1" data-bulk-label>Obriši označene</span>
                    </button>
                </div>
                <a class="btn btn-hero-success my-2" href="{{ route('actions.create') }}">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/action.action_new') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content">
    @include('back.layouts.partials.session')
        <div class="alert alert-danger" data-bulk-error role="alert" hidden></div>


        <!-- All Products -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('back/action.action_all') }} ({{ $actions->total() }})</h3>

                <div class="block-options">
                    <!-- Search Form -->
                    <form action="{{ route('actions') }}" method="GET">
                        <div class="block-options-item">
                            <input type="text" class="form-control" id="search-input" name="search" placeholder="{{ __('back/user.group_search') }}" value="{{ request()->query('search') }}">
                        </div>
                        <div class="block-options-item">
                            <a href="{{ route('actions') }}" class="btn btn-hero-sm btn-secondary"><i class="fa fa-search-minus"></i> {{ __('back/user.clear') }}</a>
                        </div>
                    </form>
                </div>

            </div>


            <div class="block-content">
                <!-- All Products Table -->
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter">
                        <thead>
                        <tr>
                            <th class="text-center" style="width: 30px;">
                                <input type="checkbox" id="actions-select-all" data-bulk-all aria-label="Označi sve akcije na ovoj stranici">
                            </th>
                            <th class="text-left">{{ __('back/action.title') }}</th>
                            <th>{{ __('back/action.from') }}</th>
                            <th>{{ __('back/action.to') }}</th>
                            <th>{{ __('back/action.action') }}</th>
                            <th>{{ __('back/action.grupa') }}</th>
                            <th class="text-center font-size-sm">{{ __('back/action.kupon_kod') }}</th>
                            <th class="text-center font-size-sm">{{ __('back/action.status') }}</th>
                            <th class="text-right" style="width: 10%;">{{ __('back/action.edit') }}</th>
                        </tr>
                        </thead>
                        <tbody>



                        @forelse ($actions as $action)


                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" value="{{ $action->id }}" data-bulk-row aria-label="Označi akciju {{ $action->id }}">
                                </td>
                                <td class="font-size-sm">
                                    <a class="font-w600" href="{{ route('actions.edit', ['action' => $action]) }}">{{ $action->translation->title }}</a>
                                </td>
                                <td class="font-size-sm">{{ $action->date_start ? \Illuminate\Support\Carbon::make($action->date_start)->format('d.m.Y') : '' }}</td>
                                <td class="font-size-sm">{{ $action->date_end ? \Illuminate\Support\Carbon::make($action->date_end)->format('d.m.Y') : '' }}</td>

                                <td class="font-size-sm">{{ $action->discount }}</td>
                                <td class=font-size-sm">
                                    @if($action->userGroup)
                                    {{ $action->userGroup->translation->title ?: ''}}
                                    @endif

                                </td>
                                <td class="text-center font-size-sm">
                                    @include('back.layouts.partials.status', ['status' => $action->coupon, 'simple' => true])
                                    {{ $action->coupon ?: '' }}
                                </td>


                                <td class="text-center font-size-sm">
                                    @include('back.layouts.partials.status', ['status' => $action->status, 'simple' => true])
                                </td>
                                <td class="text-right font-size-sm">
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('actions.edit', ['action' => $action]) }}">
                                        <i class="fa fa-fw fa-pencil-alt"></i>
                                    </a>
                                    <button class="btn btn-sm btn-alt-danger" onclick="event.preventDefault(); deleteItem({{ $action->id }}, '{{ route('actions.destroy.api') }}');"><i class="fa fa-fw fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="font-size-sm text-center" colspan="9">
                                    <label for="">{{ __('back/action.no_action') }}</label>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $actions->withQueryString()->links() }}
            </div>
        </div>
        <!-- END All Products -->
    </div>
    </div>
    <!-- END Page Content -->

@endsection

@push('js_after')


    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>

    <!-- Page JS Helpers (CKEditor 5 plugins) -->
    <script>jQuery(function(){Dashmix.helpers(['select2','datepicker']);});</script>
    <script>
        $(() => {
            $('#category-select').select2({
                placeholder: 'Odaberite kategoriju'
            });
            $('#author-select').select2({
                placeholder: 'Odaberite autora'
            });
            $('#publisher-select').select2({
                placeholder: 'Odaberite izdavača'
            });
        })
    </script>
    @include('back.layouts.partials.bulk-delete')

@endpush
