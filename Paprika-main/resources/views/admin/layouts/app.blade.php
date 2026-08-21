<!doctype html>
<html lang="vi" data-upload-max-kb="{{ config('uploads.max_image_kb', 10240) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('storefront.partials.js-translations')
    <title>@yield('title', 'Admin') | Paprika</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen lg:flex">
        @include('admin.partials.sidebar')

        <div class="min-w-0 flex-1">
            @include('admin.partials.topbar')

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @include('admin.partials.alert')
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm(form.dataset.confirm || 'Bạn chắc chắn muốn thực hiện thao tác này?')) {
                    event.preventDefault();
                }
            });
        });

        const adminToggle = document.querySelector('[data-admin-sidebar-toggle]');
        const adminSidebar = document.querySelector('[data-admin-sidebar]');
        if (adminToggle && adminSidebar) {
            adminToggle.addEventListener('click', () => adminSidebar.classList.toggle('hidden'));
        }
    </script>
    @stack('scripts')
</body>
</html>
