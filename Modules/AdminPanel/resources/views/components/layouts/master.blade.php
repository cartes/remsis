<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Panel de Administración')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-gray-100 text-gray-800">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-4 font-bold text-xl border-b">Remsis</div>
            <nav class="mt-4">
                <ul class="space-y-2 p-2">
                    @role('super-admin|admin|contador')
                        <li>
                            <a href="{{ route('users.index') }}"
                                class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                                <i class="fas fa-users mr-2"></i> Usuarios
                            </a>
                        </li>
                    @endrole
                    @role('super-admin')
                        <li>
                            <a href="{{ route('companies.index') }}"
                                class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                                <i class="fas fa-building mr-2 text-sm text-gray-500"></i>
                                Empresas
                            </a>
                        </li>
                    @endrole
                    </li>
                    @hasrole('super-admin')
                        <li x-data="{ open: false }" class="mt-4">
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                                <span><i class="fas fa-sliders-h mr-2"></i>Configuraciones</span>
                                <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                            </button>

                            <ul x-show="open" x-transition class="pl-4 mt-2 space-y-1">
                                <li>
                                    <a href="{{ route('settings.index') }}"
                                        class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                                        <i class="fas fa-cogs mr-2 text-sm"></i> Entidades Base
                                    </a>
                                </li>
                                {{-- Puedes agregar más subítems aquí si luego quieres separar AFP, Isapres, etc. --}}
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
            <h1 class="text-2xl font-semibold mb-4">@yield('title')</h1>
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

            // Si Alpine ya está cargado, registra de inmediato; si no, espera el evento.
            if (window.Alpine) {
                registerStore()
            } else {
                document.addEventListener('alpine:init', registerStore)
            }

            // Helper que nunca revienta si aún no hay store
            window.toast = function(message, type = 'success', timeout = 3200) {
                try {
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').flash(message, type, timeout)
                    } else {
                        console.log(`[${type}] ${message}`)
                    }
                } catch (e) {
                    console.log(`[${type}] ${message}`)
                }
            }
        })();
    </script>

    @stack('scripts')
</body>

</html>
