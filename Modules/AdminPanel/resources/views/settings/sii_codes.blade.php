<x-adminpanel::layouts.master>
    @section('title', 'Códigos SII - Segunda Categoría')

    @section('content')
        <div class="max-w-7xl mx-auto">

            {{-- Header Info --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                    <i class="fas fa-info-circle text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-blue-900 mb-1">Códigos de Segunda Categoría - No Afectos a IVA</h3>
                    <p class="text-sm text-blue-700">Códigos oficiales del SII para actividades profesionales y servicios
                        personales. Estos códigos se utilizan para la declaración de impuestos de segunda categoría.</p>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="bg-green-100 text-green-600 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Códigos Activos</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $codigosSii->where('activo', true)->count() }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                            <i class="fas fa-list-ol text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Códigos</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $codigosSii->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-lg">
                            <i class="fas fa-percentage text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">UTMs Mínimo</p>
                            <p class="text-2xl font-bold text-gray-800">13.5</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Table --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 text-lg">Listado de Códigos</h3>
                    <p class="text-xs text-gray-500 mt-1">Fuente: Servicio de Impuestos Internos (SII)</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                            <tr>
                                <th class="px-6 py-3 border-b text-left w-32">Código</th>
                                <th class="px-6 py-3 border-b text-left">Glosa / Descripción</th>
                                <th class="px-6 py-3 border-b text-center w-32">UTMs Mín.</th>
                                <th class="px-6 py-3 border-b text-center w-24">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($codigosSii as $codigo)
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-md">
                                            {{ $codigo->codigo }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        {{ $codigo->glosa }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="font-semibold text-gray-700">{{ number_format($codigo->utms_min, 1) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($codigo->activo)
                                            <span
                                                class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                                <i class="fas fa-check-circle"></i>
                                                Activo
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-semibold">
                                                <i class="fas fa-times-circle"></i>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400 bg-white">
                                        <div class="flex flex-col items-center gap-2">
                                            <i class="fas fa-inbox text-3xl opacity-50"></i>
                                            <span class="text-sm">No hay códigos SII registrados</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer Info --}}
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-start gap-3 text-sm text-gray-600">
                    <i class="fas fa-lightbulb text-yellow-500 mt-0.5"></i>
                    <div>
                        <p class="font-semibold text-gray-700 mb-1">Información importante:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Estos códigos corresponden a actividades de segunda categoría no afectas a IVA</li>
                            <li>El valor de UTMs mínimo (13.5) es el umbral para la retención de impuestos</li>
                            <li>Los códigos son actualizados según las normativas vigentes del SII</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    @endsection
</x-adminpanel::layouts.master>
