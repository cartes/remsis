<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <span class="font-bold text-2xl tracking-tight text-brand-dark">REMSYS</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6" x-data="{ showProfileForm: false, timeout: null }">
                <div class="relative">
                    {{-- Profile Edit Tooltip --}}
                    <div x-show="showProfileForm" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        @mouseenter="clearTimeout(timeout)" @mouseleave="showProfileForm = false"
                        class="absolute top-full right-0 mt-3 bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-slate-200 p-4 z-[100] w-64"
                        style="display: none;">
                        {{-- Arrow --}}
                        <div
                            class="absolute -top-1.5 right-12 w-3 h-3 bg-white border-t border-l border-slate-200 transform rotate-45">
                        </div>

                        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
                            <div
                                class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-sm font-black text-white shadow-xl shadow-blue-500/20 overflow-hidden">
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

                    <button @mouseenter="clearTimeout(timeout); showProfileForm = true"
                        @mouseleave="timeout = setTimeout(() => { showProfileForm = false }, 300)"
                        class="inline-flex items-center gap-3 px-4 py-2 border border-transparent text-sm leading-4 font-black rounded-xl text-slate-600 bg-white hover:text-blue-600 focus:outline-none transition ease-in-out duration-150 group">
                        <div class="flex flex-col items-end">
                            <span class="text-xs">{{ Auth::user()->name }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tight">Mi Perfil</span>
                        </div>

                        <div
                            class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-black group-hover:bg-blue-600 group-hover:text-white transition-all overflow-hidden shadow-sm">
                            @if (Auth::user()->profile_photo)
                                <img src="{{ Storage::url(Auth::user()->profile_photo) }}"
                                    class="w-full h-full object-cover">
                            @else
                                {{ substr(Auth::user()->name, 0, 2) }}
                            @endif
                        </div>
                    </button>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Editar Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
