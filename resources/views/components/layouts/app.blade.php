{{-- resources/views/components/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'SyneriaBooks' }}</title>

    <!-- Vite Resources (Bootstrap + CSS + JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        .screen { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { max-width: 400px; padding: 2rem; background: #f7f7f7; border-radius: 0.5rem; }
    </style>
</head>
<body class="bg-light">

    @include('components.layouts.partials.spinner')
    @include('components.layouts.partials.error-screen')
    @include('components.layouts.partials.nav')

    {{-- Main Content --}}
    <main class="container-fluid py-4">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>