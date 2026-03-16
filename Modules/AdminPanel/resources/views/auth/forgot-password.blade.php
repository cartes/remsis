<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-black text-slate-800 tracking-tight font-outfit">¿Olvidaste tu clave?</h2>
        <p class="text-slate-500 text-sm mt-2 font-medium">No te preocupes. Ingresa tu correo y te enviaremos un link para restablecerla.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
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
                       placeholder="ejemplo@remsis.cl"
                       class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white transition-all outline-none">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-300 group-focus-within:text-indigo-500 transition-colors">
                    <i class="fas fa-paper-plane"></i>
                </div>
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-[11px] font-bold uppercase tracking-tight" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-black py-4 px-6 rounded-2xl shadow-lg shadow-indigo-100 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl active:scale-95 flex items-center justify-center gap-3 uppercase tracking-widest text-xs">
                <span>Enviar Link de Recuperación</span>
            </button>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('admin.login') }}" class="text-[10px] font-black text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Volver al Inicio
            </a>
        </div>
    </form>
</x-guest-layout>
