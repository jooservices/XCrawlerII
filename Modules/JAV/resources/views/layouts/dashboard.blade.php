<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JAV Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
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
            margin-left: 250px;
            /* Width of sidebar */
            padding: 20px;
            margin-top: 56px;
            /* Height of navbar */
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('jav.dashboard') }}">JAV Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('jav.dashboard') }}">Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="{{ route('jav.dashboard') }}"
            class="{{ request()->routeIs('jav.dashboard') && !request()->routeIs('jav.dashboard.actors') && !request()->routeIs('jav.dashboard.tags') ? 'active' : '' }}">
            <i class="fas fa-film me-2"></i> Movies
        </a>
        <a href="{{ route('jav.dashboard.actors') }}"
            class="{{ request()->routeIs('jav.dashboard.actors') ? 'active' : '' }}">
            <i class="fas fa-users me-2"></i> Actors
        </a>
        <a href="{{ route('jav.dashboard.tags') }}"
            class="{{ request()->routeIs('jav.dashboard.tags') ? 'active' : '' }}">
            <i class="fas fa-tags me-2"></i> Tags
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; {{ date('Y') }} JAV Dashboard. All rights reserved.</p>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>