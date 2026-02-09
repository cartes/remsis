<x-adminpanel::layouts.master>
    @section('title', 'Administración de Empresas')

    @section('content')
        @if ($companies->isEmpty())
            <div class="flex items-center justify-center h-[60vh]">
                <div class="text-center space-y-6 max-w-md">
                    <div class="bg-gray-100 rounded-full w-24 h-24 flex items-center justify-center mx-auto">
                        <i class="fas fa-building text-4xl text-gray-400"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">No hay empresas registradas</h3>
                        <p class="text-gray-500">Comienza creando tu primera empresa para gestionar empleados y nóminas.</p>
                    </div>
                    <a href="{{ route('companies.create') }}"
                        class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg font-bold">
                        <i class="fas fa-plus-circle"></i>
                        Crear Primera Empresa
                    </a>
                </div>
            </div>
        @else
            <div class="max-w-7xl mx-auto">

                {{-- Header with Action Button --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Empresas Registradas</h2>
                        <p class="text-sm text-gray-500 mt-1">Gestiona las empresas del sistema</p>
                    </div>
                    <a href="{{ route('companies.create') }}"
                        class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors shadow-sm font-medium">
                        <i class="fas fa-plus"></i>
                        <span>Nueva Empresa</span>
                    </a>
                </div>

                {{-- Companies Table --}}
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 border-b text-left">Empresa</th>
                                    <th class="px-6 py-3 border-b text-left">RUT</th>
                                    <th class="px-6 py-3 border-b text-left">Email</th>
                                    <th class="px-6 py-3 border-b text-left">Teléfono</th>
                                    <th class="px-6 py-3 border-b text-center w-32">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($companies as $company)
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-800">
                                                        <a class="text-blue-600 block hover:underline"
                                                            href="{{ route('companies.edit', $company->id) }}">
                                                            {{ $company->razon_social ?? $company->name }}
                                                            @if ($company->nombre_fantasia)
                                                                <div class="text-xs text-gray-500 font-normal mt-0.5">
                                                                    {{ $company->nombre_fantasia }}
                                                                </div>
                                                            @endif
                                                        </a>
                                                    </div>
                                                </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-gray-700">{{ $company->rut }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-gray-600">
                                                <i class="fas fa-envelope text-xs text-gray-400"></i>
                                                {{ $company->email ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-gray-600">
                                                <i class="fas fa-phone text-xs text-gray-400"></i>
                                                {{ $company->phone ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('companies.edit', $company->id) }}"
                                                    class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 font-semibold text-xs px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-all">
                                                    <i class="fas fa-edit"></i>
                                                    Editar
                                                </a>
                                                <a href="{{ route('companies.employees', $company->id) }}"
                                                    class="inline-flex items-center gap-1 text-green-600 hover:text-green-800 font-semibold text-xs px-3 py-1.5 rounded-lg hover:bg-green-50 transition-all">
                                                    <i class="fas fa-users"></i>
                                                    Empleados
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination if needed --}}
                @if (method_exists($companies, 'links'))
                    <div class="mt-6">
                        {{ $companies->links() }}
                    </div>
                @endif

            </div>
        @endif

    @endsection
</x-adminpanel::layouts.master>
