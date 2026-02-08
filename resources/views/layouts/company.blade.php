<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $company->razon_social ?? $company->name }} - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="font-sans antialiased bg-slate-50 flex overflow-hidden">
    {{-- Sidebar Navigation --}}
    <aside
        class="w-68 bg-white border-r border-gray-200 flex-shrink-0 hidden lg:flex flex-col h-screen sticky top-0 z-50">
        {{-- Sidebar Header / Logo --}}
        <div class="h-20 flex items-center px-6 border-b border-gray-100">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div
                    class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/20">
                    R
                </div>
                <span class="font-black text-slate-800 tracking-tighter text-2xl uppercase">Remsys</span>
            </a>
        </div>

        {{-- Sidebar Content --}}
        <div class="flex-1 overflow-y-auto py-6 px-4 space-y-8 no-scrollbar">
            {{-- App Section --}}
            <div>
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Plataforma</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all hover:bg-slate-50 text-slate-600 hover:text-blue-600 group">
                        <i class="fas fa-chart-line text-slate-400 group-hover:text-blue-500 transition-colors"></i>
                        Dashboard
                    </a>
                </div>
            </div>

            {{-- Company Section --}}
            <div>
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Gestión de Empresa
                </p>
                <div class="space-y-1" x-data="{ activeTab: '{{ $activeTab ?? 'employees' }}' }">
                    <a href="{{ route('companies.edit', $company) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all"
                        :class="activeTab === 'accounting' ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:bg-slate-50 group'">
                        <i class="fas fa-calculator transition-colors"
                            :class="activeTab === 'accounting' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'"></i>
                        Contabilidad
                    </a>

                    <a href="{{ route('companies.employees', $company) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all"
                        :class="activeTab === 'employees' ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:bg-slate-50 group'">
                        <i class="fas fa-users-viewfinder transition-colors"
                            :class="activeTab === 'employees' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'"></i>
                        Nómina de Empleados
                    </a>

                    <a href="{{ route('companies.transactions', $company) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all"
                        :class="activeTab === 'transactions' ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:bg-slate-50 group'">
                        <i class="fas fa-money-bill-transfer transition-colors"
                            :class="activeTab === 'transactions' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'"></i>
                        Movimientos
                    </a>

                    <a href="{{ route('companies.cost-centers', $company) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all"
                        :class="activeTab === 'cost-centers' ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' :
                            'text-slate-600 hover:bg-slate-50 group'">
                        <i class="fas fa-folder-tree transition-colors"
                            :class="activeTab === 'cost-centers' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'"></i>
                        Centros de Costo
                    </a>
                </div>
            </div>

            {{-- Actions Section --}}
            <div class="pt-4 border-t border-slate-100">
                <div class="space-y-1">
                    <a href="{{ route('companies.index') }}"
                        class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-bold text-amber-600 bg-amber-50 hover:bg-amber-100 transition-all border border-amber-100 group shadow-sm">
                        <i class="fas fa-chevron-left group-hover:-translate-x-1 transition-transform"></i>
                        Volver a Empresas
                    </a>
                </div>
            </div>
        </div>

        {{-- Sidebar Footer / User Info --}}
        <div class="p-4 bg-slate-50 border-t border-gray-100">
            <div class="flex items-center gap-3 px-2 py-2">
                <div
                    class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-xs font-black text-white uppercase shadow-md shadow-blue-500/20">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-slate-800 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] font-bold text-slate-400 truncate tracking-tight">{{ Auth::user()->email }}
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
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
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-circle-check text-[10px] text-green-400"></i>
                                    <span class="text-xs font-bold uppercase tracking-widest opacity-80">Empresa
                                        Activa</span>
                                </div>
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
            window.addEventListener('DOMContentLoaded', () => {
                @if (session('success'))
                    toast(@json(session('success')), 'success');
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
