@php
    $canonicalUrl = null;
    $categoryParameters = [
        'cat' => isset($cat) && $cat ? $cat->translation->slug : null,
        'subcat' => isset($subcat) && $subcat ? $subcat->translation->slug : null,
    ];

    if (isset($brand) && $brand) {
        $canonicalUrl = route('catalog.route.brand', array_merge($categoryParameters, ['brand' => $brand->translation->slug]));
    } elseif (request()->routeIs('catalog.route.actions')) {
        $canonicalUrl = route('catalog.route.actions', $categoryParameters);
    } elseif (isset($group) && $group) {
        $canonicalGroup = isset($cat) && $cat ? \Illuminate\Support\Str::slug($cat->group) : $group;
        $canonicalUrl = route('catalog.route', array_merge($categoryParameters, ['group' => $canonicalGroup]));
    } elseif (isset($author) && $author) {
        $canonicalUrl = route('catalog.route.author', array_merge($categoryParameters, ['author' => $author->translation->slug]));
    } elseif (isset($publisher) && $publisher) {
        $canonicalUrl = route('catalog.route.publisher', array_merge($categoryParameters, ['publisher' => $publisher->translation->slug]));
    }
@endphp
@if ($canonicalUrl)
    @include('front.layouts.partials.canonical', ['canonicalUrl' => $canonicalUrl])
@endif
