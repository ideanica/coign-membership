@php($brand = $settings['brand_name'] ?? 'Coignitive')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#5294ff">
    <meta name="description" content="@yield('meta-description', 'Website, layanan digital, dan membership.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <title>@yield('title') — {{ $brand }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Untitled27_20261014014436.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/membership.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}{{ $assetVersion('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}{{ $assetVersion('css/select2-theme.css') }}">
    @if(request()->routeIs('admin.*'))<link rel="stylesheet" href="{{ asset('css/admin-responsive.css') }}{{ $assetVersion('css/admin-responsive.css') }}">@endif
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}" defer></script>
    <script src="{{ asset('vendor/select2/select2.full.min.js') }}" defer></script>
    @if(app()->getLocale() === 'id')<script src="{{ asset('vendor/select2/id.js') }}" defer></script>@endif
    @stack('scripts')
    <script>window.siteTranslations = {{ Illuminate\Support\Js::from(__('browser')) }}; window.siteText = (text) => window.siteTranslations[text] || text;</script>
    <script src="{{ asset('js/navbar.js') }}{{ $assetVersion('js/navbar.js') }}" defer></script>
    <script src="{{ asset('js/select2-controls.js') }}{{ $assetVersion('js/select2-controls.js') }}" defer></script>
    <script src="{{ asset('js/password-visibility.js') }}{{ $assetVersion('js/password-visibility.js') }}" defer></script>
</head>
<body class="@yield('body-class')">
    <a class="skip-link button" href="#main">{{ __('Lewati ke konten') }}</a>
    @yield('body')
    @stack('body-end')
</body>
</html>
