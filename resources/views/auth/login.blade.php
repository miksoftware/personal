@extends('layouts.auth')

@section('title', 'Iniciar Sesión - MIK Software')

@section('content')
<div class="auth-card">
    <!-- Header with MIK Software SVG Logo -->
    <div class="brand-header">
        @include('partials.logo')
    </div>

    <!-- Alert Banners for Validation Status -->
    @if(session('status'))
        <div class="alert-banner-success" id="status-alert">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert-banner" id="error-alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <!-- Login Form -->
    <form action="{{ route('login') }}" method="POST" autocomplete="off">
        @csrf

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
                    autofocus
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
                    placeholder="••••••••••••" 
                    required
                >
            </div>
            @error('password')
                <span class="error-msg">
                    <i class="bi bi-x-circle"></i> {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Form Actions (Remember Me & Forgot Password) -->
        <div class="form-actions">
            <label class="checkbox-container">
                Recuérdame
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <span class="checkmark"></span>
            </label>
            
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">¿Olvidaste tu contraseña?</a>
            @else
                <a href="#" class="forgot-link" onclick="alert('Funcionalidad en desarrollo para tu cuenta personal.')">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <!-- Action Button -->
        <button type="submit" class="btn-submit" id="btn-login">
            <span>Iniciar Sesión</span>
            <i class="bi bi-arrow-right-short" style="font-size: 20px;"></i>
        </button>
    </form>
</div>

<!-- Navigation Footer -->
<div class="auth-footer">
    <span>¿No tienes una cuenta?</span>
    <a href="{{ route('register') }}" id="register-link">Regístrate gratis</a>
</div>
@endsection
