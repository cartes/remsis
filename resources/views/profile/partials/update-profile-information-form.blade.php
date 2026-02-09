<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="flex items-center gap-6 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm mb-8">
            <div class="relative group">
                <div
                    class="w-16 h-16 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 overflow-hidden shadow-inner">
                    @if ($user->profile_photo)
                        <img src="{{ Storage::url($user->profile_photo) }}" class="w-full h-full object-cover">
                    @else
                        <i class="fas fa-camera text-xl opacity-30"></i>
                    @endif
                </div>
            </div>
            <div class="flex-1">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Actualizar
                    Avatar</label>
                <input id="profile_photo" name="profile_photo" type="file"
                    class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer" />
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block px-1">Nombre
                    Completo</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block px-1">Correo
                    Electrónico</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                    <div class="mt-4 p-3 bg-amber-50 rounded-xl border border-amber-100">
                        <p
                            class="text-[11px] font-bold text-amber-800 flex items-center gap-2 uppercase tracking-tight">
                            <i class="fas fa-exclamation-circle"></i>
                            Correo no verificado
                            <button form="send-verification" class="ml-auto underline hover:text-amber-950">
                                Enviar enlace
                            </button>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit"
                class="bg-indigo-600 hover:bg-black text-white px-8 py-3.5 rounded-xl text-sm font-black shadow-lg shadow-indigo-500/20 transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-save opacity-50"></i>
                Guardar Perfil
            </button>
        </div>
    </form>
</section>
