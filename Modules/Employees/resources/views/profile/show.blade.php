<x-adminpanel::layouts.master>
    @section('title', 'Perfil de empleado')

    @section('content')
        <div class="max-w-5xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Mi Ficha Personal</h1>

            <div class="grid md:grid-cols-3 gap-6">
                {{-- Datos básicos del usuario --}}
                <div class="md:col-span-1 bg-white p-4 rounded shadow">
                    <h2 class="font-semibold mb-2">Datos de Usuario</h2>
                    <p><span class="font-medium">Nombre:</span> {{ $user->name }}</p>
                    <p><span class="font-medium">Email:</span> {{ $user->email }}</p>
                    <p><span class="font-medium">Rol:</span> employee</p>
                </div>

                {{-- Datos laborales --}}
                <div class="md:col-span-2 bg-white p-4 rounded shadow">
                    <h2 class="font-semibold mb-2">Datos Laborales</h2>
                    <p><span class="font-medium">Empresa:</span> {{ optional($employee->company)->name ?? '—' }}</p>
                    <p><span class="font-medium">RUT:</span> {{ $employee->rut ?? '—' }}</p>
                    <p><span class="font-medium">Cargo:</span> {{ $employee->position ?? '—' }}</p>
                    <p><span class="font-medium">Fecha Ingreso:</span>
                        {{ optional($employee->start_date)->format('d-m-Y') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

    @endsection

</x-adminpanel::layouts.master>
