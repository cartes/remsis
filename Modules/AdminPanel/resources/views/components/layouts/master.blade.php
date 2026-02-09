<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Panel de Administración')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="font-sans antialiased bg-slate-50 flex overflow-hidden">

    <div class="flex flex-1 h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
        {{-- Overlay for mobile --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:outline
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden" x-cloak></div>

        {{-- Sidebar Navigation --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed lg:static top-0 left-0 w-64 bg-white border-r border-gray-200 flex-shrink-0 flex flex-col h-screen z-50 transition-transform duration-300 ease-in-out">

            {{-- Sidebar Header / Logo --}}
            <div class="h-20 flex items-center px-6 border-b border-gray-100 flex-shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/20">
                        R
                    </div>
                    <span class="font-black text-slate-800 tracking-tighter text-2xl uppercase">Remsys</span>
                </a>
            </div>

            {{-- Sidebar Content --}}
            <div class="flex-1 overflow-y-auto py-6 px-4 space-y-8 no-scrollbar">
                {{-- Plataforma Section --}}
                <div>
                    <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Administración
                    </p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-50 group' }}">
                            <i
                                class="fas fa-chart-line transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500' }}"></i>
                            Dashboard General
                        </a>

                        @role('super-admin')
                            <a href="{{ route('companies.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all {{ request()->routeIs('companies.index') || request()->is('companies*') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-50 group' }}">
                                <i
                                    class="fas fa-building transition-colors {{ request()->routeIs('companies.index') || request()->is('companies*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500' }}"></i>
                                Listado Empresas
                            </a>
                        @endrole

                        @role('super-admin|admin|contador')
                            <a href="{{ route('admin.users.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all {{ request()->routeIs('users*') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-50 group' }}">
                                <i
                                    class="fas fa-users transition-colors {{ request()->routeIs('users*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500' }}"></i>
                                Gestión Usuarios
                            </a>
                        @endrole
                    </div>
                </div>

                {{-- Settings Section --}}
                @hasrole('super-admin')
                    <div>
                        <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Configuraciones
                        </p>
                        <div class="space-y-1">
                            <a href="{{ route('settings.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all {{ request()->routeIs('settings.index') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-50 group' }}">
                                <i
                                    class="fas fa-cogs transition-colors {{ request()->routeIs('settings.index') ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500' }}"></i>
                                Entidades Base
                            </a>
                            <a href="{{ route('settings.legal') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all {{ request()->routeIs('settings.legal') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-50 group' }}">
                                <i
                                    class="fas fa-balance-scale transition-colors {{ request()->routeIs('settings.legal') ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500' }}"></i>
                                Parámetros Legales
                            </a>
                            <a href="{{ route('settings.sii_codes') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all {{ request()->routeIs('settings.sii_codes') ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-50 group' }}">
                                <i
                                    class="fas fa-file-invoice transition-colors {{ request()->routeIs('settings.sii_codes') ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500' }}"></i>
                                Códigos SII
                            </a>
                        </div>
                    </div>
                @endhasrole
            </div>

            {{-- Sidebar Footer / User Info --}}
            <div class="p-4 bg-slate-50 border-t border-gray-100 flex-shrink-0 relative" x-data="{ showProfileForm: false, timeout: null }">
                {{-- Profile Edit Tooltip --}}
                <div x-show="showProfileForm" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95" @mouseenter="clearTimeout(timeout)"
                    @mouseleave="showProfileForm = false"
                    class="absolute bottom-full left-4 mb-3 bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-slate-200 p-4 z-[100] w-64"
                    style="display: none;">
                    {{-- Arrow --}}
                    <div
                        class="absolute -bottom-1.5 left-8 w-3 h-3 bg-white border-b border-r border-slate-200 transform rotate-45">
                    </div>

                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
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
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest truncate">
                                Configuración</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <a href="{{ route('profile.edit') }}"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black text-slate-600 hover:bg-slate-50 hover:text-blue-600 transition-all group/item">
                            <i
                                class="fas fa-user-gear text-slate-400 group-hover/item:text-blue-600 transition-colors"></i>
                            Editar Perfil
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black text-red-500 hover:bg-red-50 transition-all group/item">
                                <i class="fas fa-power-off opacity-70 group-hover/item:opacity-100"></i>
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex items-center gap-3 px-2 py-2 group/user transition-all duration-300 rounded-xl hover:bg-white hover:shadow-sm cursor-pointer"
                    @mouseenter="clearTimeout(timeout); showProfileForm = true"
                    @mouseleave="timeout = setTimeout(() => { showProfileForm = false }, 300)">
                    <div
                        class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-xs font-black text-white uppercase shadow-md shadow-blue-500/20 transition-transform group-hover/user:scale-105 overflow-hidden">
                        @if (Auth::user()->profile_photo)
                            <img src="{{ Storage::url(Auth::user()->profile_photo) }}"
                                class="w-full h-full object-cover">
                        @else
                            {{ substr(Auth::user()->name, 0, 2) }}
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p
                            class="text-xs font-black text-slate-800 truncate group-hover/user:text-blue-600 transition-colors">
                            {{ Auth::user()->name }}</p>
                        <p class="text-[10px] font-bold text-slate-400 truncate tracking-tight">
                            {{ Auth::user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" @mouseenter="clearTimeout(timeout)">
                        @csrf
                        <button type="submit"
                            class="text-slate-400 hover:text-red-500 transition-colors p-2 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-power-off text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content Section --}}
        <main class="flex-1 min-w-0 flex flex-col h-screen overflow-y-auto">
            {{-- Header (Top Bar) --}}
            <header
                class="h-20 bg-white border-b border-gray-100 px-6 sm:px-10 flex items-center justify-between sticky top-0 z-30 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true"
                        class="lg:hidden p-2 text-slate-600 hover:bg-slate-50 rounded-lg">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <div class="text-[10px] font-black uppercase text-slate-400 mb-0.5 tracking-widest">
                            {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                        </div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight">@yield('title')</h2>
                    </div>
                </div>

                {{-- Right actions (Notifications, profile, etc.) --}}
                <div class="flex items-center gap-4">
                    @php
                        $notifications = \Modules\AdminPanel\Models\Notification::upcoming();
                        $unreadCount = $notifications->count();
                    @endphp

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                            class="relative p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-bell text-xl"></i>
                            @if ($unreadCount > 0)
                                <span
                                    class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </button>

                        {{-- Notification Dropdown --}}
                        <div x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
                            style="display: none;">

                            {{-- Header --}}
                            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-gray-800">Notificaciones</h3>
                                    @if ($unreadCount > 0)
                                        <span
                                            class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full font-semibold">
                                            {{ $unreadCount }} pendientes
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Notifications List --}}
                            <div class="max-h-96 overflow-y-auto">
                                @forelse($notifications as $notification)
                                    <div
                                        class="px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100 {{ $notification->urgent ?? false ? 'bg-red-50' : '' }}">
                                        <div class="flex items-start gap-3">
                                            <div class="flex-shrink-0 mt-1">
                                                @if ($notification->type === 'warning')
                                                    <i class="fas fa-exclamation-triangle text-orange-500"></i>
                                                @elseif($notification->type === 'info')
                                                    <i class="fas fa-info-circle text-blue-500"></i>
                                                @else
                                                    <i class="fas fa-bell text-gray-500"></i>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    {{ $notification->title }}
                                                </p>
                                                @if ($notification->message)
                                                    <p class="text-xs text-gray-600 mt-1">{{ $notification->message }}
                                                    </p>
                                                @endif
                                                <div class="flex items-center gap-2 mt-2">
                                                    <span class="text-xs text-gray-500 flex items-center gap-1">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        {{ $notification->due_date->locale('es')->isoFormat('D [de] MMMM') }}
                                                    </span>
                                                    @if ($notification->urgent ?? false)
                                                        <span
                                                            class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-semibold">
                                                            Urgente
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                        <p class="text-sm">No hay notificaciones pendientes</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Footer --}}
                            @if ($unreadCount > 0)
                                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                                    <button
                                        class="text-xs text-blue-600 hover:text-blue-800 font-semibold w-full text-center">
                                        Ver todas las notificaciones
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex-1 p-6 sm:p-10">
                @yield('content')
            </div>
        </main>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <!-- Toast UI -->
    <div x-data x-show="$store.toast?.show" x-transition.opacity x-cloak
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
            window.addEventListener('DOMContentLoaded', () => {
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
        })();
    </script>

    @stack('scripts')
</body>

</html>
