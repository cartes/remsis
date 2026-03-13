<x-layouts.company :company="$company" activeTab="honorarios">
    @section('title', 'Colaboradores a Honorarios - ' . $company->razon_social)

    <div class="max-w-7xl mx-auto" x-data="freelancersComponent()">

        {{-- Header con Acciones --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Colaboradores a Honorarios</h2>
                <p class="text-sm text-gray-500 mt-1">Gestión de prestadores de servicios a honorarios y sus boletas.</p>
            </div>
            <div class="flex items-center gap-4">
                {{-- Buscador --}}
                <div class="relative" @click.away="showSearchResults = false">
                    <div class="relative">
                        <input type="text" x-model="searchTerm" @input.debounce.300ms="fetchFreelancersSearch()"
                            placeholder="Buscar colaborador..."
                            class="w-64 pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-xs"></i>
                        </div>
                    </div>

                    {{-- Resultados de búsqueda --}}
                    <div x-show="showSearchResults && searchResults.length > 0" x-cloak
                        class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-xl shadow-xl z-50 overflow-hidden">
                        <div class="max-h-60 overflow-y-auto">
                            <template x-for="freelancer in searchResults" :key="freelancer.id">
                                <button @click="openDetailsModal(freelancer.id); showSearchResults = false; searchTerm = ''"
                                    class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors flex items-center gap-3 border-b border-gray-50 last:border-0">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold uppercase">
                                        <span x-text="initials(freelancer.first_name + ' ' + freelancer.last_name)"></span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-sm font-bold text-gray-800 truncate"
                                            x-text="freelancer.first_name + ' ' + freelancer.last_name"></span>
                                        <span class="text-[11px] text-gray-500 truncate" x-text="freelancer.rut"></span>
                                    </div>
                                    <i class="fas fa-chevron-right ml-auto text-gray-300 text-[10px]"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- No se encontraron resultados --}}
                    <div x-show="showSearchResults && searchResults.length === 0 && searchTerm.length >= 3" x-cloak
                        class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-xl shadow-xl z-50 p-4 text-center">
                        <p class="text-sm text-gray-400">No se encontraron prestadores</p>
                    </div>
                </div>

                <button type="button" @click="openCreateModal()"
                    class="bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-sm flex items-center gap-2 text-sm font-semibold whitespace-nowrap">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Prestador</span>
                </button>
            </div>
        </div>

        {{-- Tabla de Freelancers --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Prestador / RUT</th>
                            <th class="px-6 py-4 text-center">Profesión / Servicio</th>
                            <th class="px-6 py-4 text-center">Tasa Retención</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($freelancers as $f)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full border border-indigo-100 bg-indigo-50 text-xs font-bold uppercase text-indigo-700 shadow-sm">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($f->first_name, 0, 1) . \Illuminate\Support\Str::substr($f->last_name, 0, 1)) }}
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="openDetailsModal({{ $f->id }})"
                                                    class="font-bold text-gray-800 hover:text-blue-600 transition-colors text-left">
                                                    {{ $f->full_name }}
                                                </button>
                                                <button type="button" @click="openDetailsModal({{ $f->id }})"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-all border border-transparent hover:border-blue-100 self-center"
                                                    title="Editar ficha">
                                                    <i class="fas fa-pen-to-square text-[12px]"></i>
                                                </button>
                                            </div>
                                            <span class="text-[11px] text-gray-500 font-mono mt-0.5">{{ $f->rut }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-700">{{ $f->profession ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-bold">{{ number_format($f->default_retention_rate, 2) }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $f->is_active ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-gray-50 text-gray-400 border border-gray-100' }}">
                                        {{ $f->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" @click="removeFreelancer({{ $f->id }}, '{{ $f->full_name }}')"
                                        class="inline-flex items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition-all border border-red-100 bg-red-50/60 text-xs font-semibold"
                                        title="Eliminar prestador">
                                        <i class="fas fa-trash-can text-[11px]"></i>
                                        <span>Eliminar</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-30">
                                        <i class="fas fa-user-tag text-5xl mb-4 text-indigo-600"></i>
                                        <p class="text-sm font-medium">No hay colaboradores a honorarios registrados</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @include('payroll::freelancers.partials.details_modal')
    </div>

    @push('scripts')
        <script>
            window.freelancersComponent = function() {
                return {
                    showDetailsModal: false,
                    modalLoading: false,
                    loading: false,
                    activeTab: "personal", // personal, boletas
                    banks: @json($banks),
                    searchTerm: "",
                    searchResults: [],
                    showSearchResults: false,
                    isEditing: false, // true = editando existente, false = nuevo prestador
                    
                    form: {
                        id: null,
                        first_name: "",
                        last_name: "",
                        rut: "",
                        email: "",
                        phone: "",
                        address: "",
                        profession: "",
                        bank_id: "",
                        bank_account_number: "",
                        bank_account_type: "",
                        default_gross_fee: "",
                        default_retention_rate: 13.75,
                        is_active: true,
                        receipts: []
                    },
                    
                    // Estado para el sub-formulario de Boletas
                    showReceiptForm: false,
                    receiptFormLoading: false,
                    receiptForm: {
                        id: null,
                        receipt_number: "",
                        issue_date: new Date().toISOString().split('T')[0],
                        issuer: "freelancer",
                        gross_amount: "",
                        retention_amount: "",
                        net_amount: "",
                        status: "pending"
                    },

                    errors: {},
                    receiptErrors: {},

                    initials(name) {
                        return name.trim().split(/\s+/).slice(0, 2).map(p => p.charAt(0)).join("").toUpperCase();
                    },

                    // Búsqueda
                    async fetchFreelancersSearch() {
                        if (this.searchTerm.length < 3) {
                            this.searchResults = [];
                            this.showSearchResults = false;
                            return;
                        }
                        try {
                            const res = await axios.get("{{ route('companies.freelancers.search', $company) }}", { params: { query: this.searchTerm }});
                            this.searchResults = res.data;
                            this.showSearchResults = true;
                        } catch (e) {
                            console.error(e);
                        }
                    },

                    // Modales principales
                    openCreateModal() {
                        this.isEditing = false;
                        this.activeTab = "personal";
                        this.errors = {};
                        this.form = {
                            id: null,
                            first_name: "",
                            last_name: "",
                            rut: "",
                            email: "",
                            phone: "",
                            address: "",
                            profession: "",
                            bank_id: "",
                            bank_account_number: "",
                            bank_account_type: "",
                            default_gross_fee: "",
                            default_retention_rate: 13.75,
                            is_active: true,
                            receipts: []
                        };
                        this.showDetailsModal = true;
                    },

                    async openDetailsModal(id) {
                        this.isEditing = true;
                        this.activeTab = "personal";
                        this.modalLoading = true;
                        this.errors = {};
                        try {
                            const res = await axios.get(`{{ url('companies') }}/{{ $company->id }}/honorarios/${id}`);
                            this.form = res.data.freelancer;
                            this.showDetailsModal = true;
                        } catch (error) {
                            toast("Error al cargar la ficha.", "error");
                        } finally {
                            this.modalLoading = false;
                        }
                    },

                    // Guardar Ficha
                    async saveFreelancer() {
                        this.loading = true;
                        this.errors = {};
                        try {
                            let response;
                            if (this.isEditing) {
                                response = await axios.put(`{{ url('companies') }}/{{ $company->id }}/honorarios/${this.form.id}`, this.form);
                            } else {
                                response = await axios.post("{{ route('companies.freelancers.store', $company) }}", this.form);
                            }
                            
                            if (response.data.status === "success") {
                                toast(response.data.message, "success");
                                setTimeout(() => window.location.reload(), 1000);
                            }
                        } catch (error) {
                            if (error.response?.status === 422) {
                                this.errors = error.response.data.errors;
                                toast("Hay errores de validación", "error");
                            } else {
                                toast("Error al guardar.", "error");
                            }
                        } finally {
                            this.loading = false;
                        }
                    },

                    // Eliminar Freelancer
                    async removeFreelancer(id, name) {
                        if (!confirm(`¿Seguro que deseas eliminar a ${name}? Esto eliminará todas sus boletas.`)) return;
                        try {
                            const res = await axios.delete(`{{ url('companies') }}/{{ $company->id }}/honorarios/${id}`);
                            if (res.data.status === "success") {
                                window.location.reload();
                            }
                        } catch (e) {
                            toast("Error al eliminar.", "error");
                        }
                    },

                    // ------ BOLETAS LOGIC ------ //
                    
                    openAddReceipt() {
                        this.showReceiptForm = true;
                        this.receiptErrors = {};
                        this.receiptForm = {
                            id: null,
                            receipt_number: "",
                            issue_date: new Date().toISOString().split('T')[0],
                            issuer: "freelancer",
                            gross_amount: this.form.default_gross_fee || "",
                            retention_amount: "",
                            net_amount: "",
                            status: "pending"
                        };
                        this.calculateAmounts('gross');
                    },
                    
                    editReceipt(idx) {
                        const receipt = this.form.receipts[idx];
                        this.showReceiptForm = true;
                        this.receiptErrors = {};
                        this.receiptForm = { ...receipt };
                    },
                    
                    cancelReceipt() {
                        this.showReceiptForm = false;
                    },

                    // Autocálculo Bruto/Líquido
                    calculateAmounts(changedField) {
                        const rate = parseFloat(this.form.default_retention_rate) / 100; // Ej: 13.75% = 0.1375
                        
                        // Si cambia el bruto
                        if (changedField === 'gross') {
                            const gross = parseFloat(this.receiptForm.gross_amount) || 0;
                            const retention = gross * rate;
                            const net = gross - retention;
                            
                            this.receiptForm.retention_amount = Math.round(retention);
                            this.receiptForm.net_amount = Math.round(net);
                        } 
                        // Si cambia el líquido
                        else if (changedField === 'net') {
                            const net = parseFloat(this.receiptForm.net_amount) || 0;
                            const gross = net / (1 - rate);
                            const retention = gross - net;
                            
                            this.receiptForm.gross_amount = Math.round(gross);
                            this.receiptForm.retention_amount = Math.round(retention);
                        }
                    },
                    
                    formatMoney(value) {
                        if (!value) return '$0';
                        return '$' + parseFloat(value).toLocaleString('es-CL');
                    },
                    
                    formatDate(dateStr) {
                        if (!dateStr) return '';
                        return new Date(dateStr).toLocaleDateString('es-CL');
                    },

                    async saveReceipt() {
                        this.receiptFormLoading = true;
                        this.receiptErrors = {};
                        try {
                            let url = `{{ url('companies') }}/{{ $company->id }}/honorarios/${this.form.id}/receipts`;
                            let response;
                            
                            if (this.receiptForm.id) {
                                response = await axios.put(`${url}/${this.receiptForm.id}`, this.receiptForm);
                            } else {
                                response = await axios.post(url, this.receiptForm);
                            }
                            
                            if (response.data.status === "success") {
                                toast(response.data.message, "success");
                                this.showReceiptForm = false;
                                // Reload freelancer data to update the receipts table
                                this.openDetailsModal(this.form.id);
                            }
                        } catch (error) {
                            if (error.response?.status === 422) {
                                this.receiptErrors = error.response.data.errors;
                                toast("Error en los datos de la boleta.", "error");
                            } else {
                                toast("Error al guardar boleta.", "error");
                            }
                        } finally {
                            this.receiptFormLoading = false;
                        }
                    },
                    
                    async deleteReceipt(receiptId) {
                        if(!confirm("¿Deseas eliminar esta boleta de honorario?")) return;
                        
                        try {
                            const url = `{{ url('companies') }}/{{ $company->id }}/honorarios/${this.form.id}/receipts/${receiptId}`;
                            const res = await axios.delete(url);
                            if (res.data.status === "success") {
                                toast("Boleta eliminada.", "success");
                                this.openDetailsModal(this.form.id);
                            }
                        } catch (e) {
                            toast("Error al eliminar boleta.", "error");
                        }
                    }
                }
            }
        </script>
    @endpush
</x-layouts.company>
