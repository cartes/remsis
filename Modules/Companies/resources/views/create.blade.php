<x-adminpanel::layouts.master>
    @section('title', 'Agregar nueva empresa')

    @section('content')
        <div class="bg-white rounded shadow p-6 max-w-2xl mx-auto">
            <h2 class="text-lg font-bold mb-6">Nueva Empresa</h2>

            {{-- Mensajes de validación --}}
            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('companies.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block font-medium mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full border rounded p-2 focus:outline-none focus:ring focus:border-blue-300" required>
                </div>

                <div>
                    <label class="block font-medium mb-1">RUT</label>
                    <input type="text" name="rut" value="{{ old('rut') }}"
                        class="w-full border rounded p-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>

                <div>
                    <label class="block font-medium mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full border rounded p-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>

                <div>
                    <label class="block font-medium mb-1">Teléfono</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full border rounded p-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>

                <div class="flex justify-between items-center mt-6">
                    <a href="{{ route('companies.index') }}"
                        class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        Guardar Empresa
                    </button>
                </div>
            </form>
        </div>
    @endsection
</x-adminpanel::layouts.master>
