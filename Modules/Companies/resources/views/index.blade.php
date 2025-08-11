<x-adminpanel::layouts.master>
    @section('title', 'Administración de Empresas')

    @section('content')
        @if ($companies->isEmpty())
            <div class="flex items-center justify-center h-[70vh]">
                <div class="text-center space-y-4">
                    <p class="text-gray-500 text-xl">No hay ninguna empresa creada aún.</p>
                    <a href="{{ route('companies.create') }}"
                        class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                        Crear nueva empresa
                    </a>
                </div>
            </div>
        @else
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">Empresas registradas</h2>

                <a href="{{ route('companies.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Nueva Empresa
                </a>

                <table class="w-full mt-4 border rounded shadow-sm">
                    <thead class="bg-gray-100 text-left">
                        <tr class="border-b">
                            <th class="p-2">Nombre</th>
                            <th class="p-2">Email</th>
                            <th class="p-2">Teléfono</th>
                            <th class="p-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2">
                                    <a href="{{ route('companies.edit', $company->id) }}"
                                        class="text-blue-600 hover:underline">
                                        {{ $company->name }}
                                    </a>
                                </td>
                                <td class="p-2">{{ $company->email }}</td>
                                <td class="p-2">{{ $company->phone }}</td>
                                <td class="p-2">
                                    <a href="{{ route('companies.edit', $company->id) }}"
                                        class="text-blue-500 hover:underline">
                                        Editar
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
