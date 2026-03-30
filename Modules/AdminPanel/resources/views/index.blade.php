<x-adminpanel::layouts.master>
    @section('title', 'Dashboard General')

    @section('content')
        <!-- Bento Grid Container -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 animate-in fade-in duration-700">
            
            <!-- 1. Caja Principal de Bienvenida -->
            <div class="md:col-span-2 lg:col-span-2 bg-slate-900 rounded-3xl border border-slate-800 shadow-sm transition-all flex flex-col justify-center p-8 relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-indigo-500 rounded-full blur-3xl opacity-20"></div>
                <div class="relative z-10">
                    <h1 class="text-3xl font-black text-white tracking-tight leading-tight">
                        Hola, Administrador.<br/>
                        <span class="text-indigo-400">Bienvenido a Remsis.</span>
                    </h1>
                    <div class="mt-4 flex items-center space-x-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                        <p class="text-sm font-medium text-slate-300">
                            Ciclo de Remuneraciones: Marzo 2026 - En proceso
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Caja de Alertas Accionables -->
            <div x-data="{ openAlerts: false }" 
                 @click="openAlerts = true"
                 class="md:col-span-1 lg:col-span-1 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md hover:bg-slate-50 transition-all cursor-pointer p-6 flex flex-col justify-between group">
                <div class="flex justify-between items-start">
                    <div class="p-3 bg-amber-50 rounded-2xl text-amber-500 group-hover:scale-110 transition-transform">
                        <i class="fa-regular fa-bell text-xl"></i>
                    </div>
                    <span class="flex h-3 w-3 relative">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-black text-slate-800 leading-none">3</h3>
                    <p class="text-sm font-semibold text-slate-500 mt-2 leading-tight">Solicitudes de Vacaciones pendientes</p>
                </div>
            </div>

            <!-- 3. Caja de Accesos Rápidos -->
            <div class="md:col-span-3 lg:col-span-1 bg-white rounded-3xl border border-slate-200 shadow-sm transition-all p-5 flex flex-col">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 px-1">Accesos Rápidos</h3>
                <div class="grid grid-cols-2 gap-2 flex-grow">
                    <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl p-3 flex flex-col items-center justify-center gap-2 transition-colors">
                        <i class="fa-solid fa-user-plus text-lg/none"></i>
                        <span class="text-[10px] font-bold text-center leading-tight">Nuevo<br>Colab.</span>
                    </button>
                    <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl p-3 flex flex-col items-center justify-center gap-2 transition-colors">
                        <i class="fa-solid fa-cloud-arrow-up text-lg/none"></i>
                        <span class="text-[10px] font-bold text-center leading-tight">Subir<br>Doc.</span>
                    </button>
                    <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl p-3 flex flex-col items-center justify-center gap-2 transition-colors">
                        <i class="fa-solid fa-clipboard-check text-lg/none"></i>
                        <span class="text-[10px] font-bold text-center leading-tight">Aprobar<br>Turnos</span>
                    </button>
                    <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl p-3 flex flex-col items-center justify-center gap-2 transition-colors">
                        <i class="fa-solid fa-book text-lg/none"></i>
                        <span class="text-[10px] font-bold text-center leading-tight">Catálogo<br>LRE</span>
                    </button>
                </div>
            </div>

            <!-- 4. Caja de "People Analytics" Rápido -->
            <div class="md:col-span-1 lg:col-span-1 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">People Analytics</h3>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-4xl font-black text-slate-800 tracking-tighter">145</span>
                        <span class="text-xs font-bold text-slate-500">Total Dotación</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <div class="flex justify-between text-[10px] font-bold text-slate-500 mb-2">
                        <span>Indefinido (116)</span>
                        <span>Plazo Fijo (29)</span>
                    </div>
                    <!-- Tailwind Progress Bar -->
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex">
                        <div class="h-full bg-indigo-500 rounded-none border-0" style="width: 80%"></div>
                        <div class="h-full bg-amber-400 rounded-none border-0" style="width: 20%"></div>
                    </div>
                </div>
            </div>

            <!-- 5. Caja de Historial Reciente -->
            <div class="md:col-span-2 lg:col-span-3 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center">
                        <i class="fa-solid fa-clock-rotate-left text-slate-400 mr-2"></i>
                        Últimos movimientos del sistema
                    </h3>
                    <a href="#" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-800 transition-colors tracking-wider">Ver Todo</a>
                </div>
                
                <div class="flex-1 space-y-4">
                    <!-- Registros Mockup -->
                    <div class="flex items-start space-x-3 group cursor-default">
                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 mt-0.5 group-hover:bg-indigo-100 transition-colors">
                            <i class="fa-solid fa-money-bill-wave text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700">Juan Pérez modificó el sueldo base de <span class="font-bold">María López</span></p>
                            <p class="text-[11px] font-medium text-slate-400 mt-0.5">Hace 10 min</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3 group cursor-default">
                        <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5 group-hover:bg-emerald-100 transition-colors">
                            <i class="fa-solid fa-user-check text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700">Sistema aprobó contrato digital de <span class="font-bold">Carlos San Martín</span></p>
                            <p class="text-[11px] font-medium text-slate-400 mt-0.5">Hace 45 min</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3 group cursor-default">
                        <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0 mt-0.5 group-hover:bg-amber-100 transition-colors">
                            <i class="fa-solid fa-file-invoice text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700">Ana Rojas generó el Archivo PreviRed de <span class="font-bold">Marzo 2026</span></p>
                            <p class="text-[11px] font-medium text-slate-400 mt-0.5">Hace 2 horas</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    @endsection
</x-adminpanel::layouts.master>
