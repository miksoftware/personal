@extends('layouts.auth')

@section('title', 'Registro de Cuenta - MIK Software')

@section('content')
<div class="auth-card">
    <!-- Header with MIK Software SVG Logo -->
    <div class="brand-header">
        @include('partials.logo')
    </div>

    <!-- Alert Banners for Validation Status -->
    @if($errors->any())
        <div class="alert-banner" id="error-alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Por favor corrige los errores indicados abajo.</span>
        </div>
    @endif

    <!-- Register Form -->
    <form action="{{ route('register') }}" method="POST" autocomplete="off">
        @csrf

        <!-- Name Field -->
        <div class="form-group">
            <label for="name" class="form-label">Nombre Completo</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <i class="bi bi-person"></i>
                </span>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input @error('name') is-invalid @enderror" 
                    placeholder="Tu nombre" 
                    value="{{ old('name') }}" 
                    required 
                    autofocus
                >
            </div>
            @error('name')
                <span class="error-msg">
                    <i class="bi bi-x-circle"></i> {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Email Field -->
        <div class="form-group">
            <label for="email" class="form-label">Correo Electrónico</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <i class="bi bi-envelope"></i>
                </span>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input @error('email') is-invalid @enderror" 
                    placeholder="ejemplo@miksoftware.com" 
                    value="{{ old('email') }}" 
                    required
                >
            </div>
            @error('email')
                <span class="error-msg">
                    <i class="bi bi-x-circle"></i> {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Password Field -->
        <div class="form-group">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <i class="bi bi-lock"></i>
                </span>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input @error('password') is-invalid @enderror" 
                    placeholder="Mínimo 8 caracteres" 
                    required
                >
            </div>
            @error('password')
                <span class="error-msg">
                    <i class="bi bi-x-circle"></i> {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Password Confirmation Field -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <i class="bi bi-shield-lock"></i>
                </span>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    class="form-input" 
                    placeholder="Repite tu contraseña" 
                    required
                >
            </div>
        </div>

        <!-- Action Button -->
        <button type="submit" class="btn-submit" id="btn-register">
            <span>Crear Cuenta</span>
            <i class="bi bi-person-plus-fill" style="font-size: 18px;"></i>
        </button>
    </form>
</div>

<!-- Navigation Footer -->
<div class="auth-footer">
    <span>¿Ya tienes una cuenta?</span>
    <a href="{{ route('login') }}" id="login-link">Inicia sesión aquí</a>
</div>
@endsection
