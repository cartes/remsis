<section>
    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block px-1">Contraseña
                Actual</label>
            <input type="password" name="current_password" autocomplete="current-password"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block px-1">Nueva
                Contraseña</label>
            <input type="password" name="password" autocomplete="new-password"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block px-1">Confirmar
                Nueva Contraseña</label>
            <input type="password" name="password_confirmation" autocomplete="new-password"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit"
                class="bg-indigo-600 hover:bg-black text-white px-8 py-3.5 rounded-xl text-sm font-black shadow-lg shadow-indigo-500/20 transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-key opacity-50"></i>
                Actualizar Contraseña
            </button>
        </div>
    </form>
</section>
