<x-adminpanel::layouts.master>
    @section('title', 'Centros de Costos - ' . $company->razon_social)

    @section('content')
        <div class="max-w-7xl mx-auto" x-data="{
            showModal: false,
            editMode: false,
            loading: false,
            costCenter: {
                id: null,
                code: '',
                name: '',
                description: '',
                status: 'active'
            },
            resetForm() {
                this.costCenter = {
                    id: null,
                    code: '',
                    name: '',
                    description: '',
                    status: 'active'
                };
                this.editMode = false;
            },
            openCreateModal() {
                this.resetForm();
                this.showModal = true;
            },
            openEditModal(cc) {
                this.costCenter = { ...cc };
                this.editMode = true;
                this.showModal = true;
            },
            async saveCostCenter() {
                this.loading = true;
                try {
                    const url = this.editMode
                        ? '{{ route('companies.cost-centers.update', [$company, ':id']) }}'.replace(':id', this.costCenter.id)
                        : '{{ route('companies.cost-centers.store', $company) }}';
                    const method = this.editMode ? 'put' : 'post';
                    const response = await axios[method](url, this.costCenter);
                    if (response.data.status === 'success') {
                        toast(response.data.message, 'success');
                        window.location.reload();
                    }
                } catch (error) {
                    toast(error.response?.data?.message || 'Error al guardar centro de costo', 'error');
                } finally {
                    this.loading = false;
                }
            },
            async deleteCostCenter(id) {
                if (!confirm('¿Seguro que deseas eliminar este centro de costo?')) return;
                try {
                    const url = '{{ route('companies.cost-centers.destroy', [$company, ':id']) }}'.replace(':id', id);
                    const response = await axios.delete(url);
                    if (response.data.status === 'success') {
                        toast(response.data.message, 'success');
                        window.location.reload();
                    }
                } catch (error) {
                    toast('Error al eliminar centro de costo', 'error');
                }
            }
        }">

            {{-- Company Header --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                                <i class="fas fa-building text-2xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">{{ $company->razon_social }}</h2>
                                <p class="text-sm text-gray-600 mt-1">RUT: <span
                                        class="font-mono font-semibold">{{ $company->rut }}</span></p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('companies.edit', $company) }}"
                                class="inline-flex items-center gap-2 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cost Centers Section --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Centros de Costos</h3>
                        <p class="text-sm text-gray-500 mt-1">Gestión de centros de costo de la empresa</p>
                    </div>
                    <button type="button" @click="openCreateModal()"
                        class="bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-sm flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Centro de Costo</span>
                    </button>
                </div>

                {{-- Cost Centers Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-6 py-4 text-left">Código</th>
                                <th class="px-6 py-4 text-left">Nombre</th>
                                <th class="px-6 py-4 text-left">Descripción</th>
                                <th class="px-6 py-4 text-center">Estado</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($costCenters as $cc)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-gray-800">{{ $cc->code }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-gray-800">{{ $cc->name }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-gray-600 text-xs">{{ $cc->description ?: '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $cc->status === 'active' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-gray-50 text-gray-400 border border-gray-100' }}">
                                            {{ $cc->status === 'active' ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" @click="openEditModal({{ $cc }})"
                                                class="p-2 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all border border-blue-50"
                                                title="Editar">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" @click="deleteCostCenter({{ $cc->id }})"
                                                class="p-2 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition-all border border-red-50"
                                                title="Eliminar">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center opacity-30">
                                            <i class="fas fa-folder-open text-5xl mb-4"></i>
                                            <p class="text-sm font-medium">No hay centros de costo registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal Create/Edit --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak
                class="fixed inset-0 bg-gray-500 bg-opacity-75 z-[60] flex items-center justify-center p-4">

                <div @click.away="showModal = false"
                    class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800" x-text="editMode ? 'Editar Centro de Costo' : 'Nuevo Centro de Costo'"></h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form @submit.prevent="saveCostCenter()" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Código</label>
                                <input type="text" x-model="costCenter.code" required
                                    class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Estado</label>
                                <select x-model="costCenter.status" required
                                    class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre</label>
                            <input type="text" x-model="costCenter.name" required
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Descripción</label>
                            <textarea x-model="costCenter.description" rows="3"
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="showModal = false"
                                class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="loading"
                                class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                                <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                                <i class="fas fa-save" x-show="!loading"></i>
                                <span x-text="loading ? 'Guardando...' : (editMode ? 'Actualizar' : 'Crear')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection
</x-adminpanel::layouts.master>
