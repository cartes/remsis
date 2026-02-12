<x-layouts.company :company="$company" activeTab="dashboard">
    @section('title', 'Dashboard - ' . $company->razon_social)

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        {{-- Dashboard Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Panel de Control</h1>
            <p class="text-sm text-gray-500 mt-2">Resumen operativo y métricas clave de
                {{ $company->nombre_fantasia ?? $company->razon_social }}.</p>
        </div>

        {{-- Metrics Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Employees --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Empleados</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ $totalEmployees }}</h3>
                </div>
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>

            {{-- Active Employees --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Empleados Activos</p>
                    <h3 class="text-3xl font-black text-green-600">{{ $activeEmployees }}</h3>
                </div>
                <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                    <i class="fas fa-user-check text-2xl"></i>
                </div>
            </div>

            {{-- Completion Average --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Llenado de Fichas</p>
                    <h3 class="text-3xl font-black {{ $completionAverage > 90 ? 'text-blue-600' : 'text-amber-500' }}">
                        {{ round($completionAverage) }}%</h3>
                </div>
                <div class="p-3 bg-amber-50 text-amber-500 rounded-xl">
                    <i class="fas fa-chart-pie text-2xl"></i>
                </div>
            </div>

            {{-- Pending Payroll --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Nómina del Mes</p>
                    <h3 class="text-3xl font-black text-gray-300">---</h3>
                </div>
                <div class="p-3 bg-gray-50 text-gray-400 rounded-xl">
                    <i class="fas fa-file-invoice-dollar text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Main Content Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Recent Activity / Alerts --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                        <h4 class="text-sm font-bold text-gray-800">Alertas de Nómina</h4>
                        <span
                            class="text-[10px] font-bold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full uppercase tracking-tighter">Próximo
                            Cierre: 30 Mar</span>
                    </div>
                    <div class="p-0">
                        <div class="p-12 text-center">
                            <i class="fas fa-check-circle text-green-100 text-6xl mb-4"></i>
                            <h5 class="text-lg font-bold text-gray-800 mb-1">Todo al día</h5>
                            <p class="text-sm text-gray-500">No hay discrepancias críticas o alertas pendientes para
                                este período.</p>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
                    <div class="relative z-10">
                        <h4 class="text-2xl font-black mb-2 italic">Procesamiento de Payroll</h4>
                        <p class="text-blue-100 text-sm mb-6 max-w-md">Optimiza la gestión de tu empresa asegurando que
                            todas las fichas de empleados estén completas sobre el 90%.</p>
                        <a href="{{ route('companies.employees', $company) }}"
                            class="inline-flex items-center gap-2 bg-white text-blue-700 px-6 py-3 rounded-xl font-black text-sm hover:bg-blue-50 transition-all shadow-lg transform hover:-translate-y-0.5">
                            Revisar Nómina <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <i class="fas fa-rocket absolute -right-4 -bottom-4 text-white/10 text-[180px] -rotate-12"></i>
                </div>
            </div>

            {{-- Quick Links / Sidebar --}}
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h4 class="text-sm font-bold text-gray-800 mb-4">Acciones Rápidas</h4>
                    <div class="space-y-3">
                        <a href="{{ route('companies.edit', $company) }}"
                            class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-all group">
                            <div
                                class="w-10 h-10 bg-amber-50 text-amber-500 rounded-lg flex items-center justify-center transition-all group-hover:bg-amber-500 group-hover:text-white">
                                <i class="fas fa-cog"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Configuración</span>
                        </a>
                        <a href="{{ route('companies.cost-centers', $company) }}"
                            class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-all group">
                            <div
                                class="w-10 h-10 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center transition-all group-hover:bg-purple-600 group-hover:text-white">
                                <i class="fas fa-sitemap"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Centros de Costo</span>
                        </a>
                        <a href="#"
                            class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-all group opacity-50 cursor-not-allowed">
                            <div
                                class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cloud-download-alt"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Exportar Reportes</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.company>
