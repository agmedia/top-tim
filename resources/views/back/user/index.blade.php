@extends('back.layouts.backend')
@push('css_before')

    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">


@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/user.users') }}</h1>
                <a href="{{ route('users.export', $filters) }}" class="btn btn-hero-sm btn-success">
                    <i class="fa fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
    </div>


    <!-- Page Content -->
    <div class="content">
    @include('back.layouts.partials.session')


        <!-- All Orders -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('back/user.all_users') }} ({{ $users->total() }})</h3>
            </div>
            <div class="block-content">
                    <form class="row mb-2" action="{{ route('users') }}" method="GET">
                        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                        <input type="hidden" name="direction" value="{{ $filters['direction'] }}">
                        <div class="col-12 col-md-5 mb-2">
                            <input type="text" class="form-control" id="search-input" name="search" placeholder="{{ __('back/user.search') }}" value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <select class="form-control" name="role" aria-label="{{ __('back/user.filter_role') }}">
                                <option value="">{{ __('back/user.all_roles') }}</option>
                                <option value="administrators" {{ $filters['role'] === 'administrators' ? 'selected' : '' }}>{{ __('back/user.administrators') }}</option>
                                @foreach($roleLabels as $role => $label)
                                    <option value="{{ $role }}" {{ $filters['role'] === $role ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                                <option value="none" {{ $filters['role'] === 'none' ? 'selected' : '' }}>{{ __('back/user.no_role') }}</option>
                            </select>
                        </div>
                        <div class="col-auto mb-2">
                            <button class="btn btn-hero-sm btn-primary" type="submit">{{ __('back/user.filter') }}</button>
                        </div>
                        <div class="col-auto mb-2">
                            <a href="{{ route('users') }}" class="btn btn-hero-sm btn-secondary"><i class="fa fa-search-minus"></i> {{ __('back/user.clear') }}</a>
                        </div>
                    </form>
                <p>
                    <a class="btn btn-sm {{ $filters['role'] === 'administrators' ? 'btn-primary' : 'btn-alt-primary' }}" href="{{ route('users', ['role' => 'administrators', 'sort' => 'role', 'direction' => 'asc']) }}">
                        <i class="fa fa-user-shield mr-1"></i>{{ __('back/user.show_administrators') }}
                    </a>
                </p>
                <!-- All Orders Table -->
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter font-size-sm">
                        <thead>
                        <tr>
                            <th><a href="{{ route('users', array_merge($filters, ['sort' => 'name', 'direction' => $filters['sort'] === 'name' && $filters['direction'] === 'asc' ? 'desc' : 'asc'])) }}">{{ __('back/user.title_user') }}</a></th>
                            <th>{{ __('back/action.grupa') }}</th>
                            <th><a href="{{ route('users', array_merge($filters, ['sort' => 'email', 'direction' => $filters['sort'] === 'email' && $filters['direction'] === 'asc' ? 'desc' : 'asc'])) }}">Email</a></th>
                            <th class="text-center">Status</th>
                            <th class="text-center" aria-sort="{{ $filters['sort'] === 'role' ? ($filters['direction'] === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <a href="{{ route('users', array_merge($filters, ['sort' => 'role', 'direction' => $filters['sort'] === 'role' && $filters['direction'] === 'asc' ? 'desc' : 'asc'])) }}">
                                    {{ __('back/user.role') }} @if($filters['sort'] === 'role') {{ $filters['direction'] === 'asc' ? '↑' : '↓' }} @endif
                                </a>
                            </th>
                            <th class="text-right">{{ __('back/user.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>


                        @foreach ($users as $user)
                            @php($canManage = ! $user->roles->contains('name', 'superadmin') || auth()->user()->roles->contains('name', 'superadmin'))

                            <tr>
                                <td>
                                    @if($canManage)
                                        <a class="font-w600" href="{{ route('users.edit', ['user' => $user]) }}">{{ $user->name }}</a>
                                    @else
                                        <span class="font-w600">{{ $user->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $user->details?->group?->translation?->title }}

                                </td>
                                <td>{{ $user->email }}</td>
                                <td class="text-center font-size-sm">
                                    @if((int) ($user->details?->status ?? 0) === 1)
                                        <span class="text-success"><i class="fa fa-fw fa-check"></i> {{ __('back/user.active') }}</span>
                                    @else
                                        <span class="text-danger"><i class="fa fa-fw fa-times"></i> {{ __('back/user.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-center font-size-sm">
                                    @forelse($user->roles as $role)
                                        <span class="badge {{ in_array($role->name, ['admin', 'superadmin']) ? 'badge-primary' : 'badge-secondary' }}">{{ $roleLabels[$role->name] ?? $role->name }}</span>
                                    @empty
                                        <span class="text-muted">{{ __('back/user.no_role') }}</span>
                                    @endforelse
                                </td>
                                <td class="text-right font-size-base">
                                    @if($canManage)
                                        <a class="btn btn-sm btn-alt-secondary" href="{{ route('users.edit', ['user' => $user]) }}">
                                            <i class="fa fa-fw fa-pencil-alt"></i>
                                        </a>
                                    @endif
                                    @if(auth()->id() !== $user->id && $canManage)
                                        <form class="d-inline" action="{{ route('users.destroy', ['user' => $user]) }}" method="POST" data-confirm="{{ __('back/user.delete_confirm', ['name' => $user->name]) }}" onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            @method('DELETE')
                                            @foreach($filters as $key => $value)
                                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                            @endforeach
                                            <button class="btn btn-sm btn-alt-danger" type="submit" title="{{ __('back/user.delete') }}" aria-label="{{ __('back/user.delete') }}: {{ $user->name }}"><i class="fa fa-fw fa-trash-alt"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $users->links() }}

            </div>
        </div>
        <!-- END All Orders -->
    </div>

@endsection

@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(() => {
            $('#status-select').select2({
                placeholder: 'Promjenite status'
            });

        })
    </script>
    <script>
        $("#checkAll").click(function () {
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
    </script>

@endpush
