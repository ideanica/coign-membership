@php($brand = $settings['brand_name'] ?? 'Coignitive')
<!DOCTYPE html>
<html lang="id">
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
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}?v={{ filemtime(public_path('css/navbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ filemtime(public_path('css/select2-theme.css')) }}">
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}" defer></script>
    <script src="{{ asset('vendor/select2/select2.full.min.js') }}" defer></script>
    <script src="{{ asset('vendor/select2/id.js') }}" defer></script>
    @stack('scripts')
    <script src="{{ asset('js/navbar.js') }}?v={{ filemtime(public_path('js/navbar.js')) }}" defer></script>
    <script src="{{ asset('js/select2-controls.js') }}?v={{ filemtime(public_path('js/select2-controls.js')) }}" defer></script>
</head>
<body class="@yield('body-class')">
    <a class="skip-link button" href="#main">Lewati ke konten</a>
    @yield('body')
    @stack('body-end')
</body>
</html>
