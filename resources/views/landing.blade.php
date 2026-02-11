<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>REMSYS - Gestión de Remuneraciones para Chile</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-brand-dark bg-white">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-40" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex-shrink-0">
                    <span class="font-bold text-2xl tracking-tight text-brand-dark">REMSYS</span>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="#features"
                        class="text-base font-medium text-gray-600 hover:text-brand-dark transition-colors">Características</a>
                    <a href="#pricing"
                        class="text-base font-medium text-gray-600 hover:text-brand-dark transition-colors">Precios</a>
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center px-6 py-3 text-base font-medium rounded-button text-white bg-brand-blue hover:bg-blue-600 transition-all duration-150">
                            Dashboard
                        </a>
                    @else
                        <button onclick="openLoginModal()"
                            class="text-base font-medium text-brand-dark hover:text-brand-blue transition-colors">Iniciar
                            Sesión</button>
                        <button onclick="openRegisterModal()"
                            class="inline-flex items-center px-6 py-3 text-base font-medium rounded-button text-white bg-brand-blue hover:bg-blue-600 transition-all duration-150">
                            Comenzar Gratis
                        </button>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="text-gray-600 hover:text-brand-dark focus:outline-none">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Portal -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="md:hidden bg-white border-b border-gray-100 px-6 py-8 space-y-6 shadow-xl" x-cloak>
            <a href="#features" @click="mobileMenuOpen = false"
                class="block text-lg font-bold text-gray-600">Características</a>
            <a href="#pricing" @click="mobileMenuOpen = false" class="block text-lg font-bold text-gray-600">Precios</a>
            <div class="pt-4 border-t border-gray-100 flex flex-col gap-4">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="w-full text-center px-6 py-4 text-lg font-bold rounded-button text-white bg-brand-blue">
                        Dashboard
                    </a>
                @else
                    <button onclick="openLoginModal()" @click="mobileMenuOpen = false"
                        class="w-full text-center px-6 py-4 text-lg font-bold text-brand-dark border border-gray-200 rounded-button">
                        Iniciar Sesión
                    </button>
                    <button onclick="openRegisterModal()" @click="mobileMenuOpen = false"
                        class="w-full text-center px-6 py-4 text-lg font-bold rounded-button text-white bg-brand-blue">
                        Comenzar Gratis
                    </button>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section - 50/50 Layout (Text LEFT, Image RIGHT) -->
    <div class="relative py-24 lg:py-32 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                <!-- LEFT: Text Content -->
                <div>
                    <p class="text-sm font-semibold text-brand-blue uppercase tracking-wide mb-6">REMSYS</p>
                    <h1 class="text-5xl sm:text-7xl font-bold tracking-tight text-brand-dark mb-6 leading-tight">
                        Gestión de remuneraciones, simple y al día.
                    </h1>
                    <p class="text-xl text-gray-600 mb-10 leading-relaxed">
                        Cumple con la legislación chilena (DT, Previred) sin complicaciones. Automatiza sueldos,
                        liquidaciones y contratos en minutos.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                        <button onclick="openRegisterModal()"
                            class="inline-flex justify-center items-center px-8 py-4 text-lg font-semibold rounded-button text-white bg-brand-blue hover:bg-blue-600 transition-all duration-150">
                            Comenzar Gratis
                        </button>
                        <a href="#demo"
                            class="inline-flex justify-center items-center px-8 py-4 text-lg font-semibold text-brand-blue hover:text-blue-600 transition-colors group">
                            Ver Demo
                            <svg class="ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                    <p class="mt-6 text-sm text-gray-600">
                        * No requiere tarjeta de crédito para empezar.
                    </p>
                </div>

                <!-- RIGHT: Hero Image -->
                <div class="relative">
                    <div class="rounded-3xl overflow-hidden shadow-2xl">
                        <img src="/images/hero-dashboard.png" alt="REMSYS Dashboard - Liquidación de Sueldo"
                            class="w-full h-auto" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Grid - 3 Columns with Product Screenshots -->
    <section id="features" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-4xl sm:text-5xl font-bold tracking-tight text-brand-dark mb-6">
                    Todo lo que necesitas para tu empresa
                </h2>
                <p class="text-xl text-gray-600">
                    Olvídate de las planillas de cálculo complejas. REMSYS centraliza toda la información laboral en un
                    solo lugar seguro.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 - Calculation (placeholder for missing image) -->
                <div
                    class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-150">
                    <div
                        class="aspect-[4/3] bg-gradient-to-br from-blue-50 to-white flex items-center justify-center p-8">
                        <div class="text-center">
                            <div
                                class="w-20 h-20 bg-brand-blue rounded-2xl flex items-center justify-center mx-auto mb-4 text-white">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500">Vista previa de cálculo automático</p>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-brand-dark mb-4">Cálculo Automático</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">
                            Calcula sueldos, gratificaciones, horas extras y bonos al instante. Olvídate de los errores
                            manuales en las liquidaciones.
                        </p>
                        <a href="#"
                            class="inline-flex items-center text-brand-blue font-semibold hover:text-blue-600 transition-colors group">
                            Más información
                            <svg class="ml-2 w-4 h-4 transition-transform group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Feature 2 - Legal Compliance -->
                <div
                    class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-150">
                    <div class="aspect-[4/3] bg-white">
                        <img src="/images/feature-legal.png" alt="100% Legal - DT & SII"
                            class="w-full h-full object-cover" />
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-brand-dark mb-4">100% Legal (DT & SII)</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">
                            Siempre actualizado con la normativa laboral chilena. Genera contratos, anexos y finiquitos
                            cumpliendo todas las leyes.
                        </p>
                        <a href="#"
                            class="inline-flex items-center text-brand-blue font-semibold hover:text-blue-600 transition-colors group">
                            Más información
                            <svg class="ml-2 w-4 h-4 transition-transform group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Feature 3 - Previred Integration -->
                <div
                    class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-150">
                    <div class="aspect-[4/3] bg-white">
                        <img src="/images/feature-previred.png" alt="Integración Previred"
                            class="w-full h-full object-cover" />
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-brand-dark mb-4">Integración Previred</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">
                            Genera el archivo de carga para Previred con un solo clic. Ahorra horas de trabajo tedioso
                            cada mes y evita multas.
                        </p>
                        <a href="#"
                            class="inline-flex items-center text-brand-blue font-semibold hover:text-blue-600 transition-colors group">
                            Más información
                            <svg class="ml-2 w-4 h-4 transition-transform group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 bg-white">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-4xl sm:text-5xl font-bold tracking-tight text-brand-dark mb-6">
                Precios transparentes
            </h2>
            <p class="text-xl text-gray-600 mb-12">
                Sin sorpresas. Paga solo por lo que usas.
            </p>
            <div class="bg-gray-50 rounded-3xl p-8 sm:p-12 border border-gray-100">
                <div class="text-5xl sm:text-6xl font-bold text-brand-dark mb-4">$5.000 <span
                        class="text-2xl font-normal text-gray-600">CLP</span></div>
                <p class="text-xl text-gray-600 mb-8">por empleado/mes</p>
                <button onclick="openRegisterModal()"
                    class="inline-flex items-center px-8 py-4 text-lg font-semibold rounded-button text-white bg-brand-blue hover:bg-blue-600 transition-all duration-150">
                    Comenzar Gratis
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white py-16">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-2 md:col-span-1">
                    <span class="font-bold text-3xl tracking-tight text-white">REMSYS</span>
                    <p class="mt-6 text-gray-400 text-base leading-relaxed">
                        Software de remuneraciones simplificado para Chile.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider text-gray-500 mb-6">Producto</h4>
                    <ul class="space-y-4">
                        <li><a href="#"
                                class="text-gray-300 hover:text-white transition-colors text-base">Características</a>
                        </li>
                        <li><a href="#"
                                class="text-gray-300 hover:text-white transition-colors text-base">Precios</a></li>
                        <li><a href="#"
                                class="text-gray-300 hover:text-white transition-colors text-base">Seguridad</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider text-gray-500 mb-6">Recursos</h4>
                    <ul class="space-y-4">
                        <li><a href="#"
                                class="text-gray-300 hover:text-white transition-colors text-base">Blog</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors text-base">Guía
                                Legal</a></li>
                        <li><a href="#"
                                class="text-gray-300 hover:text-white transition-colors text-base">Soporte</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider text-gray-500 mb-6">Legal</h4>
                    <ul class="space-y-4">
                        <li><a href="#"
                                class="text-gray-300 hover:text-white transition-colors text-base">Privacidad</a></li>
                        <li><a href="#"
                                class="text-gray-300 hover:text-white transition-colors text-base">Términos</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} REMSYS. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <!-- Login Modal (Alpine.js) -->
    <div x-data="{
        open: false,
        loading: false,
        errors: {},
        formData: {
            email: '{{ old('email') }}',
            password: '',
            remember: false
        },
        async login() {
            this.loading = true;
            this.errors = {};
    
            try {
                const response = await fetch('{{ route('login') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });
    
                const data = await response.json();
    
                if (response.ok) {
                    window.location.href = data.redirect;
                } else {
                    this.errors = data.errors || { email: [data.message || 'Error al iniciar sesión'] };
                    this.loading = false;
                }
            } catch (error) {
                this.errors = { email: ['Error de conexión. Intente nuevamente.'] };
                this.loading = false;
            }
        }
    }" @open-login-modal.window="open = true" @open-register-modal.window="open = false"
        @keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="open = false"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl shadow-xl w-full max-w-[calc(100vw-2rem)] sm:max-w-md p-6 sm:p-8"
                @click.away="open = false">
                <!-- Close button -->
                <button @click="open = false" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <h2 class="text-3xl font-bold text-brand-dark mb-2">Iniciar Sesión</h2>
                <p class="text-gray-600 mb-8">Accede a tu cuenta REMSYS</p>

                <form @submit.prevent="login()">
                    <div class="space-y-6">
                        <div>
                            <label for="email"
                                class="block text-sm font-semibold text-brand-dark mb-2">Email</label>
                            <input type="email" id="email" x-model="formData.email" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-blue focus:border-transparent"
                                :class="{ 'border-red-500': errors.email }">
                            <template x-if="errors.email">
                                <p class="mt-2 text-sm text-red-600" x-text="errors.email[0]"></p>
                            </template>
                        </div>
                        <div>
                            <label for="password"
                                class="block text-sm font-semibold text-brand-dark mb-2">Contraseña</label>
                            <input type="password" id="password" x-model="formData.password" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-blue focus:border-transparent"
                                :class="{ 'border-red-500': errors.password }">
                            <template x-if="errors.password">
                                <p class="mt-2 text-sm text-red-600" x-text="errors.password[0]"></p>
                            </template>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" x-model="formData.remember"
                                class="w-4 h-4 text-brand-blue border-gray-300 rounded focus:ring-brand-blue">
                            <label for="remember" class="ml-2 text-sm text-gray-600">Recuérdame</label>
                        </div>
                        <button type="submit" :disabled="loading"
                            class="w-full px-6 py-4 text-lg font-semibold rounded-button text-white bg-brand-blue hover:bg-blue-600 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="loading ? 'Iniciando...' : 'Iniciar Sesión'"></span>
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    ¿No tienes cuenta? <button @click="open = false; $dispatch('open-register-modal')"
                        class="text-brand-blue font-semibold hover:text-blue-600">Regístrate gratis</button>
                </p>
            </div>
        </div>
    </div>

    <!-- Register Modal (Alpine.js) -->
    <div x-data="{ open: false }" x-init="if ({{ $errors->has('name') || ($errors->has('email') && !old('password')) || $errors->has('password') ? 'true' : 'false' }}) open = true" x-show="open" @open-register-modal.window="open = true"
        @open-login-modal.window="open = false" @keydown.escape.window="open = false" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="open = false"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl shadow-xl w-full max-w-[calc(100vw-2rem)] sm:max-w-md p-6 sm:p-8"
                @click.away="open = false">
                <!-- Close button -->
                <button @click="open = false" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <h2 class="text-3xl font-bold text-brand-dark mb-2">Crear Cuenta</h2>
                <p class="text-gray-600 mb-8">Comienza a gestionar tus remuneraciones</p>

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-brand-dark mb-2">Nombre
                                Completo</label>
                            <input type="text" id="name" name="name" :value="old('name')" required
                                autofocus
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <label for="email_reg"
                                class="block text-sm font-semibold text-brand-dark mb-2">Email</label>
                            <input type="email" id="email_reg" name="email" :value="old('email')" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <label for="password_reg"
                                class="block text-sm font-semibold text-brand-dark mb-2">Contraseña</label>
                            <input type="password" id="password_reg" name="password" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <label for="password_confirmation"
                                class="block text-sm font-semibold text-brand-dark mb-2">Confirmar Contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                        </div>
                        <button type="submit"
                            class="w-full px-6 py-4 text-lg font-semibold rounded-button text-white bg-brand-blue hover:bg-blue-600 transition-all duration-150">
                            Comenzar Ahora
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    ¿Ya tienes cuenta? <button @click="open = false; $dispatch('open-login-modal')"
                        class="text-brand-blue font-semibold hover:text-blue-600">Inicia sesión</button>
                </p>
            </div>
        </div>
    </div>

    <script>
        function openLoginModal() {
            window.dispatchEvent(new CustomEvent('open-login-modal'));
        }

        function openRegisterModal() {
            window.dispatchEvent(new CustomEvent('open-register-modal'));
        }
    </script>
</body>

</html>
