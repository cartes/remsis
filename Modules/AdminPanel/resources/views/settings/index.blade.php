<x-adminpanel::layouts.master>
    @section('title', 'Configuraciones')

    @section('content')
        @php
            $entidades = [
                'afps' => [
                    'label' => 'AFP',
                    'store' => 'afps.store',
                    'edit' => 'afps.edit', // si usas formularios tradicionales
                    'update' => 'afps.update', // 🔹 NECESARIO para Axios + PUT
                    'destroy' => 'afps.destroy',
                    'data' => $afps,
                    'session' => 'success_afp',
                    'ref' => 'afpsTable',
                ],
                'isapres' => [
                    'label' => 'Isapres',
                    'store' => 'isapres.store',
                    'edit' => 'isapres.edit',
                    'update' => 'isapres.update',
                    'destroy' => 'isapres.destroy',
                    'data' => $isapres,
                    'session' => 'success_isapre',
                    'ref' => 'isapresTable',
                ],
                'ccafs' => [
                    'label' => 'CCAF',
                    'store' => 'ccafs.store',
                    'edit' => 'ccafs.edit',
                    'update' => 'ccafs.update',
                    'destroy' => 'ccafs.destroy',
                    'data' => $ccafs,
                    'session' => 'success_ccaf',
                    'ref' => 'ccafsTable',
                ],
            ];

        @endphp

        <div x-data="settingsManager()" class="max-w-5xl space-y-10">

            {{-- ENTIDADES --}}
            @foreach ($entidades as $key => $config)
                <section class="bg-white mb-4 shadow-sm rounded-md border p-5 space-y-4 max-w-[600px]">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-blue-900">{{ $config['label'] }}</h2>
                        <button
                            @click="openModal('{{ $config['label'] }}', '{{ $key }}', '{{ route($config['store']) }}', '{{ $config['ref'] }}')"
                            class="bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 text-sm">
                            Agregar {{ $config['label'] }}
                        </button>
                    </div>

                    @if (session($config['session']))
                        <div class="bg-green-100 text-green-800 px-3 py-2 rounded">
                            {{ session($config['session']) }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table x-ref="{{ $config['ref'] }}" class="min-w-[400px] w-full max-w-2xl border text-sm">
                            <thead class="bg-gray-100 text-gray-600">
                                <tr>
                                    <th class="border px-2 py-1 text-left w-20">ID</th>
                                    <th class="border px-2 py-1 text-left">Nombre</th>
                                    <th class="border px-2 py-1 w-32 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($config['data'] as $item)
                                    <tr>
                                        <td class="border px-2 py-1">{{ $item->id }}</td>
                                        <td class="border px-2 py-1">{{ $item->nombre }}</td>
                                        <td class="border px-2 py-1 text-center text-sm text-gray-500">
                                            <table>
                                                <tr>
                                                    <td>
                                                        <button
                                                            @click="editModal(
                                        '{{ $item->id }}',
                                        '{{ $item->nombre }}',
                                        '{{ $config['label'] }}',
                                        '{{ route($config['update'], $item->id) }}',
                                        '{{ $config['ref'] }}'
                                    )"
                                                            class="text-blue-600 hover:underline text-xs">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                    <td class="px-3">
                                                        <button
                                                            @click="deleteItem('{{ $item->id }}', '{{ $config['label'] }}', '{{ route($config['destroy'], $item->id) }}', '{{ $config['ref'] }}')"
                                                            class="text-red-600 hover:underline text-xs">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                            </table>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="border px-2 py-2 text-center text-gray-400">
                                            Sin registros
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach

            {{-- MODAL --}}
            <div x-show="modal.open" x-transition class="fixed mt-0 bg-black/50 flex items-center justify-center z-50">
                <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div @click.away="modal.open = false"
                        class="bg-white p-6 rounded-md w-full max-w-md shadow-lg space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800">Agregar <span x-text="modal.label"></span></h3>

                        <input type="text" x-model="modal.nombre" required :placeholder="'Nombre de ' + modal.label"
                            class="border border-gray-300 rounded px-3 py-2 w-full text-sm">

                        <div class="flex justify-end gap-2">
                            <button @click="modal.open = false"
                                class="bg-gray-300 text-gray-700 px-4 py-1.5 rounded hover:bg-gray-400 text-sm">
                                Cancelar
                            </button>
                            <button @click="submit()"
                                class="bg-blue-600 text-white px-4 py-1.5 rounded hover:bg-blue-700 text-sm">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal de Edicion --}}
            <div x-show="edit.open" x-transition class="fixed mt-0 bg-black/50 flex items-center justify-center z-50">

                <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div @click.away="edit.open = false"
                        class="bg-white p-6 rounded-md w-full max-w-md shadow-lg space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Editar <span x-text="edit.label"></span>
                        </h3>

                        <input type="text" x-model="edit.nombre" required :placeholder="'Nombre de ' + edit.label"
                            class="border border-gray-300 rounded px-3 py-2 w-full text-sm">

                        <div class="flex justify-end gap-2">
                            <button @click="edit.open = false"
                                class="bg-gray-300 text-gray-700 px-4 py-1.5 rounded hover:bg-gray-400 text-sm">
                                Cancelar
                            </button>
                            <button @click="submitEdit()"
                                class="bg-blue-600 text-white px-4 py-1.5 rounded hover:bg-blue-700 text-sm">
                                Guardar cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            function settingsManager() {
                return {
                    modal: {
                        open: false,
                        label: '',
                        type: '',
                        action: '',
                        nombre: '',
                        ref: ''
                    },

                    openModal(label, type, action, ref) {
                        this.modal.label = label;
                        this.modal.type = type;
                        this.modal.action = action;
                        this.modal.nombre = '';
                        this.modal.ref = ref;
                        this.modal.open = true;
                    },

                    async submit() {
                        try {
                            const response = await axios.post(this.modal.action, {
                                nombre: this.modal.nombre
                            });

                            const nuevo = response.data;

                            // Referencia a la tabla correcta
                            const table = this.$refs[this.modal.ref].querySelector('tbody');
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td class="border px-2 py-1">${nuevo.id}</td>
                                <td class="border px-2 py-1">${nuevo.nombre}</td>
                                <td class="border px-2 py-1 text-center text-sm text-gray-500">Editar / Eliminar</td>
                            `;
                            table.appendChild(row);

                            this.modal.open = false;
                        } catch (error) {
                            alert('Error al guardar');
                            console.error(error);
                        }
                    },

                    edit: {
                        open: false,
                        label: '',
                        nombre: '',
                        action: '',
                        ref: '',
                        rowId: ''
                    },

                    editModal(id, nombre, label, action, ref) {
                        this.edit.open = true;
                        this.edit.label = label;
                        this.edit.nombre = nombre;
                        this.edit.action = action;
                        this.edit.ref = ref;
                        this.edit.rowId = id;

                        console.log(this.edit.action);
                    },

                    async submitEdit() {
                        try {
                            const response = await axios.put(this.edit.action, {
                                nombre: this.edit.nombre
                            });

                            const actualizado = response.data;

                            const table = this.$refs[this.edit.ref].querySelector('tbody');
                            const row = Array.from(table.rows).find(r => r.cells[0]?.textContent == this.edit.rowId);
                            if (row) {
                                row.cells[1].textContent = actualizado.nombre;
                            }

                            this.edit.open = false;
                        } catch (error) {
                            alert('Error al actualizar');
                            console.error(error);
                        }
                    },
                }
            }
        </script>
    @endpush
</x-adminpanel::layouts.master>
