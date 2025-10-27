<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PromoShop Admin')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
</head>
<body class="bg-light">
    @include('layouts.admin.header')

    <div class="container-fluid">
        <div class="row flex-nowrap">
            <div class="col-12 col-md-3 col-lg-2 bg-white border-end min-vh-100 p-0">
                @include('layouts.admin.sidebar')
            </div>
            <main class="col py-4">
                <div class="container-fluid">
                    @include('shared.alerts')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @include('layouts.admin.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    @stack('scripts')
</body>
</html>
