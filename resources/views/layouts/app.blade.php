<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'MIK Software - Control')</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Panel de control MIK Software para gestión personal.">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="/css/auth-mik.css">
</head>
<body>

    <!-- Glowing Background Orbs (Premium look) -->
    <div class="bg-decorations">
        <div class="orb orb-1" style="width: 500px; height: 500px; left: -150px; top: -150px; opacity: 0.3;"></div>
        <div class="orb orb-2" style="width: 600px; height: 600px; right: -200px; bottom: -200px; opacity: 0.25;"></div>
    </div>

    <!-- App Main Layout Container -->
    <div class="app-layout">
        
        <!-- Sidebar Backdrop Overlay (Mobile only) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar Navigation Menu -->
        <aside class="app-sidebar" id="appSidebar">
            <!-- Sidebar Header with Logo -->
            <div class="sidebar-logo">
                <img src="{{ asset('img/logo.png') }}" alt="MIK Software">
            </div>

            <!-- Navigation Links -->
            <ul class="sidebar-menu">

                <!-- Dashboard -->
                <li class="sidebar-menu-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-menu-link {{ Route::is('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Clientes -->
                <li class="sidebar-menu-item">
                    <a href="{{ route('clients.index') }}" class="sidebar-menu-link {{ Route::is('clients.*') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>
                        <span>Clientes</span>
                    </a>
                </li>

                <!-- Ventas (Collapsible Dropdown) -->
                @php
                    $ventasOpen = Route::is('licenses.*') || Route::is('developments.*');
                @endphp
                <li class="sidebar-dropdown-item">
                    <button 
                        class="dropdown-trigger {{ $ventasOpen ? 'open' : '' }}" 
                        id="ventasDropdownTrigger" 
                        type="button"
                        aria-expanded="{{ $ventasOpen ? 'true' : 'false' }}"
                    >
                        <span class="trigger-left">
                            <i class="bi bi-cash-stack"></i>
                            <span>Ventas</span>
                        </span>
                        <i class="bi bi-chevron-down dropdown-chevron"></i>
                    </button>

                    <!-- Submenu -->
                    <ul class="sidebar-submenu {{ $ventasOpen ? 'open' : '' }}" id="ventasSubmenu">
                        <li class="sidebar-submenu-item">
                            <a 
                                href="{{ route('licenses.index') }}" 
                                class="sidebar-submenu-link {{ Route::is('licenses.*') ? 'active' : '' }}"
                            >
                                <span>Licencias</span>
                            </a>
                        </li>
                        <li class="sidebar-submenu-item">
                            <a 
                                href="{{ route('developments.index') }}" 
                                class="sidebar-submenu-link {{ Route::is('developments.*') ? 'active' : '' }}"
                            >
                                <span>Desarrollos a Medida</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Pagos y Abonos -->
                <li class="sidebar-menu-item">
                    <a href="{{ route('payments.index') }}" class="sidebar-menu-link {{ Route::is('payments.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt"></i>
                        <span>Pagos y Abonos</span>
                    </a>
                </li>

                <!-- Préstamos -->
                <li class="sidebar-menu-item">
                    <a href="{{ route('loans.index') }}" class="sidebar-menu-link {{ Route::is('loans.*') ? 'active' : '' }}">
                        <i class="bi bi-hand-thumbs-up-fill"></i>
                        <span>Préstamos</span>
                    </a>
                </li>

                <!-- Créditos -->
                <li class="sidebar-menu-item">
                    <a href="{{ route('credits.index') }}" class="sidebar-menu-link {{ Route::is('credits.*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-front"></i>
                        <span>Créditos</span>
                    </a>
                </li>

                <!-- Reportes (Collapsible Dropdown) -->
                @php
                    $reportesOpen = Route::is('reports.*');
                @endphp
                <li class="sidebar-dropdown-item">
                    <button
                        class="dropdown-trigger {{ $reportesOpen ? 'open' : '' }}"
                        id="reportesDropdownTrigger"
                        type="button"
                        aria-expanded="{{ $reportesOpen ? 'true' : 'false' }}"
                    >
                        <span class="trigger-left">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Reportes</span>
                        </span>
                        <i class="bi bi-chevron-down dropdown-chevron"></i>
                    </button>
                    <ul class="sidebar-submenu {{ $reportesOpen ? 'open' : '' }}" id="reportesSubmenu">
                        <li class="sidebar-submenu-item">
                            <a href="{{ route('reports.index') }}"
                               class="sidebar-submenu-link {{ Route::is('reports.*') ? 'active' : '' }}">
                                <span>Estado de Cuentas</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Importar Base de Datos -->
                <li class="sidebar-menu-item">
                    <a href="{{ route('db-import.index') }}" class="sidebar-menu-link {{ Route::is('db-import.*') ? 'active' : '' }}">
                        <i class="bi bi-database-up"></i>
                        <span>Importar BD</span>
                    </a>
                </li>

            </ul>

            <!-- Sidebar Profile Footer -->
            <div class="sidebar-profile">
                <div class="profile-user">
                    <!-- Circular avatar icon based on first character of name -->
                    <div class="profile-avatar">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="profile-details">
                        <div class="profile-name">{{ Auth::user()->name }}</div>
                        <div class="profile-role">Administrador</div>
                    </div>
                </div>

                <!-- Secure Logout Action -->
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="profile-logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- App Main Content Area -->
        <main class="app-main">
            <!-- Mobile Sticky Top Bar (Only visible <= 1024px) -->
            <div class="mobile-top-bar">
                <div class="mobile-logo">
                    <img src="{{ asset('img/logo.png') }}" alt="MIK Software">
                </div>
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle Sidebar">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <!-- Dashboard Dynamic Content -->
            <div class="main-content">
                <!-- Page Title Header -->
                <div class="page-header">
                    <h1 class="page-title">@yield('page_title', 'Dashboard')</h1>
                    <p class="page-subtitle">@yield('page_subtitle', 'Bienvenido al sistema de gestión.')</p>
                </div>

                @yield('content')
            </div>
        </main>

    </div>

    <!-- Responsive Sidebar & Dropdown JavaScript Controller (Vanilla JS) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── Mobile Sidebar Toggle ──────────────────────────────────
            const menuBtn    = document.getElementById('mobileMenuBtn');
            const sidebar    = document.getElementById('appSidebar');
            const overlay    = document.getElementById('sidebarOverlay');

            if (menuBtn && sidebar && overlay) {
                menuBtn.addEventListener('click', function() {
                    sidebar.classList.add('active');
                    overlay.classList.add('active');
                });
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }

            // ── Ventas Dropdown Toggle ─────────────────────────────────
            const ventasTrigger = document.getElementById('ventasDropdownTrigger');
            const ventasSubmenu = document.getElementById('ventasSubmenu');

            if (ventasTrigger && ventasSubmenu) {
                ventasTrigger.addEventListener('click', function () {
                    const isOpen = ventasSubmenu.classList.contains('open');

                    if (isOpen) {
                        ventasSubmenu.classList.remove('open');
                        ventasTrigger.classList.remove('open');
                        ventasTrigger.setAttribute('aria-expanded', 'false');
                    } else {
                        ventasSubmenu.classList.add('open');
                        ventasTrigger.classList.add('open');
                        ventasTrigger.setAttribute('aria-expanded', 'true');
                    }
                });
            }

            // ── Reportes Dropdown Toggle ───────────────────────────────
            const reportesTrigger = document.getElementById('reportesDropdownTrigger');
            const reportesSubmenu = document.getElementById('reportesSubmenu');

            if (reportesTrigger && reportesSubmenu) {
                reportesTrigger.addEventListener('click', function () {
                    const isOpen = reportesSubmenu.classList.contains('open');

                    if (isOpen) {
                        reportesSubmenu.classList.remove('open');
                        reportesTrigger.classList.remove('open');
                        reportesTrigger.setAttribute('aria-expanded', 'false');
                    } else {
                        reportesSubmenu.classList.add('open');
                        reportesTrigger.classList.add('open');
                        reportesTrigger.setAttribute('aria-expanded', 'true');
                    }
                });
            }

        });
    </script>
</body>
</html>
