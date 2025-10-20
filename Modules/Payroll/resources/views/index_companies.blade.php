<x-adminpanel::layouts.master>
    @section('title', 'Administración de Empresas')

    @section('content')
        @if ($companies->isEmpty())
            <div class="flex items-center justify-center h-[70vh]">
                <div class="text-center space-y-4">
                    <p class="text-gray-500 text-xl">No hay empresas registradas aún.</p>
                </div>
            </div>
        @else
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">Empresas</h2>

                <table class="w-full mt-4 border rounded shadow-sm">
                    <thead class="bg-gray-100 text-left">
                        <tr class="border-b">
                            <th class="p-2">Nombre</th>
                            <th class="p-2">RUT</th>
                            <th class="p-2">N° Nóminas</th>
                            <th class="p-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2">
                                    <a href="{{ route('payrolls.byCompany', ['company' => $company->id]) }}"
                                       class="text-blue-600 hover:underline">
                                        {{ $company->name }}
                                    </a>
                                </td>
                                <td class="p-2">{{ $company->rut }}</td>
                                <td class="p-2 font-semibold">{{ $company->payrolls_count }}</td>
                                <td class="p-2">
                                    <a href="{{ route('payrolls.byCompany', ['company' => $company->id]) }}"
                                       class="text-blue-600 hover:underline">
                                        Ver Remuneraciones
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endsection
</x-adminpanel::layouts.master>
