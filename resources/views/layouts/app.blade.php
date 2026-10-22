<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LeadFlow') }} - Workflow Engine</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="nav-header">
        <a href="{{ url('/') }}" class="nav-brand">
            <div class="nav-brand-icon">&#x26A1;</div>
            <span>LeadFlow</span>
        </a>

        <nav>
            <ul class="nav-links">
                <li>
                    <a href="{{ route('workflows.index') }}" class="nav-link {{ request()->is('workflows*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        Workflows
                    </a>
                </li>
                <li>
                    <a href="{{ route('leads.index') }}" class="nav-link {{ request()->is('leads*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        Leads & Testing
                    </a>
                </li>
                <li>
                    <a href="{{ route('runs.index') }}" class="nav-link {{ request()->is('runs*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        Workflow Runs
                    </a>
                </li>
            </ul>
        </nav>

        <div class="nav-actions">
            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-primary">
                + New Lead
            </a>
        </div>
    </header>

    @if(session('success'))
        <div style="background: var(--success-bg); color: var(--success); padding: 10px 24px; border-bottom: 1px solid var(--line); font-size: 13.5px; font-weight: 500;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background: var(--danger-bg); color: var(--danger); padding: 10px 24px; border-bottom: 1px solid var(--line); font-size: 13.5px; font-weight: 500;">
            {{ session('error') }}
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
