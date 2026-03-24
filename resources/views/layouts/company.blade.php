<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $company->razon_social ?? $company->name }} - {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Anti-flicker Sidebar Script -->
    <meta name="turbo-cache-control" content="no-preview">
    <script>
        (function() {
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
            document.documentElement.classList.add('sidebar-initializing');
            // Remove initializing class after a short delay
            setTimeout(() => document.documentElement.classList.remove('sidebar-initializing'), 200);
        })();
    </script>
    <style>
        /* Base styles before Alpine loads */
        .sidebar-collapsed aside { width: 80px !important; }
        .sidebar-initializing aside { transition: none !important; }
        
        /* Centering icons in collapsed mode before Alpine boots */
        .sidebar-collapsed aside .logo-wrapper { justify-content: center !important; }
        .sidebar-collapsed aside .nav-item-link { justify-content: center !important; padding: 0.625rem !important; }

        [x-cloak] { display: none !important; }

        .sidebar-collapsed aside span,
        .sidebar-collapsed aside p[x-show],
        .sidebar-collapsed aside div[x-show]:not(.fixed),
        .sidebar-collapsed aside i[x-show],
        .sidebar-collapsed aside form[x-show] {
            display: none !important;
        }
    </style>
</head>

<body class="font-sans antialiased bg-slate-50 flex overflow-hidden" x-data="{
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    hoveringLabel: null,
    tooltipTop: 0
}" x-init="
    $watch('sidebarCollapsed', val => {
        localStorage.setItem('sidebarCollapsed', val);
        document.documentElement.classList.toggle('sidebar-collapsed', val);
    });
    // Ensure initial sync
    document.documentElement.classList.toggle('sidebar-collapsed', sidebarCollapsed);
">

    {{-- Global Tooltip for Collapsed Sidebar --}}
    <div x-show="sidebarCollapsed && hoveringLabel" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
        class="fixed py-1.5 px-3 bg-slate-900/90 backdrop-blur-sm text-white text-[10px] font-bold rounded-lg shadow-xl z-[110] whitespace-nowrap pointer-events-none"
        :style="`left: 76px; top: ${tooltipTop}px`" x-text="hoveringLabel" x-cloak style="display: none;">
    </div>
    {{-- Sidebar Navigation --}}
    <aside :class="sidebarCollapsed ? 'w-20' : 'w-68'"
        class="w-68 bg-white border-r border-gray-200 flex-shrink-0 hidden lg:flex flex-col h-screen sticky top-0 z-50 transition-all duration-300">
        {{-- Sidebar Header / Logo --}}
        <div class="h-20 flex items-center px-6 border-b border-gray-100 flex-shrink-0">
            <div class="logo-wrapper flex items-center w-full transition-all duration-300"
                :class="sidebarCollapsed ? 'justify-center pr-0' : 'justify-between'">
                <a href="{{ route('companies.dashboard', $company) }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/20 flex-shrink-0 transition-transform"
                        :class="sidebarCollapsed ? 'scale-90' : ''">
                        R
                    </div>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition-opacity duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        class="font-black text-slate-800 tracking-tighter text-2xl uppercase whitespace-nowrap">Remsys</span>
                </a>
            </div>

            {{-- Unified Floating Toggle Button --}}
            <button @click="sidebarCollapsed = !sidebarCollapsed"
                class="absolute -right-3 top-7 w-6 h-6 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-400 hover:text-blue-600 shadow-md z-50 transition-all hover:scale-110 active:scale-95">
                <i class="fas text-[10px] transition-transform duration-300"
                    :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
            </button>
        </div>

        {{-- Sidebar Content --}}
        <div class="flex-1 overflow-y-auto py-6 px-4 space-y-8 no-scrollbar">
            {{-- App Section --}}
            <div>
                <p x-show="!sidebarCollapsed"
                    class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Plataforma</p>
                <div class="space-y-1">
                    <a href="{{ route('companies.dashboard', $company) }}" title="Dashboard"
                        @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Dashboard'; tooltipTop = $el.getBoundingClientRect().top + 8 }"
                        @mouseleave="hoveringLabel = null"
                        class="nav-item-link flex items-center rounded-xl text-sm font-bold transition-all transition-colors"
                        :class="sidebarCollapsed ? 'justify-center p-2.5 hover:bg-slate-50' : 'gap-3 px-3 py-2.5 ' + ((
                                '{{ $activeTab ?? '' }}'
                                === 'dashboard') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:text-blue-600')">
                        <i
                            class="fas fa-chart-pie w-5 text-center {{ ($activeTab ?? '') === 'dashboard' ? 'text-blue-600' : 'text-slate-400' }} transition-colors"></i>
                        <span x-show="!sidebarCollapsed">Dashboard</span>
                    </a>
                </div>
            </div>

            @php
                $currentCompanyTab = $activeTab ?? '';
                $isAccountingSection = in_array(
                    $currentCompanyTab,
                    [
                        'accounting',
                        'accounting-data',
                        'accounting-remunerations',
                        'cost-centers',
                        'employees',
                        'honorarios',
                    ],
                    true,
                );
                $isCompanyDataActive = in_array(
                    $currentCompanyTab,
                    ['accounting', 'accounting-data', 'cost-centers'],
                    true,
                );
                $isRemunerationsActive = $currentCompanyTab === 'accounting-remunerations';
                $isEmployeesActive = $currentCompanyTab === 'employees';
            @endphp

            {{-- Company Section --}}
            <div>
                <p x-show="!sidebarCollapsed"
                    class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Gestión de Empresa
                </p>
                <div class="space-y-1" x-data="{ activeTab: '{{ $activeTab ?? 'employees' }}', accountingOpen: @js($isAccountingSection), showBalloon: false, balloonTimeout: null }">
                    <div class="rounded-2xl transition-all duration-200"
                        :class="sidebarCollapsed ? '' : 'border border-slate-200/80 bg-slate-50/70 p-1'">
                        <button type="button"
                            @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Contabilidad'; tooltipTop = $el.getBoundingClientRect().top + 8 }"
                            @mouseleave="hoveringLabel = null"
                            @click="sidebarCollapsed ? (showBalloon = !showBalloon) : accountingOpen = !accountingOpen"
                            class="nav-item-link flex w-full items-center rounded-xl text-left text-sm font-bold transition-all"
                            :class="sidebarCollapsed ? 'justify-center p-2.5 hover:bg-slate-100' : 'gap-3 px-3 py-2.5 ' + ((
                                    accountingOpen || @js($isAccountingSection)) ?
                                'bg-white text-slate-900 shadow-sm border border-slate-200' :
                                'text-slate-600 hover:bg-white group')">
                            <i class="fas fa-calculator w-5 text-center transition-colors"
                                :class="(accountingOpen || @js($isAccountingSection)) && !sidebarCollapsed ?
                                    'text-blue-600' :
                                    'text-slate-400 group-hover:text-blue-500'"></i>
                            <span x-show="!sidebarCollapsed" class="flex-1">Contabilidad</span>
                            <i x-show="!sidebarCollapsed"
                                class="fas fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': accountingOpen }"></i>
                        </button>

                        {{-- Collapsed Floating Menu --}}
                        <div x-show="showBalloon && sidebarCollapsed"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-x-4"
                            x-transition:enter-end="opacity-100 translate-x-0" @mouseenter="hoveringLabel = null"
                            @click.away="showBalloon = false" x-cloak
                            class="fixed left-20 ml-0 py-3 px-2 bg-white border border-slate-200 rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] z-[100] w-64"
                            style="margin-top: -45px;">
                            <div class="absolute -left-4 top-0 bottom-0 w-4"></div> {{-- Invisible bridge --}}
                            <p
                                class="px-3 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pb-2 border-b border-slate-50">
                                Contabilidad</p>
                            <div class="space-y-1">
                                <a href="{{ route('companies.edit', ['company' => $company, 'section' => 'company-data']) }}"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ $isCompanyDataActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                                    <i
                                        class="fas fa-building w-4 text-center {{ $isCompanyDataActive ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                    Datos empresa
                                </a>
                                <a href="{{ route('companies.edit', ['company' => $company, 'section' => 'remunerations', 'tab' => 'remu']) }}"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ $isRemunerationsActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                                    <i
                                        class="fas fa-money-bill-wave w-4 text-center {{ $isRemunerationsActive ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                    Remuneraciones
                                </a>
                                <a href="{{ route('companies.employees', $company) }}"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ $isEmployeesActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                                    <i
                                        class="fas fa-users-viewfinder w-4 text-center {{ $isEmployeesActive ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                    Nómina de empleados
                                </a>
                                <a href="{{ route('companies.freelancers.index', $company) }}"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ ($activeTab ?? '') === 'honorarios' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                                    <i
                                        class="fas fa-file-invoice w-4 text-center {{ ($activeTab ?? '') === 'honorarios' ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                    Colaboradores a Honorarios
                                </a>
                            </div>
                        </div>

                        <div x-show="accountingOpen && !sidebarCollapsed" x-transition x-cloak
                            class="mt-1 space-y-1 px-2 pb-2">
                            <a href="{{ route('companies.edit', ['company' => $company, 'section' => 'company-data']) }}"
                                class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ $isCompanyDataActive ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600' }}">
                                <i
                                    class="fas fa-building w-4 text-center {{ $isCompanyDataActive ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                <span x-show="!sidebarCollapsed">Datos empresa</span>
                            </a>

                            <a href="{{ route('companies.edit', ['company' => $company, 'section' => 'remunerations', 'tab' => 'remu']) }}"
                                class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ $isRemunerationsActive ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600' }}">
                                <i
                                    class="fas fa-money-bill-wave w-4 text-center {{ $isRemunerationsActive ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                <span x-show="!sidebarCollapsed">Remuneraciones</span>
                            </a>

                            <a href="{{ route('companies.employees', $company) }}"
                                class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ $isEmployeesActive ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600' }}">
                                <i
                                    class="fas fa-users-viewfinder w-4 text-center {{ $isEmployeesActive ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                <span x-show="!sidebarCollapsed">Nómina de empleados</span>
                            </a>

                            <a href="{{ route('companies.freelancers.index', $company) }}"
                                class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-all {{ ($activeTab ?? '') === 'honorarios' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600' }}">
                                <i
                                    class="fas fa-file-invoice w-4 text-center {{ ($activeTab ?? '') === 'honorarios' ? 'text-blue-600' : 'text-slate-400' }}"></i>
                                <span x-show="!sidebarCollapsed">Colaboradores a Honorarios</span>
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('companies.transactions', $company) }}" title="Movimientos"
                        @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Movimientos'; tooltipTop = $el.getBoundingClientRect().top + 8 }"
                        @mouseleave="hoveringLabel = null"
                        class="nav-item-link flex items-center rounded-xl text-sm font-bold transition-all transition-colors"
                        :class="sidebarCollapsed ? 'justify-center p-2.5 hover:bg-slate-50' : 'gap-3 px-3 py-2.5 ' + (
                            activeTab === 'transactions' ?
                            ' bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            ' text-slate-600 hover:bg-slate-50 group')">
                        <i class="fas fa-money-bill-transfer w-5 text-center transition-colors"
                            :class="activeTab === 'transactions' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'"></i>
                        <span x-show="!sidebarCollapsed">Movimientos</span>
                    </a>

                </div>
            </div>

            {{-- Payroll Section --}}
            <div>
                <p x-show="!sidebarCollapsed"
                    class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Nómina
                </p>
                <div class="space-y-1" x-data="{ activeTab: '{{ $activeTab ?? 'employees' }}' }">
                    <a href="{{ route('companies.payroll-periods.index', ['company' => $company]) }}"
                        title="Períodos de Nómina"
                        @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Períodos de Nómina'; tooltipTop = $el.getBoundingClientRect().top + 8 }"
                        @mouseleave="hoveringLabel = null"
                        class="nav-item-link flex items-center rounded-xl text-sm font-bold transition-all transition-colors"
                        :class="sidebarCollapsed ? 'justify-center p-2.5 hover:bg-slate-50' : 'gap-3 px-3 py-2.5 ' + ((
                                '{{ $activeTab ?? '' }}'
                                === 'payroll-periods') ?
                            'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:text-blue-600')">
                        <i class="fas fa-calendar-alt w-5 text-center transition-colors"
                            :class="'{{ $activeTab ?? '' }}'
                            === 'payroll-periods' ? 'text-blue-600' : 'text-slate-400'"></i>
                        <span x-show="!sidebarCollapsed">Períodos de Nómina</span>
                    </a>

                    <a href="{{ route('payrolls.byCompany', ['company' => $company]) }}" title="Historial de Nóminas"
                        @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Historial de Nóminas'; tooltipTop = $el.getBoundingClientRect().top + 8 }"
                        @mouseleave="hoveringLabel = null"
                        class="nav-item-link flex items-center rounded-xl text-sm font-bold transition-all transition-colors"
                        :class="sidebarCollapsed ? 'justify-center p-2.5 hover:bg-slate-50' : 'gap-3 px-3 py-2.5 ' + ((
                                '{{ $activeTab ?? '' }}'
                                === 'payrolls') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:text-blue-600')">
                        <i class="fas fa-file-invoice-dollar w-5 text-center transition-colors"
                            :class="'{{ $activeTab ?? '' }}'
                            === 'payrolls' ? 'text-blue-600' : 'text-slate-400'"></i>
                        <span x-show="!sidebarCollapsed">Historial de Nóminas</span>
                    </a>
                </div>
            </div>

            {{-- Access Section --}}
            <div>
                <p x-show="!sidebarCollapsed"
                    class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Configuración</p>
                <div class="space-y-1">
                    <a href="{{ route('companies.users.index', ['company' => $company]) }}"
                        title="Usuarios y Accesos"
                        @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Usuarios y Accesos'; tooltipTop = $el.getBoundingClientRect().top + 8 }"
                        @mouseleave="hoveringLabel = null"
                        class="nav-item-link flex items-center rounded-xl text-sm font-bold transition-all transition-colors"
                        :class="sidebarCollapsed ? 'justify-center p-2.5 hover:bg-slate-50' : 'gap-3 px-3 py-2.5 ' + ((
                                '{{ $activeTab ?? '' }}'
                                === 'users') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:text-blue-600 hover:bg-slate-50')">
                        <i
                            class="fas fa-user-shield w-5 text-center {{ ($activeTab ?? '') === 'users' ? 'text-blue-600' : 'text-slate-400' }} transition-colors"></i>
                        <span x-show="!sidebarCollapsed">Usuarios y Accesos</span>
                    </a>
                </div>
            </div>

            @if (Auth::user()->hasRole('super-admin') || Auth::user()->getAllCompanies()->count() > 1)
                {{-- Actions Section --}}
                <div class="pt-4 border-t border-slate-100">
                    <div class="space-y-1">
                        <a href="{{ route('companies.index') }}" title="Volver a Empresas"
                            @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Volver a Empresas'; tooltipTop = $el.getBoundingClientRect().top + 8 }"
                            @mouseleave="hoveringLabel = null"
                            class="nav-item-link flex items-center rounded-xl text-sm font-bold text-amber-600 bg-amber-50 hover:bg-amber-100 transition-all border border-amber-100 group shadow-sm"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'gap-3 px-3 py-3'">
                            <i
                                class="fas fa-chevron-left w-5 text-center group-hover:-translate-x-1 transition-transform"></i>
                            <span x-show="!sidebarCollapsed">Volver a Empresas</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar Footer / User Info --}}
        <div class="p-4 bg-slate-50 border-t border-gray-100 relative" x-data="{ showProfileForm: false, timeout: null }">
            {{-- Profile Edit Tooltip --}}
            <div x-show="showProfileForm" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" @mouseenter="hoveringLabel = null; clearTimeout(timeout)"
                @mouseleave="showProfileForm = false" x-cloak
                class="absolute bottom-full mb-3 bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-slate-200 p-5 z-[100] w-80"
                :class="sidebarCollapsed ? 'left-20' : 'left-4'">
                {{-- Arrow --}}
                <div class="absolute -bottom-1.5 w-3 h-3 bg-white border-b border-r border-slate-200 transform rotate-45"
                    :class="sidebarCollapsed ? 'left-2' : 'left-8'">
                </div>

                <div class="flex items-center gap-3 mb-4 border-b border-slate-100 pb-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-xs font-black text-white shadow-lg shadow-blue-500/20 overflow-hidden">
                        @if (Auth::user()->profile_photo)
                            <img src="{{ Storage::url(Auth::user()->profile_photo) }}"
                                class="w-full h-full object-cover">
                        @else
                            {{ substr(Auth::user()->name, 0, 2) }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-black text-slate-800 truncate">{{ Auth::user()->name }}</h3>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest truncate">Ajustes del
                            Cuenta</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <a href="{{ route('profile.edit') }}"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black text-slate-600 hover:bg-slate-50 hover:text-blue-600 transition-all group/item">
                        <i
                            class="fas fa-user-gear text-slate-400 group-hover/item:text-blue-600 transition-colors"></i>
                        Editar Perfil
                    </a>

                    <form method="POST" action="{{ route('logout') }}" data-turbo="false">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black text-red-500 hover:bg-red-50 transition-all group/item">
                            <i class="fas fa-power-off opacity-70 group-hover/item:opacity-100"></i>
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>

            <div class="nav-item-link flex items-center group/user transition-all duration-300 rounded-xl hover:bg-white hover:shadow-sm cursor-pointer transition-all"
                :class="sidebarCollapsed ? 'justify-center p-2' : 'gap-3 px-2 py-2'"
                @mouseenter="if(sidebarCollapsed) { hoveringLabel = 'Mi Perfil'; tooltipTop = $el.getBoundingClientRect().top + 8 }; clearTimeout(timeout); showProfileForm = true"
                @mouseleave="hoveringLabel = null; timeout = setTimeout(() => { showProfileForm = false }, 300)">
                <div
                    class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-xs font-black text-white uppercase shadow-md shadow-blue-500/20 transition-transform group-hover/user:scale-105 overflow-hidden flex-shrink-0">
                    @if (Auth::user()->profile_photo)
                        <img src="{{ Storage::url(Auth::user()->profile_photo) }}"
                            class="w-full h-full object-cover">
                    @else
                        {{ substr(Auth::user()->name, 0, 2) }}
                    @endif
                </div>
                <div class="flex-1 min-w-0" x-show="!sidebarCollapsed">
                    <p
                        class="text-xs font-black text-slate-800 truncate group-hover/user:text-blue-600 transition-colors">
                        {{ Auth::user()->name }}</p>
                    <p class="text-[10px] font-bold text-slate-400 truncate tracking-tight">{{ Auth::user()->email }}
                    </p>
                </div>
                <form x-show="!sidebarCollapsed" method="POST" action="{{ route('logout') }}" data-turbo="false"
                    @mouseenter="clearTimeout(timeout)">
                    @csrf
                    <button type="submit"
                        class="text-slate-400 hover:text-red-500 transition-colors p-2 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-power-off text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Content Wrapper --}}
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        {{-- Mobile Header --}}
        <header
            class="lg:hidden h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sticky top-0 z-50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-black">R
                </div>
                <span class="font-black text-slate-900 tracking-tighter text-xl uppercase">Remsys</span>
            </div>
            <button class="p-2 text-slate-600"><i class="fas fa-bars text-xl"></i></button>
        </header>

        {{-- Company Header Banner (Restored Blue Gradient) --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-xl relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="h-full w-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                    <path d="M0 0 L100 100 L0 100 Z" fill="currentColor"></path>
                </svg>
            </div>
            <div class="max-w-7xl mx-auto px-6 sm:px-8 py-6 relative z-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <div
                            class="bg-white/20 p-3 rounded-xl backdrop-blur-md border border-white/10 shadow-xl hidden sm:block text-blue-100">
                            <i class="fas fa-building-shield text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="text-[9px] font-black uppercase tracking-[0.2em] text-blue-100 bg-white/10 px-2 py-0.5 rounded-full backdrop-blur-sm border border-white/5 italic">Gestión
                                    Corporativa</span>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tighter leading-tight">
                                {{ $company->razon_social ?? $company->name }}</h1>
                            <div class="flex items-center gap-4 mt-4 text-blue-100">
                                <div
                                    class="flex items-center gap-2 bg-black/10 px-3 py-1 rounded-lg border border-white/5">
                                    <span class="text-[10px] font-bold uppercase opacity-50 tracking-widest">RUT</span>
                                    <span
                                        class="font-mono font-black text-sm tracking-tight">{{ $company->rut }}</span>
                                </div>
                                <span class="opacity-30">|</span>
                                @if (isset($dailyUf))
                                    <div class="flex items-center gap-2 bg-black/10 px-3 py-1 rounded-lg border border-white/5"
                                        title="UF al {{ $dailyUf->date->format('d-m-Y') }}">
                                        <span
                                            class="text-[10px] font-bold uppercase opacity-50 tracking-widest">UF</span>
                                        <span class="font-mono font-black text-sm tracking-tight">
                                            ${{ number_format($dailyUf->value, 2, ',', '.') }}
                                        </span>
                                    </div>
                                    <span class="opacity-30">|</span>
                                @endif
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-circle-check text-[10px] text-green-400"></i>
                                    <span class="text-xs font-bold uppercase tracking-widest opacity-80">Empresa
                                        Activa</span>
                                </div>
                                @php
                                    $activePeriod = $company
                                        ->periods()
                                        ->whereIn('status', ['draft', 'open', 'calculated'])
                                        ->latest('year')
                                        ->latest('month')
                                        ->first();
                                @endphp
                                @if ($activePeriod)
                                    <span class="opacity-30">|</span>
                                    <a href="{{ route('companies.payroll-periods.wizard', ['company' => $company, 'period' => $activePeriod]) }}"
                                        class="flex items-center gap-2 bg-blue-400/20 hover:bg-blue-400/40 transition-colors px-3 py-1 rounded-lg border border-blue-300/30 cursor-pointer">
                                        <i class="fas fa-edit text-[10px] text-blue-200"></i>
                                        <span class="text-xs font-bold uppercase tracking-widest text-blue-50">
                                            Período Activo: {{ $activePeriod->getDisplayName() }}
                                        </span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Section --}}
        <main class="flex-1 p-6 sm:p-8 bg-slate-50/50 overflow-y-auto">
            <div class="max-w-7xl mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>


    <!-- Toast UI -->
    <div x-data x-show="$store.toast?.show" x-transition.opacity
        class="fixed top-6 right-6 z-[100] max-w-sm w-[90vw] sm:w-[420px]" style="display:none">
        <div class="rounded-lg shadow-lg px-4 py-3 text-sm flex items-start gap-3"
            :class="{
                'bg-green-50 text-green-800 border border-green-200': $store.toast.type==='success',
                'bg-red-50 text-red-800 border border-red-200': $store.toast.type==='error',
                'bg-blue-50 text-blue-800 border border-blue-200': $store.toast.type==='info'
            }"
            role="status" aria-live="polite">
            <div class="pt-0.5">
                <template x-if="$store.toast.type==='success'"><i class="fas fa-check-circle"></i></template>
                <template x-if="$store.toast.type==='error'"><i class="fas fa-circle-exclamation"></i></template>
                <template x-if="$store.toast.type==='info'"><i class="fas fa-circle-info"></i></template>
            </div>
            <div class="whitespace-pre-line" x-text="$store.toast.message"></div>
            <button class="ml-auto opacity-70 hover:opacity-100" @click="$store.toast.hide()">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Toast store + helper -->
    <script>
        (function() {
            function registerStore() {
                Alpine.store('toast', {
                    show: false,
                    message: '',
                    type: 'success',
                    _timer: null,
                    flash(message, type = 'success', timeout = 3200) {
                        this.message = message ?? ''
                        this.type = type
                        this.show = true
                        clearTimeout(this._timer)
                        this._timer = setTimeout(() => this.hide(), timeout)
                    },
                    hide() {
                        this.show = false
                        clearTimeout(this._timer)
                        this._timer = null
                    }
                })
            }
            if (window.Alpine) registerStore()
            else document.addEventListener('alpine:init', registerStore)

            // helper seguro (si el store no existe, hace console.log)
            window.toast = function(message, type = 'error', timeout = 7000) {
                try {
                    if (window.Alpine && Alpine.store('toast')) Alpine.store('toast').flash(message, type, timeout)
                    else console.log(`[${type}] ${message}`)
                } catch {
                    console.log(`[${type}] ${message}`)
                }
            }

            // Escuchar flashes de sesión de Laravel
            document.addEventListener('turbo:load', () => {
                @if (session('success'))
                    toast(@json(session('success')), 'success');
                @endif
                @if (session('status') === 'password-updated')
                    toast('Contraseña actualizada correctamente.', 'success');
                @endif
                @if (session('status') === 'profile-updated')
                    toast('Perfil actualizado correctamente.', 'success');
                @endif
                @if (session('error'))
                    toast(@json(session('error')), 'error');
                @endif
                @if ($errors->any())
                    toast(@json(implode("\n", $errors->all())), 'error');
                @endif
            });

            // Formateador de RUT global (Formato: xxxxxxxx-x)
            window.formatRut = function(value) {
                if (!value) return '';
                // Limpiar todo lo que no sea número o k/K
                let raw = value.replace(/[^0-9kK]/g, "");
                if (raw.length === 0) return "";
                if (raw.length === 1) return raw.toUpperCase();

                let dv = raw.slice(-1).toUpperCase();
                let body = raw.slice(0, -1);

                // Limitar cuerpo a 8 dígitos (máximo 99.999.999)
                if (body.length > 8) body = body.slice(0, 8);

                return body + "-" + dv;
            }
        })();
    </script>

    @stack('scripts')

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</body>

</html>
