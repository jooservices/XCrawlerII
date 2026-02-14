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
    <style>
        :root {
            --navbar-height: 56px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            overflow-x: hidden;
        }

        .dashboard-layout {
            margin-top: var(--navbar-height);
            min-height: calc(100vh - var(--navbar-height));
        }

        .dashboard-sidebar-col {
            background-color: #343a40;
            color: #fff;
            min-height: calc(100vh - var(--navbar-height));
        }

        .sidebar {
            width: 100%;
            min-height: calc(100vh - var(--navbar-height));
            position: relative;
            top: auto;
            padding-top: 20px;
            overflow-y: visible;
        }

        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 1.1rem;
            color: #ccc;
            display: block;
        }

        .sidebar a:hover {
            color: #fff;
            background-color: #495057;
        }

        .sidebar a.active {
            background-color: #0d6efd;
            color: #fff;
        }

        .main-content {
            padding: 20px;
            min-width: 0;
            min-height: calc(100vh - var(--navbar-height));
        }

        @media (min-width: 992px) {
            .dashboard-layout>.row {
                display: flex;
                flex-wrap: nowrap;
            }

            #sidebarColumn {
                display: block !important;
                flex: 0 0 25%;
                max-width: 25%;
            }

            #mainContentColumn {
                flex: 0 0 75%;
                max-width: 75%;
            }
        }

        @media (min-width: 1200px) {
            #sidebarColumn {
                flex-basis: 16.666667%;
                max-width: 16.666667%;
            }

            #mainContentColumn {
                flex-basis: 83.333333%;
                max-width: 83.333333%;
            }
        }

        body.sidebar-collapsed #sidebarColumn {
            display: none !important;
        }

        body.sidebar-collapsed #mainContentColumn {
            flex: 0 0 100%;
            max-width: 100%;
        }

        @media (max-width: 991.98px) {
            .main-content {
                min-height: calc(100vh - var(--navbar-height));
            }

            body.sidebar-mobile-open #sidebarColumn {
                display: block !important;
                position: fixed;
                top: var(--navbar-height);
                left: 0;
                width: min(85vw, 300px);
                max-width: none;
                z-index: 1050;
                min-height: calc(100vh - var(--navbar-height));
            }

            body.sidebar-mobile-open #sidebarColumn .sidebar {
                height: calc(100vh - var(--navbar-height));
                position: static;
                overflow-y: auto;
            }

            body.sidebar-mobile-open::before {
                content: '';
                position: fixed;
                top: var(--navbar-height);
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1049;
            }
        }

        .navbar-brand {
            font-weight: bold;
        }

        .card-img-top {
            height: 300px;
            /* Fixed height for consistency */
            object-fit: cover;
        }

        footer {
            margin-top: 50px;
            padding: 20px;
            background-color: #e9ecef;
            text-align: center;
        }
    </style>
</head>

<body>

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
