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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            transition: margin-left 0.3s ease;
        }

        .sidebar {
            height: 100vh;
            position: fixed;
            top: 56px;
            /* Height of navbar */
            left: 0;
            width: 250px;
            padding-top: 20px;
            background-color: #343a40;
            color: #fff;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 999;
            /* Below navbar but above content */
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
            margin-left: 250px !important;
            /* Width of sidebar */
            padding: 20px;
            margin-top: 56px;
            /* Height of navbar */
            transition: margin-left 0.3s ease, width 0.3s ease;
            min-height: 100vh;
            width: calc(100% - 250px);
            /* Ensure content doesn't extend under sidebar */
        }

        /* Sidebar toggle states */
        body.sidebar-hidden .sidebar {
            transform: translateX(-250px);
        }

        body.sidebar-hidden .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
                z-index: 1050;
                /* Higher z-index on mobile for overlay effect */
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }

            body.sidebar-visible .sidebar {
                transform: translateX(0);
            }

            /* Add overlay when sidebar is visible on mobile */
            body.sidebar-visible::before {
                content: '';
                position: fixed;
                top: 0;
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

    <!-- Sidebar -->
    @include('jav::layouts.partials._sidebar')

    <!-- Main Content -->
    <div class="main-content">
        @yield('content')

        <!-- Footer -->
        @include('jav::layouts.partials._footer')
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/lazyload.js') }}"></script>

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const body = document.body;

            // Load sidebar state from localStorage
            const sidebarState = localStorage.getItem('sidebarState');
            const isMobile = window.innerWidth <= 768;

            if (isMobile) {
                // On mobile, sidebar is hidden by default
                if (sidebarState === 'visible') {
                    body.classList.add('sidebar-visible');
                }
            } else {
                // On desktop, sidebar is visible by default
                if (sidebarState === 'hidden') {
                    body.classList.add('sidebar-hidden');
                }
            }

            // Toggle sidebar on button click
            sidebarToggle.addEventListener('click', function () {
                if (isMobile) {
                    body.classList.toggle('sidebar-visible');
                    const isVisible = body.classList.contains('sidebar-visible');
                    localStorage.setItem('sidebarState', isVisible ? 'visible' : 'hidden');
                } else {
                    body.classList.toggle('sidebar-hidden');
                    const isHidden = body.classList.contains('sidebar-hidden');
                    localStorage.setItem('sidebarState', isHidden ? 'hidden' : 'visible');
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>