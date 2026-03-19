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
        @php
            $activePeriod = $company->periods()->whereIn('status', ['draft', 'open', 'calculated'])->latest('year')->latest('month')->first();
        @endphp
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

            {{-- Pending Payroll / Summary --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Nómina del Mes</p>
                    <h3 class="text-2xl font-black text-gray-900">{{ $activePeriod ? $activePeriod->getDisplayName() : '---' }}</h3>
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
                        @if($activePeriod)
                            <div class="p-10 flex flex-col items-center bg-indigo-50/30">
                                <div class="w-14 h-14 bg-white shadow-sm border border-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                                    <i class="fas fa-money-check-dollar text-2xl"></i>
                                </div>
                                <h5 class="text-xl font-black text-gray-900 mb-1">Periodo de Remuneraciones</h5>
                                <p class="text-sm text-gray-500 mb-6 text-center max-w-sm">Estamos procesando el periodo de <strong>{{ $activePeriod->getDisplayName() }}</strong>. Aún puedes realizar cambios.</p>
                                <a href="{{ route('companies.payroll-periods.wizard', ['company' => $company, 'period' => $activePeriod]) }}" 
                                    class="bg-blue-600 text-white px-8 py-3 rounded-xl font-black text-sm hover:bg-blue-700 transition-all shadow-lg hover:-translate-y-0.5 flex items-center gap-2">
                                    Ir a Liquidaciones: {{ $activePeriod->getDisplayName() }} <i class="fas fa-chevron-right text-xs"></i>
                                </a>
                            </div>
                        @else
                            <div class="p-12 text-center">
                                <i class="fas fa-check-circle text-green-100 text-6xl mb-4"></i>
                                <h5 class="text-lg font-bold text-gray-800 mb-1">Todo al día</h5>
                                <p class="text-sm text-gray-500">No hay discrepancias críticas o alertas pendientes para
                                    este período.</p>
                            </div>
                        @endif
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
                        <a href="{{ route('companies.edit', ['company' => $company, 'section' => 'company-data']) }}"
                            class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-all group">
                            <div
                                class="w-10 h-10 bg-amber-50 text-amber-500 rounded-lg flex items-center justify-center transition-all group-hover:bg-amber-500 group-hover:text-white">
                                <i class="fas fa-cog"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Datos empresa</span>
                        </a>
                        <a href="{{ route('companies.edit', ['company' => $company, 'section' => 'remunerations', 'tab' => 'remu']) }}"
                            class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-all group">
                            <div
                                class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center transition-all group-hover:bg-emerald-600 group-hover:text-white">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Remuneraciones</span>
                        </a>
                        <a href="{{ route('companies.edit', ['company' => $company, 'section' => 'remunerations', 'tab' => 'cost-centers']) }}"
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

    <!-- Admin Required Modal -->
    <div x-data="{ showAdminModal: @js(!$hasAdmin) }" x-show="showAdminModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAdminModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showAdminModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <form action="{{ route('companies.users.store', $company) }}" method="POST">
                    @csrf
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-6 text-center shadow-inner">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white/20 backdrop-blur border border-white/20 mb-4">
                            <i class="fas fa-user-shield text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl leading-6 font-black text-white" id="modal-title">Administrador Requerido</h3>
                        <p class="text-blue-100 text-sm mt-2 opacity-90 mx-auto">Para continuar utilizando el dashboard y gestionar funciones clave de la empresa, debes designar a un administrador principal.</p>
                    </div>
                    <div class="bg-white px-6 pt-5 pb-6">
                        <input type="hidden" name="role" value="admin">
                        <div class="space-y-4 text-sm w-full">
                            <div class="space-y-1">
                                <label class="font-bold text-slate-700 text-xs uppercase tracking-widest">Nombre Completo</label>
                                <input type="text" name="name" required class="w-full border border-gray-200 rounded-xl px-4 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Ej. Administrador">
                            </div>
                            <div class="space-y-1">
                                <label class="font-bold text-slate-700 text-xs uppercase tracking-widest">Correo Electrónico (Acceso)</label>
                                <input type="email" name="email" required class="w-full border border-gray-200 rounded-xl px-4 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="admin@empresa.com">
                            </div>
                            <div class="space-y-1">
                                <label class="font-bold text-slate-700 text-xs uppercase tracking-widest">Contraseña</label>
                                <input type="password" name="password" required minlength="6" class="w-full border border-gray-200 rounded-xl px-4 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Mínimo 6 caracteres">
                            </div>
                            <div class="mt-4 p-4 bg-slate-50 border border-slate-100 rounded-xl flex items-start gap-3">
                                <div class="flex items-center h-5">
                                    <input id="is_in_payroll" name="is_in_payroll" type="checkbox" value="1" class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500">
                                </div>
                                <div class="ml-2 text-sm">
                                    <label for="is_in_payroll" class="font-bold text-gray-700">¿Será parte de la nómina de la empresa?</label>
                                    <p class="text-[11px] font-medium text-gray-500 mt-1">Marca esta casilla si el administrador recibirá pagos o generará liquidaciones como empleado de <strong>{{ $company->razon_social }}</strong>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 rounded-b-2xl border-t border-gray-100 text-right">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-blue-600 text-sm font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Guardar Administrador <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.company>
