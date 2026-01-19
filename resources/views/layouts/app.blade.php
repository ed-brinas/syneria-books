<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'SyneriaBooks')</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Design Spec: Verdana 10pt Typography -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    <style>
        /* Fallback if app.css isn't loaded yet */
        body { font-family: 'Verdana', sans-serif; font-size: 10pt; }
    </style>
</head>
<body>

    {{-- Preloader / Spinner --}}
    @include('layouts.partials.spinner')

    @auth
        {{-- Hide navigation if user hasn't completed setup (no tenant_id) --}}
        @if(auth()->user()->tenant_id)
            @include('layouts.partials.nav')
        @endif
    @endauth

    <div class="container-fluid p-0">
        {{-- Check if $slot exists (for Livewire) OR yield content (for Standard Blade) --}}
        @if(isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </div>

    <!-- Livewire Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>