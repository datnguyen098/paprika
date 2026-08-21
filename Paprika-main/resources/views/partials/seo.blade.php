@php
    $seo = $seo ?? [];
    $title = $seo['title'] ?? localized_setting('default_meta_title', is_english()
        ? 'Paprika Patras | Vietnamese cuisine and grilled dishes'
        : 'Paprika Patras | Ẩm thực Việt Nam và món nướng Hy Lạp');
    $description = $seo['description'] ?? localized_setting('default_meta_description', is_english()
        ? 'Paprika serves Vietnamese cuisine, pho, banh mi, nem, Greek grilled dishes and easy table booking in Patras.'
        : 'Paprika phục vụ ẩm thực Việt Nam, phở, bánh mì, nem, món nướng Hy Lạp và đặt bàn tiện lợi tại Patras.');
    $keywords = $seo['keywords'] ?? localized_setting('default_meta_keywords', is_english()
        ? 'Paprika Patras, Vietnamese cuisine Patras, pho, banh mi, Greek grilled dishes'
        : 'Paprika Patras, ẩm thực Việt Nam Patras, phở, bánh mì, nem, món nướng Hy Lạp');
    $canonical = $seo['canonical'] ?? url()->current();
    $image = $seo['image'] ?? \App\Services\SeoService::defaultImage();
    $type = $seo['type'] ?? 'website';
    $ogLocale = config('locales.supported.'.current_locale().'.og_locale', 'vi_VN');
    $alternates = $seo['alternates'] ?? [
        'vi' => route('home'),
        'en' => \Illuminate\Support\Facades\Route::has('localized.en.home') ? route('localized.en.home') : route('home'),
        'el' => \Illuminate\Support\Facades\Route::has('localized.el.home') ? route('localized.el.home') : route('home'),
    ];
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<link rel="canonical" href="{{ $canonical }}">

@foreach ($alternates as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
@endforeach
@if (isset($alternates['vi']))
    <link rel="alternate" hreflang="x-default" href="{{ $alternates['vi'] }}">
@endif

<meta property="og:locale" content="{{ $ogLocale }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ localized_setting('restaurant_name', 'Paprika') }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">

@if (setting('favicon'))
    <link rel="icon" href="{{ media_url(setting('favicon')) }}">
@endif
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

{!! setting('google_analytics_code') !!}
{!! setting('google_search_console') !!}
{!! setting('facebook_pixel_code') !!}
