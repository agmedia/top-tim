@if(isset($simple) && $simple)
    @if($status)
        <i class="fa fa-fw fa-check text-success"></i>
    @else
        <i class="fa fa-fw fa-times text-danger"></i>
    @endif
@else
    @if($status)
        <span class="badge badge-success">{{ __('back/layout.active') }}</span>
    @else
        <span class="badge badge-secondary">{{ __('back/layout.inactive') }}</span>
    @endif
@endif
