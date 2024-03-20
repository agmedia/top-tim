
<div class="language">
    <div class="dropdown hover-dropdown">
        <button class="dropdown-toggle btn btn-sm btn-default px-1 text-white" type="button" data-bs-toggle="dropdown"><img class="lang" style="width:16px" src="{{ asset('media/flags/' . session('locale') . '.png') }}" alt=""> {{ \Illuminate\Support\Str::upper(current_locale()) }} </button>
        <ul class="dropdown-menu">
            @if (isset($langs))
                @foreach ($langs as $lang)
                    <li>
                        <a class="dropdown-item @if (current_locale() == $lang['code']) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang['code'], $lang['slug'], [], true) }}">{{ $lang['title'] }}</a>
                    </li>
                @endforeach
            @else
                @foreach (ag_lang() as $lang)
                    <li>
                        @if (isset($page))
                            <a class=" @if (current_locale() == $lang->code) active @endif"
                               href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route.page', ['page' => $page->translation($lang->code)->slug]), [], true) }}">
                                <img class="lang" style="width:16px" src="{{ asset('images/'.Str::upper($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @elseif(isset($group) && ! isset($cat))
                            <a class=" @if (current_locale() == $lang->code) active @endif"
                               href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['group' => \Illuminate\Support\Str::slug(config('settings.group_path'))]), [], true) }}">
                                <img class="lang" style="width:16px" src="{{ asset('images/'.Str::upper($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @elseif(isset($cat) && ! isset($subcat))

                            <a class=" @if (current_locale() == $lang->code) active @endif"
                               href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, null, [], true) }}">

                                <img class="lang" style="width:16px" src="{{ asset('images/'.Str::upper($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @elseif(isset($subcat) && ! isset($product))
                            <a class=" @if (current_locale() == $lang->code) active @endif"
                               href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['product' => $product->translation($lang->code)->slug]), [], true) }}">

                                <img class="lang" style="width:16px" src="{{ asset('images/'.Str::upper($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @elseif(isset($product))
                            <a class=" @if (current_locale() == $lang->code) active @endif"
                               href="{{ LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['product' => $product->translation($lang->code)->slug]), [], true) }}">

                                <img class="lang" style="width:16px" src="{{ asset('images/'.Str::upper($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @else
                            <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, null, [], true) }}">
                                <img class="lang" style="width:16px" src="{{ asset('images/'.Str::upper($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @endif
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</div>
