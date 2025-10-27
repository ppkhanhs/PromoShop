<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PromoShop')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('client/assets/theme.css') }}">
    @stack('styles')
</head>
<body>
    @include('layouts.client.header')

    <main class="site-main py-4">
        <div class="container">
            @include('shared.alerts')
            @yield('content')
        </div>
    </main>

    @include('layouts.client.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    @stack('scripts')
</body>
</html>
