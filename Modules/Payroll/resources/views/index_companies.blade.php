<x-adminpanel::layouts.master>
    @section('title', 'Administración de Empresas')

    @section('content')
        <div class="max-w-7xl mx-auto p-4 sm:p-6">
            {{-- Header --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6">
                <div
                    class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-600 text-white p-3 rounded-lg shadow-md">
                            <i class="fas fa-file-invoice-dollar text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Administración de Nóminas</h2>
                            <p class="text-sm text-gray-600 mt-1">Selecciona una empresa para gestionar sus remuneraciones
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($companies->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-building text-3xl text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">No hay empresas registradas</h3>
                    <p class="text-gray-500 mb-6">Primero debes registrar empresas en el sistema para gestionar sus nóminas.
                    </p>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-6 py-4 text-left">Empresa</th>
                                <th class="px-6 py-4 text-left">RUT</th>
                                <th class="px-6 py-4 text-center">N° Nóminas</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($companies as $company)
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800">{{ $company->name }}</div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">{{ $company->razon_social }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-gray-600">{{ $company->rut }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $company->payrolls_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('payrolls.byCompany', ['company' => $company->id]) }}"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all font-bold text-xs uppercase tracking-wide border border-blue-100">
                                            <i class="fas fa-eye"></i>
                                            Ver Detalles
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endsection
</x-adminpanel::layouts.master>
