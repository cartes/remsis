<x-adminpanel::layouts.master>
    @section('title', 'Configuraciones Generales')

    @section('content')
        @php
            $entidades = [
                'afps' => [
                    'label' => 'AFP',
                    'store' => 'afps.store',
                    'edit' => 'afps.edit',
                    'update' => 'afps.update',
                    'destroy' => 'afps.destroy',
                    'data' => $afps,
                    'session' => 'success_afp',
                    'ref' => 'afpsTable',
                    'icon' => 'fas fa-building',
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
                    'icon' => 'fas fa-heartbeat',
                ],
                'ccafs' => [
                    'label' => 'Cajas de Compensación (CCAF)',
                    'store' => 'ccafs.store',
                    'edit' => 'ccafs.edit',
                    'update' => 'ccafs.update',
                    'destroy' => 'ccafs.destroy',
                    'data' => $ccafs,
                    'session' => 'success_ccaf',
                    'ref' => 'ccafsTable',
                    'icon' => 'fas fa-hands-helping',
                ],
            ];
        @endphp

        <div x-data="{ activeTab: 'afps', ...settingsManager() }" class="max-w-6xl mx-auto space-y-6">

            {{-- HEADER / TABS --}}
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 flex overflow-x-auto">
                    @foreach ($entidades as $key => $config)
                        <button @click="activeTab = '{{ $key }}'"
                            :class="{
                                'border-b-2 border-blue-600 text-blue-700 bg-blue-50/50': activeTab === '{{ $key }}',
                                'text-gray-600 hover:text-gray-800 hover:bg-gray-100': activeTab !== '{{ $key }}'
                            }"
                            class="px-6 py-4 text-sm font-medium transition-colors duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none">
                            <i class="{{ $config['icon'] ?? 'fas fa-circle' }}"></i>
                            {{ $config['label'] }}
                        </button>
                    @endforeach
                </div>

                {{-- CONTENT --}}
                <div class="p-6 bg-white min-h-[400px]">
                    @foreach ($entidades as $key => $config)
                        <div x-show="activeTab === '{{ $key }}'" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0">

                            {{-- Action Header --}}
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-800">{{ $config['label'] }}</h2>
                                    <p class="text-sm text-gray-500 mt-1">Administra los registros disponibles para
                                        {{ strtolower($config['label']) }}.</p>
                                </div>
                                <button
                                    @click="openModal('{{ $config['label'] }}', '{{ $key }}', '{{ route($config['store']) }}', '{{ $config['ref'] }}')"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2 text-sm font-medium">
                                    <i class="fas fa-plus"></i>
                                    <span>Agregar Nuevo</span>
                                </button>
                            </div>

                            {{-- Alerts --}}
                            @if (session($config['session']))
                                <div class="mb-4 bg-green-50 text-green-700 border border-green-200 px-4 py-3 rounded-lg flex items-center gap-2"
                                    role="alert">
                                    <i class="fas fa-check-circle"></i>
                                    <span>{{ session($config['session']) }}</span>
                                </div>
                            @endif

                            {{-- Table --}}
                            <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                                <table x-ref="{{ $config['ref'] }}" class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                                        <tr>
                                            <th class="px-6 py-3 border-b text-center w-20">ID</th>
                                            <th class="px-6 py-3 border-b">Nombre</th>
                                            <th class="px-6 py-3 border-b text-center w-32">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @forelse ($config['data'] as $item)
                                            <tr class="hover:bg-gray-50 transition-colors group">
                                                <td class="px-6 py-3 text-center text-gray-500 font-mono text-xs">
                                                    {{ $item->id }}</td>
                                                <td class="px-6 py-3 font-medium text-gray-800">{{ $item->nombre }}</td>
                                                <td class="px-6 py-3 text-center">
                                                    <div class="flex items-center justify-center gap-3">
                                                        <button
                                                            @click="editModal(
                                                                '{{ $item->id }}',
                                                                '{{ $item->nombre }}',
                                                                '{{ $config['label'] }}',
                                                                '{{ route($config['update'], $item->id) }}',
                                                                '{{ $config['ref'] }}'
                                                            )"
                                                            class="text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-1.5 rounded-full transition-colors"
                                                            title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button
                                                            @click="deleteItem('{{ $item->id }}', '{{ $config['label'] }}', '{{ route($config['destroy'], $item->id) }}', '{{ $config['ref'] }}')"
                                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1.5 rounded-full transition-colors"
                                                            title="Eliminar">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-6 py-12 text-center text-gray-400 bg-white">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <i class="fas fa-inbox text-3xl opacity-50"></i>
                                                        <span class="text-sm">No hay registros disponibles</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- MODAL CREATE --}}
            <div x-show="modal.open" 
                style="display: none;"
                class="fixed inset-0 z-50 overflow-y-auto" 
                aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                
                {{-- Backdrop --}}
                <div x-show="modal.open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="modal.open = false"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="modal.open" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        @click.away="modal.open = false"
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fas fa-plus text-blue-600"></i>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
                                        Agregar <span x-text="modal.label"></span>
                                    </h3>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                        <input type="text" x-model="modal.nombre" required
                                            @keydown.enter="submit()"
                                            :placeholder="'Ingrese nombre'"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" @click="submit()"
                                class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                                Guardar
                            </button>
                            <button type="button" @click="modal.open = false"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MODAL EDIT --}}
            <div x-show="edit.open" 
                style="display: none;"
                class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                
                <div x-show="edit.open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="edit.open = false"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="edit.open" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        @click.away="edit.open = false"
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fas fa-edit text-blue-600"></i>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900">
                                        Editar <span x-text="edit.label"></span>
                                    </h3>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                        <input type="text" x-model="edit.nombre" required
                                            @keydown.enter="submitEdit()"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" @click="submitEdit()"
                                class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                                Guardar Cambios
                            </button>
                            <button type="button" @click="edit.open = false"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Cancelar
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
                        
                        // Focus input after modal opens
                        setTimeout(() => {
                            // Simple focus attempt if needed, though x-trap is better if available
                        }, 100);
                    },

                    async submit() {
                        if (!this.modal.nombre.trim()) return;
                        
                        try {
                            const response = await axios.post(this.modal.action, {
                                nombre: this.modal.nombre
                            });

                            const nuevo = response.data;

                            // Referencia a la tabla correcta
                            const tableBody = this.$refs[this.modal.ref].querySelector('tbody');
                            
                            // Remove "No records" row if it exists
                            if (tableBody.rows.length === 1 && tableBody.rows[0].cells.length === 1) {
                                tableBody.innerHTML = '';
                            }

                            const row = document.createElement('tr');
                            row.className = "hover:bg-gray-50 transition-colors group";
                            row.innerHTML = `
                                <td class="px-6 py-3 text-center text-gray-500 font-mono text-xs">${nuevo.id || '?'}</td>
                                <td class="px-6 py-3 font-medium text-gray-800">${nuevo.nombre}</td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button class="text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-1.5 rounded-full transition-colors" onclick="window.location.reload()">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            // Note: Added a reload button temporarily for the new row because binding Alpine click events 
                            // to dynamically inserted HTML is tricky without a full component re-render. 
                            // Ideally, the list should be reactive, but this is a pure DOM manipulation approach 
                            // to match the existing pattern.
                            
                            tableBody.appendChild(row);

                            this.modal.open = false;
                            
                            // Optional hooks/notifications could go here
                            
                        } catch (error) {
                            alert('Error al guardar: ' + (error.response?.data?.message || error.message));
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
                    },

                    async submitEdit() {
                        if (!this.edit.nombre.trim()) return;

                        try {
                            const response = await axios.put(this.edit.action, {
                                nombre: this.edit.nombre
                            });

                            const actualizado = response.data;

                            const tableBody = this.$refs[this.edit.ref].querySelector('tbody');
                            const row = Array.from(tableBody.rows).find(r => r.cells[0]?.textContent.trim() == this.edit.rowId);
                            
                            if (row) {
                                row.cells[1].textContent = this.edit.nombre; // Optimistically update or use response
                                
                                // Flash effect
                                row.classList.add('bg-green-50');
                                setTimeout(() => row.classList.remove('bg-green-50'), 1000);
                            }

                            this.edit.open = false;
                        } catch (error) {
                             alert('Error al actualizar: ' + (error.response?.data?.message || error.message));
                            console.error(error);
                        }
                    },
                    
                    async deleteItem(id, label, action, ref) {
                        if(!confirm(`¿Estás seguro de que deseas eliminar este registro de ${label}?`)) return;
                        
                        try {
                            await axios.delete(action);
                             
                            const tableBody = this.$refs[ref].querySelector('tbody');
                            const row = Array.from(tableBody.rows).find(r => r.cells[0]?.textContent.trim() == id);
                            
                            if (row) {
                                row.remove();
                                if(tableBody.rows.length === 0) {
                                    tableBody.innerHTML = `
                                        <tr>
                                            <td colspan="3" class="px-6 py-12 text-center text-gray-400 bg-white">
                                                <div class="flex flex-col items-center gap-2">
                                                    <i class="fas fa-inbox text-3xl opacity-50"></i>
                                                    <span class="text-sm">No hay registros disponibles</span>
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                                }
                            }
                        } catch (error) {
                            alert('Error al eliminar: ' + (error.response?.data?.message || error.message));
                            console.error(error);
                        }
                    }
                }
            }
        </script>
    @endpush
</x-adminpanel::layouts.master>
