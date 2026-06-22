<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @include('layouts.admin-styles')
    @stack('styles')
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            @include('layouts.partials.header')

            <!-- Page Content -->
            <div class="content-wrapper">
                @if(session('success'))
                    <div class="alert alert-success">
                        <span>{!! session('success') !!}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    if (sidebar && sidebar.classList.contains('open')) {
                        if (!sidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                            sidebar.classList.remove('open');
                        }
                    }
                }
            });
        });
    </script>
    @include('layouts.partials.ai-chat-widget')
    @stack('scripts')

</body>
</html>
