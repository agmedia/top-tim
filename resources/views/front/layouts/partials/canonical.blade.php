@php
    $page = filter_var(request()->query('page'), FILTER_VALIDATE_INT);
    if ($page !== false && $page > 1) {
        $canonicalUrl .= (str_contains($canonicalUrl, '?') ? '&' : '?') . 'page=' . $page;
    }
@endphp
<link rel="canonical" href="{{ $canonicalUrl }}" />
