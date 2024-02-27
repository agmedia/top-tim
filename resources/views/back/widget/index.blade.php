@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/widget.widgets') }} ({{ $widgets->total() }})</h1>
                <a class="btn btn-hero-success my-2" href="{{ route('widget.create') }}">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/widget.new') }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="row no-gutters flex-md-10-auto">
        <div class="col-md-12 order-md-0 bg-body-dark">
            <!-- Main Content -->
            <div class="content content-full">
                @include('back.layouts.partials.session')

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">{{ __('back/widget.widgets') }}</h3>
                        <div class="block-options">
                            <!-- Search Form -->
                            <form action="{{ route('widgets') }}" method="GET">
                                <div class="block-options-item">
                                    <input type="text" class="form-control" id="search-input" name="search" placeholder="Search" value="{{ request()->query('search') }}">
                                </div>
                                <div class="block-options-item">
                                    <a href="{{ route('widgets') }}" class="btn btn-hero-sm btn-secondary"><i class="fa fa-search-minus"></i> {{ __('back/layout.btn.clear') }}</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="block-content">
                        <table class="table table-striped table-borderless table-vcenter">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">#</th>
                                <th style="width: 30%;">{{ __('back/layout.table.title') }}</th>
                                <th style="width: 25%;">{{ __('back/layout.table.id') }}</th>
                                <th class="text-center">{{ __('back/layout.table.created') }}</th>
                                <th class="text-center">{{ __('back/layout.table.status') }}</th>
                                <th style="width: 120px;" class="text-center">{{ __('back/layout.table.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($widgets as $widget)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}.</td>
                                    <td>
                                        <a href="{{ route('widget.edit', ['widget' => $widget]) }}">{{ $widget->title }}</a>
                                    </td>
                                    <td class="font-size-sm">++{{ $widget->slug }}++</td>
                                    <td class="text-center">
                                        {{ \Illuminate\Support\Carbon::make($widget->created_at)->format('d.m.Y') }}
                                    </td>
                                    <td class="text-center">@include('back.layouts.partials.status', ['status' => $widget->status])</td>
                                    <td class="text-right font-size-sm">
                                        <a class="btn btn-sm btn-alt-secondary" href="{{ route('widget.edit', ['widget' => $widget]) }}">
                                            <i class="fa fa-fw fa-pencil-alt"></i>
                                        </a>
                                        <button class="btn btn-sm btn-alt-danger"
                                                onclick="event.preventDefault(); deleteItem({{ $widget->id }}, '{{ route('widget.destroy') }}');">
                                            <i class="fa fa-fw fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="text-center">
                                    <td colspan="6">{{ __('back/widget.no_widgets') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                        {{ $widgets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js_after')

@endpush
