<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'MIK Software - Acceso')</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Acceso seguro a MIK Software Personal App. Gestiona tus tareas, notas e información personal de forma moderna y segura.">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Icons (Lucide Icons via CDN for modern look) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="/css/auth-mik.css">
</head>
<body>

    <!-- Premium Animated Background Orbs -->
    <div class="bg-decorations">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Main Dynamic Content Wrapper -->
    <div class="auth-container">
        @yield('content')
    </div>

</body>
</html>
