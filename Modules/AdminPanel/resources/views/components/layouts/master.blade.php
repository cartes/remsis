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

<body class="bg-gray-100 text-gray-800">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-white shadow-md flex flex-col h-screen sticky top-0 overflow-y-auto">
            <div class="p-4 font-bold text-xl border-b flex-shrink-0">Remsis</div>
            <nav class="mt-4 flex-1">
                <ul class="space-y-2 p-2 pb-10">
                    @role('super-admin|admin|contador')
                        <li>
                            <a href="{{ route('users.index') }}"
                                class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold uppercase text-xs {{ request()->routeIs('users*') ? 'text-blue-600 bg-blue-50' : 'text-gray-500' }}">
                                <i class="fas fa-users mr-2"></i> Usuarios
                            </a>
                        </li>
                    @endrole
                    @role('super-admin')
                        <li x-data="{ open: {{ request()->routeIs('companies*') || request()->is('payrolls*') ? 'true' : 'false' }} }">
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between p-2 rounded hover:bg-gray-200 font-bold uppercase text-xs {{ request()->routeIs('companies*') || request()->is('payrolls*') ? 'text-blue-600 bg-blue-50' : 'text-gray-500' }}">
                                <span><i class="fas fa-building mr-2 text-sm"></i> Empresas</span>
                                <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                            </button>
                            <ul x-show="open" x-transition class="pl-4 mt-2 space-y-1" x-cloak>
                                <li>
                                    <a href="{{ route('companies.index') }}"
                                        class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold uppercase text-xs {{ request()->routeIs('companies.index') || (request()->routeIs('companies.edit') && !request()->is('payrolls*')) ? 'text-blue-600 bg-blue-50' : 'text-gray-500' }}">
                                        <i class="fas fa-list mr-2 text-sm"></i> Listado
                                    </a>
                            </ul>
                        </li>
                    @endrole
                    @hasrole('super-admin')
                        <li x-data="{ open: {{ request()->routeIs('settings.*') ? 'true' : 'false' }} }" class="mt-4">
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between p-2 rounded hover:bg-gray-200 font-bold uppercase text-xs {{ request()->routeIs('settings.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-500' }}">
                                <span><i class="fas fa-sliders-h mr-2"></i>Configuraciones</span>
                                <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                            </button>

                            <ul x-show="open" x-transition class="pl-4 mt-2 space-y-1">
                                <li>
                                    <a href="{{ route('settings.index') }}"
                                        class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold uppercase text-xs {{ request()->routeIs('settings.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-500' }}">
                                        <i class="fas fa-cogs mr-2 text-sm"></i> Entidades Base
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.legal') }}"
                                        class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold uppercase text-xs {{ request()->routeIs('settings.legal') ? 'text-blue-600 bg-blue-50' : 'text-gray-500' }}">
                                        <i class="fas fa-balance-scale mr-2 text-sm"></i> Parámetros Legales
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.sii_codes') }}"
                                        class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold uppercase text-xs {{ request()->routeIs('settings.sii_codes') ? 'text-blue-600 bg-blue-50' : 'text-gray-500' }}">
                                        <i class="fas fa-file-invoice mr-2 text-sm"></i> Códigos SII
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endhasrole
                </ul>
            </nav>
            <div class="mt-auto px-4 py-4 border-t border-gray-200">
                <div class="text-sm text-gray-700 mb-2">
                    {{ Auth::user()->name }}
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-sm text-red-600 hover:text-red-800 font-medium flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Salir</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Contenido principal --}}
        <main class="flex-1 p-6">
            {{-- Header with Date and Notifications --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    {{-- Current Date --}}
                    <div class="text-sm text-gray-500 mb-1 flex items-center gap-2">
                        <i class="fas fa-calendar-day"></i>
                        <span>{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</span>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-800">@yield('title')</h1>
                </div>

                {{-- Notification Bell --}}
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
                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
                        style="display: none;">

                        {{-- Header --}}
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-gray-800">Notificaciones</h3>
                                @if ($unreadCount > 0)
                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full font-semibold">
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
                                            <p class="text-sm font-semibold text-gray-800">{{ $notification->title }}
                                            </p>
                                            @if ($notification->message)
                                                <p class="text-xs text-gray-600 mt-1">{{ $notification->message }}</p>
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

            @yield('content')
        </main>
    </div>

    <!-- Toast UI -->
    <div x-data x-show="$store.toast?.show" x-transition.opacity
        class="fixed top-6 right-6 z-50 max-w-sm w-[90vw] sm:w-[420px]" style="display:none">
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
        })();
    </script>

    @stack('scripts')
</body>

</html>
