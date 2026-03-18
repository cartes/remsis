<x-guest-layout>
    <div class="mb-10 text-center">
        <h2 class="text-2xl font-black text-slate-800 tracking-tight font-outfit">¡Bienvenido de nuevo!</h2>
        <p class="text-slate-500 text-sm mt-2 font-medium">Ingresa tus credenciales para acceder</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div class="space-y-2">
            <label for="email" class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2 px-1">
                <i class="fas fa-envelope text-[10px] opacity-70"></i>
                Correo Electrónico
            </label>
            <div class="relative group">
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus 
                       autocomplete="username"
                       placeholder="ejemplo@remsis.cl"
                       class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white transition-all outline-none">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-300 group-focus-within:text-indigo-500 transition-colors">
                    <i class="fas fa-id-card"></i>
                </div>
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-[11px] font-bold uppercase tracking-tight" />
        </div>

        <!-- Password -->
        <div class="space-y-2">
            <div class="flex justify-between items-center px-1">
                <label for="password" class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-lock text-[10px] opacity-70"></i>
                    Contraseña
                </label>
                @if (Route::has('password.request'))
                    <a class="text-[10px] font-black text-indigo-600 hover:text-indigo-700 uppercase tracking-widest transition-colors"
                        href="{{ route('password.request') }}">
                        ¿Olvidaste tu clave?
                    </a>
                @endif
            </div>

            <div class="relative group">
                <input id="password" 
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white transition-all outline-none">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-300 group-focus-within:text-indigo-500 transition-colors">
                    <i class="fas fa-key"></i>
                </div>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-[11px] font-bold uppercase tracking-tight" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center px-1">
            <label for="remember_me" class="relative flex items-center cursor-pointer group">
                <div class="relative">
                    <input id="remember_me" type="checkbox" name="remember" class="sr-only peer">
                    <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-indigo-600 transition-all duration-300"></div>
                    <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-5"></div>
                </div>
                <span class="ms-3 text-xs font-bold text-slate-500 uppercase tracking-wider group-hover:text-slate-700 transition-colors">Recordarme</span>
            </label>
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-black py-4 px-6 rounded-2xl shadow-lg shadow-indigo-100 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl active:scale-95 flex items-center justify-center gap-3 uppercase tracking-widest text-xs">
                <span>Ingresar al Panel</span>
                <i class="fas fa-arrow-right-long transition-transform group-hover:translate-x-1"></i>
            </button>
        </div>
    </form>
</x-guest-layout>
