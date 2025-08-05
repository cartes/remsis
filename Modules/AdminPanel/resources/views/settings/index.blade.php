<x-adminpanel::layouts.master>
    @section('title', 'Configuraciones')

    @section('content')
        <div class="p-6">
            <h1 class="text-2xl font-semibold mb-4">Administrar AFPs</h1>

            <!-- Formulario para agregar nueva AFP -->
            <form action="{{ route('afps.store') }}" method="POST" class="mb-6">
                @csrf
                <div class="flex gap-4 items-center">
                    <input type="text" name="nombre" required placeholder="Nombre de la AFP"
                        class="border border-gray-300 rounded px-4 py-2 w-full">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Agregar AFP
                    </button>
                </div>
            </form>

            <!-- Tabla de AFPs -->
            <table class="w-full table-auto border-collapse border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2 text-left">ID</th>
                        <th class="border px-4 py-2 text-left">Nombre</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($afps as $afp)
                        <tr>
                            <td class="border px-4 py-2">{{ $afp->id }}</td>
                            <td class="border px-4 py-2">{{ $afp->nombre }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="border px-4 py-2 text-center text-gray-500">No hay AFPs registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endsection
</x-adminpanel::layouts.master>
