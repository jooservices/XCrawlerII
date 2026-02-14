<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JAV Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @vite('Modules/JAV/resources/css/dashboard-shared.css')
</head>

<body class="{{ auth()->check() && (bool) (auth()->user()->preferences['compact_mode'] ?? false) ? 'compact-mode' : '' }}">

    <!-- Top Navbar -->
    @include('jav::layouts.partials._navbar')

    <div class="container-fluid dashboard-layout">
        <div class="row g-0">
            <aside id="sidebarColumn" class="dashboard-sidebar-col d-none d-lg-block col-lg-3 col-xl-2">
                @include('jav::layouts.partials._sidebar')
            </aside>

            <main id="mainContentColumn" class="col-12 col-lg-9 col-xl-10 main-content">
                @yield('content')
                @include('jav::layouts.partials._footer')
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/lazyload.js') }}"></script>

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const body = document.body;
            const isMobileViewport = () => window.matchMedia('(max-width: 991.98px)').matches;

            const resetViewportClasses = () => {
                if (isMobileViewport()) {
                    body.classList.remove('sidebar-collapsed');
                    body.classList.remove('sidebar-mobile-open');
                } else {
                    body.classList.remove('sidebar-mobile-open');
                }
            };

            resetViewportClasses();
            window.addEventListener('resize', resetViewportClasses);

            if (!sidebarToggle) {
                return;
            }

            sidebarToggle.addEventListener('click', function () {
                if (isMobileViewport()) {
                    body.classList.toggle('sidebar-mobile-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                }
            });

            document.addEventListener('click', function (event) {
                if (!isMobileViewport()) {
                    return;
                }
                if (!body.classList.contains('sidebar-mobile-open')) {
                    return;
                }
                const clickedInSidebar = event.target.closest('#sidebar');
                const clickedToggle = event.target.closest('#sidebarToggle');
                if (!clickedInSidebar && !clickedToggle) {
                    body.classList.remove('sidebar-mobile-open');
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
