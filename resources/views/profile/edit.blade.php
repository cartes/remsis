<x-adminpanel::layouts.master>
    @section('title', 'Mi Perfil de Usuario')

    @section('content')
        <div class="max-w-5xl mx-auto space-y-10">
            {{-- Header Section --}}
            <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-48 h-48 bg-black/10 rounded-full blur-2xl"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center gap-8 text-center md:text-left">
                    <div class="relative group">
                        <div
                            class="w-32 h-32 rounded-3xl bg-white/20 backdrop-blur-md flex items-center justify-center border-2 border-white/30 overflow-hidden shadow-2xl transition-transform duration-500 group-hover:scale-105">
                            @if (Auth::user()->profile_photo)
                                <img src="{{ Storage::url(Auth::user()->profile_photo) }}" class="w-full h-full object-cover">
                            @else
                                <span
                                    class="text-4xl font-black text-white italic">{{ substr(Auth::user()->name, 0, 2) }}</span>
                            @endif
                        </div>
                        <div
                            class="absolute -bottom-2 -right-2 bg-green-400 w-6 h-6 rounded-full border-4 border-indigo-600">
                        </div>
                    </div>

                    <div class="flex-1">
                        <div
                            class="inline-flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full backdrop-blur-sm border border-white/10 mb-4 text-[10px] font-black uppercase tracking-widest text-blue-100">
                            <i class="fas fa-shield-alt"></i>
                            Cuenta Verificada
                        </div>
                        <h2 class="text-4xl font-black text-white tracking-tighter mb-2">{{ Auth::user()->name }}</h2>
                        <p class="text-blue-100/80 font-bold tracking-tight text-lg">{{ Auth::user()->email }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="bg-white/10 backdrop-blur-sm px-6 py-4 rounded-2xl border border-white/10 text-center">
                            <p class="text-[10px] font-black text-blue-100 uppercase tracking-widest opacity-60 mb-1">
                                Empresas</p>
                            <p class="text-2xl font-black text-white italic">--</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Profile Info Section --}}
                <div class="group">
                    <div
                        class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1">
                        <div class="h-2 bg-gradient-to-r from-indigo-500 to-blue-500"></div>
                        <div class="p-8">
                            <div class="flex items-center gap-4 mb-8">
                                <div
                                    class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 shadow-inner">
                                    <i class="fas fa-user-edit text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-slate-800 tracking-tight">Información Básica</h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">General</p>
                                </div>
                            </div>

                            <div class="bg-slate-50/50 rounded-2xl p-6 border border-slate-100">
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Security Section --}}
                <div class="group">
                    <div
                        class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1">
                        <div class="h-2 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
                        <div class="p-8">
                            <div class="flex items-center gap-4 mb-8">
                                <div
                                    class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 shadow-inner">
                                    <i class="fas fa-lock text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-slate-800 tracking-tight">Seguridad</h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Contraseña</p>
                                </div>
                            </div>

                            <div class="bg-slate-50/50 rounded-2xl p-6 border border-slate-100">
                                @include('profile.partials.update-password-form')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Advanced Actions --}}
            <div
                class="bg-red-50/50 rounded-3xl p-8 border border-red-100/50 flex flex-col md:flex-row items-center justify-between gap-6 transition-all hover:bg-red-50">
                <div class="flex items-center gap-5">
                    <div
                        class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center text-red-600 shadow-sm border border-red-200">
                        <i class="fas fa-trash-alt text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-black text-slate-800 tracking-tight">Zona de Peligro</h4>
                        <p class="text-sm font-medium text-slate-500 max-w-sm">Eliminar tu cuenta es una acción permanente y
                            no se puede deshacer.</p>
                    </div>
                </div>
                <div class="bg-white p-2 rounded-2xl shadow-sm border border-red-100">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    @endsection
</x-adminpanel::layouts.master>
