<x-adminpanel::layouts.master>
    @section('title', 'Empresas - Períodos de Nómina')

    @section('content')
        <div class="max-w-7xl mx-auto text-sm">
            {{-- Header --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-6 border-b border-gray-200">
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-600 text-white p-3 rounded-lg shadow-md">
                            <i class="fas fa-building text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">Períodos de Nómina por Empresa</h1>
                            <p class="text-gray-600 mt-1">Seleccione una empresa para gestionar sus períodos</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Companies Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($companies as $company)
                    <a href="{{ route('payroll-periods.byCompany', ['company' => $company->id]) }}"
                        class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-blue-300 transition-all overflow-hidden group">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition-colors">
                                        {{ $company->razon_social }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">RUT: {{ $company->rut }}</p>
                                </div>
                                <div class="bg-blue-50 text-blue-600 p-2 rounded-lg">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div>
                                    <p class="text-xs text-gray-500">Períodos registrados</p>
                                    <p class="text-2xl font-bold text-gray-800">{{ $company->payroll_periods_count }}</p>
                                </div>
                                <div class="text-blue-600 group-hover:translate-x-1 transition-transform">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <div class="opacity-30">
                                <i class="fas fa-building text-5xl mb-4"></i>
                                <p class="text-gray-600">No hay empresas registradas</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    @endsection
</x-adminpanel::layouts.master>
