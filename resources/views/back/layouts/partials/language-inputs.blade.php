@if ($type == 'input')
    <div class="form-group">
        <label for="{{ $input }}" class="w-100">{{ isset($title) ? $title : __('back/app.payments.input_title') }}
            <ul class="nav nav-pills float-right">
                @foreach(ag_lang() as $lang)
                    <li @if ($lang->code == current_locale()) class="active" @endif ">
                    <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#{{ $tab }}-{{ $lang->code }}">
                        <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                    </a>
                    </li>
                @endforeach
            </ul>

        </label>
        <div class="tab-content">
            @foreach(ag_lang() as $lang)
                <div id="{{ $tab }}-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                    <input type="text" class="form-control" id="{{ $id }}-{{ $lang->code }}" name="{{ $input }}[{{ $lang->code }}]" placeholder="{{ $lang->code }}"  >
                </div>
            @endforeach
        </div>
    </div>
{{--
--}}
@elseif ($type == 'textarea')
    <div class="form-group mb-4">
        <label for="{{ $input }}" class="w-100">{!! isset($title) ? $title : __('back/app.payments.short_desc') !!}
            <div class="float-right">
                <ul class="nav nav-pills float-right">
                    @foreach(ag_lang() as $lang)
                        <li @if ($lang->code == current_locale()) class="active" @endif ">
                        <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#{{ $tab }}-{{ $lang->code }}">
                            <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                        </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </label>
        <div class="tab-content">
            @foreach(ag_lang() as $lang)
                <div id="{{ $tab }}-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                    <textarea id="{{ $id }}-{{ $lang->code }}" class=" form-control"  name="data['{{ $input }}'][{{ $lang->code }}]" placeholder="{{ $lang->code }}" ></textarea>
                </div>
            @endforeach
        </div>
        <small class="form-text text-muted">
            160 {{ __('back/app.payments.chars') }} max
        </small>
    </div>
{{--
--}}
@elseif ($type == 'rte')
@endif