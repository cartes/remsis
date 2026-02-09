<section>
    <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="bg-red-600 hover:bg-black text-white px-8 py-3 rounded-xl text-sm font-black shadow-lg shadow-red-500/20 transition-all active:scale-95 flex items-center gap-2">
        <i class="fas fa-user-minus opacity-50"></i>
        {{ __('Eliminar Cuenta definitivamente') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-8">
            @csrf
            @method('delete')

            <h2 class="text-2xl font-black text-slate-800 tracking-tighter mb-2">
                ¿Confirmas la eliminación?
            </h2>

            <p class="text-sm font-medium text-slate-500 mb-6 leading-relaxed">
                Esta acción es irreversible. Se borrarán todos tus datos y accesos de forma permanente.
            </p>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">Confirma con tu
                    clave</label>
                <input type="password" name="password" placeholder="Ingresa tu contraseña actual"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all outline-none">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')"
                    class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black transition-all">
                    Cancelar
                </button>

                <button type="submit"
                    class="px-8 py-3 bg-red-600 hover:bg-black text-white rounded-xl text-xs font-black shadow-lg shadow-red-500/20 transition-all">
                    Si, Eliminar Cuenta
                </button>
            </div>
        </form>
    </x-modal>
</section>
