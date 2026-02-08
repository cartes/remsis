<x-layouts.company :company="$company" activeTab="employees">
    @section('title', 'Nómina de Empleados - ' . $company->razon_social)

    <div class="max-w-7xl mx-auto"
        x-data='{
        showAddEmployeeModal: false,
        showPayrollModal: false,
        payrollLoading: false,
        activePayrollTab: "personal",
        loading: false,
        afps: @json($afps),
        isapres: @json($isapres),
        ccafs: @json($ccafs),
        bancos: @json($bancos),
        costCenters: @json($costCenters),
        selectedEmployee: {
            id: null,
            first_name: "",
            last_name: "",
            rut: "",
            email: "",
            phone: "",
            position: "",
            birth_date: "",
            nationality: "",
            marital_status: "",
            num_dependents: 0,
            hire_date: "",
            work_schedule: "",
            cost_center_id: "",
            afp_id: "",
            isapre_id: "",
            ccaf_id: "",
            health_contribution: "",
            apv_amount: "",
            salary: "",
            salary_type: "",
            contract_type: "",
            status: "",
            bank_id: "",
            bank_account_number: "",
            bank_account_type: "",
            emergency_contact_name: "",
            emergency_contact_phone: "",
            address: "",
            user: { name: "", email: "" }
            },
            newEmployee: { name: "", email: "", password: "" },
            async addEmployee() {
                this.loading = true;
                try {
                    const response = await axios.post("{{ route('companies.employees.store', $company) }}", this.newEmployee);
                    if (response.data.status === "success") {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error(error);
                    toast(error.response?.data?.message || "Error al crear empleado", "error");
                } finally {
                    this.loading = false;
                }
            },
            async removeEmployee(userId) {
                if (!confirm("¿Seguro que deseas desvincular a este empleado?")) return;
                try {
                    const url = "{{ route('companies.employees.destroy', [$company, ':id']) }}".replace(":id", userId);
                    const response = await axios.delete(url);
                    if (response.data.status === "success") {
                        window.location.reload();
                    }
                } catch (error) {
                    toast("Error al desvincular empleado", "error");
                }
            },
            async openPayrollModal(employeeId) {
                this.payrollLoading = true;
                this.activePayrollTab = "personal";
                try {
                    const url = "{{ route('companies.employees.payroll', [$company, ':id']) }}".replace(":id", employeeId);
                    const response = await axios.get(url);
                    if (response.data.status === "success") {
                        this.selectedEmployee = response.data.employee;
                        this.showPayrollModal = true;
                    }
                } catch (error) {
                    toast("Error al cargar datos de nómina", "error");
                } finally {
                    this.payrollLoading = false;
                }
            },
            searchTerm: "",
            searchResults: [],
            showSearchResults: false,
            async fetchEmployeesSearch() {
                if (this.searchTerm.length < 3) {
                    this.searchResults = [];
                    this.showSearchResults = false;
                    return;
                }
                try {
                    const response = await axios.get("{{ route('companies.employees.search', $company) }}", {
                        params: {
                            query: this.searchTerm
                        }
                    });
                    this.searchResults = response.data;
                    this.showSearchResults = true;
                } catch (error) {
                    console.error("Error searching employees:", error);
                }
            },
            async updatePayroll() {
            this.payrollLoading = true;
            try {
                const url = "{{ route('companies.employees.payroll.update', [$company, ':id']) }}".replace(":id", this.selectedEmployee.id);
                const response = await axios.put(url, this.selectedEmployee);
                if (response.data.status === "success") {
                    toast(response.data.message || "Datos de nómina actualizados", "success");
                    if (response.data.employee) {
                        this.selectedEmployee = response.data.employee;
                    }
                }
            } catch (error) {
                console.error(error);
                toast("Error al actualizar datos de nómina", "error");
            } finally {
                this.payrollLoading = false;
            }
        }
    }'>

        {{-- Header con Acciones --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Nómina de Empleados</h2>
                <p class="text-sm text-gray-500 mt-1">Gestión de empleados vinculados a esta empresa.</p>
            </div>
            <div class="flex items-center gap-4">
                {{-- Buscador de Empleados --}}
                <div class="relative" @click.away="showSearchResults = false">
                    <div class="relative">
                        <input type="text" x-model="searchTerm" @input.debounce.300ms="fetchEmployeesSearch()"
                            placeholder="Buscar empleado..."
                            class="w-64 pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-xs"></i>
                        </div>
                    </div>

                    {{-- Resultados de búsqueda --}}
                    <div x-show="showSearchResults && searchResults.length > 0" x-cloak
                        class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-xl shadow-xl z-50 overflow-hidden">
                        <div class="max-h-60 overflow-y-auto">
                            <template x-for="emp in searchResults" :key="emp.id">
                                <button @click="openPayrollModal(emp.id); showSearchResults = false; searchTerm = ''"
                                    class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors flex items-center gap-3 border-b border-gray-50 last:border-0">
                                    <div
                                        class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold uppercase">
                                        <span x-text="emp.user.name.substring(0,2)"></span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-sm font-bold text-gray-800 truncate"
                                            x-text="emp.user.name"></span>
                                        <span class="text-[11px] text-gray-500 truncate" x-text="emp.user.email"></span>
                                    </div>
                                    <i class="fas fa-chevron-right ml-auto text-gray-300 text-[10px]"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- No se encontraron resultados --}}
                    <div x-show="showSearchResults && searchResults.length === 0 && searchTerm.length >= 3" x-cloak
                        class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-xl shadow-xl z-50 p-4 text-center">
                        <p class="text-sm text-gray-400">No se encontraron empleados</p>
                    </div>
                </div>

                <button type="button" @click="showAddEmployeeModal = true"
                    class="bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-sm flex items-center gap-2 text-sm font-semibold whitespace-nowrap">
                    <i class="fas fa-user-plus"></i>
                    <span>Nuevo Empleado</span>
                </button>
            </div>
        </div>

        {{-- Employees Table Section --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">

            {{-- Employees Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Nombre / Email</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($employees as $emp)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800">{{ $emp->user->name }}</span>
                                            <div class="flex gap-1">
                                                @foreach ($emp->user->roles as $role)
                                                    <span
                                                        class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[9px] font-bold uppercase border border-blue-100">
                                                        {{ str_replace('-', ' ', $role->name) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <span class="text-[11px] text-gray-500 mt-0.5">{{ $emp->user->email }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $emp->user->status ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-gray-50 text-gray-400 border border-gray-100' }}">
                                        {{ $emp->user->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="openPayrollModal({{ $emp->id }})"
                                            class="p-2 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all border border-blue-50"
                                            title="Ver Ficha / Remuneraciones">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" @click="removeEmployee({{ $emp->user->id }})"
                                            class="p-2 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition-all border border-red-50"
                                            title="Eliminar / Desvincular">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-30">
                                        <i class="fas fa-users-slash text-5xl mb-4"></i>
                                        <p class="text-sm font-medium">No hay empleados registrados en esta empresa</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add Employee Modal --}}
        <div x-show="showAddEmployeeModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-cloak
            class="fixed inset-0 bg-gray-500 bg-opacity-75 z-[60] flex items-center justify-center p-4">

            <div @click.away="showAddEmployeeModal = false"
                class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Agregar Nuevo Empleado</h3>
                    <button @click="showAddEmployeeModal = false"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form @submit.prevent="addEmployee" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre
                            Completo</label>
                        <input type="text" x-model="newEmployee.name" required placeholder="Ej: Juan Pérez"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Correo
                            Electrónico</label>
                        <input type="email" x-model="newEmployee.email" required placeholder="juan@ejemplo.com"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Contraseña
                            Inicial</label>
                        <input type="password" x-model="newEmployee.password" required
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" @click="showAddEmployeeModal = false"
                            class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="loading"
                            class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                            <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                            <i class="fas fa-user-plus" x-show="!loading"></i>
                            <span x-text="loading ? 'Procesando...' : 'Crear Empleado'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @include('companies::partials.payroll_modal')
    </div>
</x-layouts.company>
