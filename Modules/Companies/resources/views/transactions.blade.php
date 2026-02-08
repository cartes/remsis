<x-layouts.company :company="$company" activeTab="transactions">
    <div class="space-y-6 animate-fade-in">
        {{-- Header con Acciones --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Movimientos Financieros</h2>
                <p class="text-sm text-gray-500 mt-1">Consulta y gestión de ingresos y egresos de la empresa.</p>
            </div>
            <button
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 flex items-center gap-2">
                <i class="fas fa-plus-circle"></i>
                Nuevo Movimiento
            </button>
        </div>

        {{-- Tarjetas de Resumen Dinámico --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Ingresos --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Ingresos</p>
                        <p class="text-3xl font-black text-green-600 mt-1 tracking-tight">$0</p>
                        <p class="text-[10px] text-green-500 font-bold mt-1 flex items-center gap-1">
                            <i class="fas fa-arrow-up"></i> 0% vs mes anterior
                        </p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-trend-up text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            {{-- Egresos --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Egresos</p>
                        <p class="text-3xl font-black text-red-600 mt-1 tracking-tight">$0</p>
                        <p class="text-[10px] text-red-500 font-bold mt-1 flex items-center gap-1">
                            <i class="fas fa-arrow-down"></i> 0% vs mes anterior
                        </p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-trend-down text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>

            {{-- Balance --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Balance Neto</p>
                        <p class="text-3xl font-black text-blue-600 mt-1 tracking-tight">$0</p>
                        <div class="w-24 h-1.5 bg-gray-100 rounded-full mt-3 overflow-hidden">
                            <div class="h-full bg-blue-500 w-0"></div>
                        </div>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-scale-balanced text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sección de Tabla con Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div
                class="px-6 py-5 border-b border-gray-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                <h3 class="text-lg font-bold text-gray-800">Historial de Movimientos</h3>
                <div class="flex gap-2 w-full sm:w-auto">
                    <select
                        class="bg-white border border-gray-200 text-sm rounded-lg px-3 py-1.5 outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium text-gray-600">
                        <option>Todos los tipos</option>
                        <option>Ingresos</option>
                        <option>Egresos</option>
                    </select>
                    <select
                        class="bg-white border border-gray-200 text-sm rounded-lg px-3 py-1.5 outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium text-gray-600">
                        <option>Últimos 30 días</option>
                        <option>Este mes</option>
                        <option>Año actual</option>
                    </select>
                </div>
            </div>

            <div class="p-12 flex flex-col items-center justify-center text-center">
                <div
                    class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-dashed border-gray-300">
                    <i class="fas fa-ghost text-3xl text-gray-300"></i>
                </div>
                <h4 class="text-base font-bold text-gray-800">No se encontraron movimientos</h4>
                <p class="text-sm text-gray-500 mt-1 max-w-xs">Parece que aún no has registrado ninguna transacción para
                    esta empresa.</p>
                <button class="mt-6 inline-flex items-center gap-2 text-blue-600 font-bold text-sm hover:underline">
                    Comenzar ahora
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</x-layouts.company>
