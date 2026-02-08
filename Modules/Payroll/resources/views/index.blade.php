<x-adminpanel::layouts.master>
    @section('title', 'Historial de Nóminas')

    @section('content')
        <div class="max-w-7xl mx-auto text-sm">
            {{-- Company Context Header (if viewing specific company) --}}
            @if (isset($companyModel))
                <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-building text-blue-600"></i>
                        <div>
                            <span class="font-semibold text-gray-800">{{ $companyModel->razon_social }}</span>
                            <span class="text-gray-500 text-xs ml-2">RUT: {{ $companyModel->rut }}</span>
                        </div>
                    </div>
                    <a href="{{ route('companies.index') }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a Empresas
                    </a>
                </div>
            @endif

            {{-- Header --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="bg-blue-600 text-white p-3 rounded-lg shadow-md">
                                <i class="fas fa-file-invoice-dollar text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-800">Historial de Nóminas</h1>
                                <p class="text-gray-600 mt-1">Gestión y revisión de remuneraciones pagadas</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                class="bg-white text-gray-700 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-all font-semibold flex items-center gap-2">
                                <i class="fas fa-download"></i>
                                <span>Exportar</span>
                            </button>
                            <button
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all shadow-md font-semibold flex items-center gap-2">
                                <i class="fas fa-plus"></i>
                                <span>Generar Nómina</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Empleado</th>
                            <th class="px-6 py-4 text-left">Periodo</th>
                            <th class="px-6 py-4 text-center">Tipo</th>
                            <th class="px-6 py-4 text-right">Monto Líquido</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($payrolls as $payroll)
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-800">{{ $payroll->employee->user->name ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $payroll->employee->position ?? 'Empleado' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                    {{ $payroll->period->name ?? 'Enero 2024' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] uppercase font-bold border {{ $payroll->type === 'normal' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                        {{ $payroll->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-gray-800">
                                    ${{ number_format($payroll->net_salary ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] uppercase font-bold {{ $payroll->status === 'paid' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-gray-50 text-gray-400 border border-gray-100' }}">
                                        {{ $payroll->status === 'paid' ? 'Pagado' : 'Pendiente' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-all"
                                            title="Descargar Liquidación">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="opacity-30">
                                        <i class="fas fa-receipt text-5xl mb-4"></i>
                                        <p class="text-gray-600">No hay nóminas registradas para este periodo</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination --}}
                @if ($payrolls->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        {{ $payrolls->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endsection
</x-adminpanel::layouts.master>
