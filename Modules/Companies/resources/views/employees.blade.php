<x-layouts.company :company="$company" activeTab="employees">
    @section('title', 'Nómina de Empleados - ' . $company->razon_social)

    <div class="max-w-7xl mx-auto" x-data="employeeComponent()">

        {{-- Header con Acciones --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Panel de Control', 'url' => route('companies.dashboard', $company)],
                    ['label' => 'Nómina'],
                    ['label' => 'Nómina de Empleados']
                ]" />
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
                                    <template x-if="emp.user.profile_photo_url">
                                        <img :src="emp.user.profile_photo_url" :alt="`Foto de ${emp.user.name}`"
                                            class="w-8 h-8 rounded-full object-cover border border-gray-200 shadow-sm">
                                    </template>
                                    <template x-if="!emp.user.profile_photo_url">
                                        <div
                                            class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold uppercase">
                                            <span x-text="employeeInitials(emp.user.name)"></span>
                                        </div>
                                    </template>
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
        @if($adminCount === 0)
        <div class="mb-6 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg shadow-sm">
            <div class="flex items-center gap-3">
                <div class="bg-amber-100 p-2 rounded-full">
                    <i class="fas fa-user-shield text-amber-600 text-lg"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-amber-800">No se ha creado un Administrador General</h4>
                    <p class="text-xs text-amber-700 mt-0.5">La empresa no cuenta con un administrador asignado. Esto es necesario para gestionar accesos y configuraciones críticas.</p>
                </div>
                <div>
                    <a href="{{ route('companies.users.index', $company) }}" 
                       class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm gap-2">
                        <i class="fas fa-user-plus"></i>
                        Configurar Administrador
                    </a>
                </div>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Employees Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead
                        class="bg-gray-50 text-gray-600 uppercase text-[10px] font-bold tracking-wider overflow-visible">
                        <tr>
                            <th class="px-6 py-4 text-left overflow-visible">
                                <div class="group relative flex items-center">
                                    <span>Nombre / Email</span>
                                    <div
                                        class="hidden group-hover:block absolute z-50 bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-lg top-full mt-2 left-0 whitespace-nowrap normal-case tracking-normal">
                                        Nombre y correo electrónico del empleado
                                        <div class="absolute -top-1 left-4 w-2 h-2 bg-gray-900 rotate-45"></div>
                                    </div>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-center overflow-visible">
                                <div class="group relative flex items-center justify-center">
                                    <span>Estado</span>
                                    <div
                                        class="hidden group-hover:block absolute z-50 bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-lg top-full mt-2 left-1/2 -translate-x-1/2 whitespace-nowrap normal-case tracking-normal">
                                        Estado actual de la cuenta del usuario
                                        <div
                                            class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45">
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-center overflow-visible">
                                <div class="group relative flex items-center justify-center">
                                    <span>Porcentaje</span>
                                    <div
                                        class="hidden group-hover:block absolute z-50 bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-lg top-full mt-2 left-1/2 -translate-x-1/2 whitespace-nowrap normal-case tracking-normal">
                                        Datos completados para remuneraciones
                                        <div
                                            class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45">
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-right overflow-visible">
                                <div class="group relative flex items-center justify-end">
                                    <span>Acciones</span>
                                    <div
                                        class="hidden group-hover:block absolute z-50 bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-lg top-full mt-2 right-0 whitespace-nowrap normal-case tracking-normal">
                                        Acciones de gestión de empleado
                                        <div class="absolute -top-1 right-4 w-2 h-2 bg-gray-900 rotate-45"></div>
                                    </div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($employees as $emp)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        @if ($emp->user->profile_photo_url)
                                            <img src="{{ $emp->user->profile_photo_url }}"
                                                alt="Foto de {{ $emp->user->name }}"
                                                class="h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm">
                                        @else
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full border border-blue-100 bg-blue-100 text-xs font-bold uppercase text-blue-700 shadow-sm">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($emp->user->name, 0, 2)) }}
                                            </div>
                                        @endif

                                        <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="openPayrollModal({{ $emp->id }})"
                                                class="font-bold text-gray-800 hover:text-blue-600 transition-colors text-left">
                                                {{ $emp->user->name }}
                                            </button>
                                            <button type="button" @click="openPayrollModal({{ $emp->id }})"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-all border border-transparent hover:border-blue-100 self-center"
                                                title="Editar ficha / remuneraciones">
                                                <i class="fas fa-pen-to-square text-[12px]"></i>
                                            </button>
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
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $emp->user->status ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-gray-50 text-gray-400 border border-gray-100' }}">
                                        {{ $emp->user->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 max-w-[80px]">
                                            <div class="h-1.5 rounded-full {{ $emp->completion_percentage > 90 ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.4)]' : 'bg-amber-400' }}"
                                                style="width: {{ $emp->completion_percentage }}%"></div>
                                        </div>
                                        <span
                                            class="text-[10px] font-black {{ $emp->completion_percentage > 90 ? 'text-green-600' : 'text-gray-500' }}">
                                            {{ $emp->completion_percentage }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                            @click="removeEmployee({{ $emp->user->id }}, @js($emp->user->name))"
                                            class="inline-flex items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition-all border border-red-100 bg-red-50/60 text-xs font-semibold"
                                            title="Desvincular empleado">
                                            <i class="fas fa-trash-can text-[11px]"></i>
                                            <span>Desvincular</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-30">
                                        <i class="fas fa-users-slash text-5xl mb-4"></i>
                                        <p class="text-sm font-medium">No hay empleados registrados en esta empresa
                                        </p>
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
                        <input type="text" x-model="newEmployee.name" placeholder="Ej: Juan Pérez"
                            :class="errors.name ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-4 py-2.5 bg-white border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <template x-if="errors.name">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.name[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Correo
                            Electrónico</label>
                        <input type="email" x-model="newEmployee.email" placeholder="juan@ejemplo.com"
                            :class="errors.email ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-4 py-2.5 bg-white border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <template x-if="errors.email">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.email[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Contraseña
                            Inicial</label>
                        <input type="password" x-model="newEmployee.password"
                            :class="errors.password ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-4 py-2.5 bg-white border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <template x-if="errors.password">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.password[0]"></p>
                        </template>
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

    @push('scripts')
        <script>
            window.employeeComponent = function() {
                return {
                    showAddEmployeeModal: false,
                    showPayrollModal: false,
                    payrollLoading: false,
                    activePayrollTab: "personal",
                    loading: false,
                    shouldReloadAfterPayrollSave: false,
                    selectedProfilePhoto: null,
                    selectedProfilePhotoPreview: null,
                    payrollFormFields: [
                        "first_name", "last_name", "rut", "email", "phone", "birth_date", "nationality",
                        "marital_status", "address", "position", "hire_date", "contract_type", "work_schedule",
                        "work_schedule_type", "part_time_hours", "part_time_schedule",
                        "cost_center_id", "afp_id", "isapre_id", "ccaf_id", "health_contribution", "apv_amount",
                        "salary", "salary_type", "num_dependents", "bank_id", "bank_account_number",
                        "bank_account_type", "emergency_contact_name", "emergency_contact_phone", "status", "is_in_payroll"
                    ],
                    afps: @json($afps),
                    isapres: @json($isapres),
                    ccafs: @json($ccafs),
                    bancos: @json($bancos),
                    costCenters: @json($costCenters),
                    isNewEmployee: false,
                    init() {
                        this.$watch("showPayrollModal", value => {
                            if (!value) {
                                this.resetProfilePhotoState();
                            }

                            if (!value && (this.isNewEmployee || this.shouldReloadAfterPayrollSave)) {
                                window.location.reload();
                            }
                        });

                        // Watch check for work_schedule_type to initialize schedule if it becomes part_time
                        this.$watch("selectedEmployee.work_schedule_type", value => {
                            if (value === 'part_time' && (!this.selectedEmployee.part_time_schedule || Object.keys(this.selectedEmployee.part_time_schedule).length === 0)) {
                                this.selectedEmployee.part_time_schedule = this.getDefaultSchedule();
                            }
                        });
                    },
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
                        work_schedule_type: "",
                        part_time_hours: "",
                        part_time_schedule: null,
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
                        is_in_payroll: true,
                        user: {
                            name: "",
                            email: "",
                            profile_photo: null,
                            profile_photo_url: null,
                        }
                    },
                    errors: {},
                    newEmployee: {
                        name: "",
                        email: "",
                        password: ""
                    },
                    employeeInitials(name) {
                        if (!name) return "--";

                        return name
                            .trim()
                            .split(/\s+/)
                            .filter(Boolean)
                            .slice(0, 2)
                            .map(part => part.charAt(0))
                            .join("")
                            .toUpperCase();
                    },
                    getDefaultSchedule() {
                        return {
                            mon: { label: 'Lunes', active: true, start: '09:00', end: '18:30', break: 60 },
                            tue: { label: 'Martes', active: true, start: '09:00', end: '18:30', break: 60 },
                            wed: { label: 'Miércoles', active: true, start: '09:00', end: '18:30', break: 60 },
                            thu: { label: 'Jueves', active: true, start: '09:00', end: '18:30', break: 60 },
                            fri: { label: 'Viernes', active: true, start: '09:00', end: '16:30', break: 60 },
                            sat: { label: 'Sábado', active: false, start: '10:00', end: '14:00', break: 0 },
                            sun: { label: 'Domingo', active: false, start: '10:00', end: '14:00', break: 0 },
                        };
                    },
                    currentEmployeePhotoUrl() {
                        return this.selectedProfilePhotoPreview || this.selectedEmployee.user.profile_photo_url || null;
                    },
                    resetProfilePhotoState() {
                        if (this.selectedProfilePhotoPreview) {
                            URL.revokeObjectURL(this.selectedProfilePhotoPreview);
                        }

                        this.selectedProfilePhoto = null;
                        this.selectedProfilePhotoPreview = null;

                        if (this.$refs.profilePhotoInput) {
                            this.$refs.profilePhotoInput.value = "";
                        }
                    },
                    handleProfilePhotoChange(event) {
                        const [file] = event.target.files || [];

                        this.errors = {
                            ...this.errors,
                            profile_photo: undefined,
                        };

                        if (this.selectedProfilePhotoPreview) {
                            URL.revokeObjectURL(this.selectedProfilePhotoPreview);
                            this.selectedProfilePhotoPreview = null;
                        }

                        this.selectedProfilePhoto = file || null;

                        if (file) {
                            this.selectedProfilePhotoPreview = URL.createObjectURL(file);
                        }
                    },
                    buildPayrollFormData() {
                        const formData = new FormData();
                        formData.append("_method", "PUT");

                        this.payrollFormFields.forEach(field => {
                            if (field === 'part_time_schedule') {
                                if (this.selectedEmployee.work_schedule_type === 'part_time' && this.selectedEmployee.part_time_schedule) {
                                    formData.append(field, JSON.stringify(this.selectedEmployee.part_time_schedule));
                                } else {
                                    formData.append(field, "");
                                }
                            } else {
                                const value = this.selectedEmployee[field];
                                formData.append(field, value ?? "");
                            }
                        });

                        if (this.selectedProfilePhoto) {
                            formData.append("profile_photo", this.selectedProfilePhoto);
                        }

                        return formData;
                    },
                    async addEmployee() {
                        this.loading = true;
                        this.errors = {};
                        try {
                            const response = await axios.post("{{ route('companies.employees.store', $company) }}",
                                this
                                .newEmployee);
                            if (response.data.status === "success") {
                                // Mark as new employee so when modal closes, we reload
                                this.isNewEmployee = true;
                                // Close add modal
                                this.showAddEmployeeModal = false;
                                // Clear form
                                this.newEmployee = {
                                    name: "",
                                    email: "",
                                    password: ""
                                };
                                // Open payroll/details modal immediately
                                // Use a small timeout to ensure transitions don't conflict, though not strictly necessary
                                setTimeout(() => {
                                    this.openPayrollModal(response.data.employee.id);
                                }, 300);
                            }
                        } catch (error) {
                            console.error(error);
                            if (error.response?.status === 422) {
                                this.errors = Object.assign({}, error.response.data.errors);
                            } else {
                                toast(error.response?.data?.message || "Error al crear empleado", "error");
                            }
                        } finally {
                            this.loading = false;
                        }
                    },
                    async removeEmployee(userId, employeeName = "este empleado") {
                        if (!confirm(`¿Seguro que deseas desvincular a ${employeeName}?`)) return;
                        try {
                            const url = "{{ route('companies.employees.destroy', [$company, ':id']) }}".replace(
                                ":id",
                                userId);
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
                        this.errors = {};
                        try {
                            const url = "{{ route('companies.employees.payroll', [$company, ':id']) }}"
                                .replace(":id",
                                    employeeId);
                            const response = await axios.get(url);
                            if (response.data.status === "success") {
                                this.errors = {}; // Reset errors before loading new data
                                this.selectedEmployee = response.data.employee;
                                // Robust check: null, empty array, or empty object
                                if (!this.selectedEmployee.part_time_schedule || 
                                    (Array.isArray(this.selectedEmployee.part_time_schedule) && this.selectedEmployee.part_time_schedule.length === 0) ||
                                    (typeof this.selectedEmployee.part_time_schedule === 'object' && Object.keys(this.selectedEmployee.part_time_schedule).length === 0)) {
                                    this.selectedEmployee.part_time_schedule = this.getDefaultSchedule();
                                }
                                this.shouldReloadAfterPayrollSave = false;
                                this.resetProfilePhotoState();
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
                            const response = await axios.get(
                                "{{ route('companies.employees.search', $company) }}", {
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
                        this.errors = {};
                        try {
                            const url = "{{ route('companies.employees.payroll.update', [$company, ':id']) }}"
                                .replace(
                                    ":id", this.selectedEmployee.id);
                            const response = await axios.post(url, this.buildPayrollFormData(), {
                                headers: {
                                    Accept: "application/json",
                                }
                            });
                            if (response.data.status === "success") {
                                toast(response.data.message || "Datos de nómina actualizados", "success");
                                if (response.data.employee) {
                                    this.selectedEmployee = response.data.employee;
                                }
                                this.shouldReloadAfterPayrollSave = true;
                                this.resetProfilePhotoState();
                            }
                        } catch (error) {
                            console.error(error);
                            if (error.response?.status === 422) {
                                // Explicitly update the errors object to trigger reactivity
                                this.errors = Object.assign({}, error.response.data.errors);
                                console.log("Validation Errors:", this.errors);
                                toast("Hay errores de validación en el formulario", "error");
                            } else {
                                toast("Error al actualizar datos de nómina", "error");
                            }
                        } finally {
                            this.payrollLoading = false;
                        }
                    },
                    sectionFields: {
                        personal: ['first_name', 'last_name', 'rut', 'birth_date', 'email', 'phone', 'nationality', 'marital_status', 'address', 'profile_photo'],
                        laboral: ['position', 'hire_date', 'contract_type', 'work_schedule_type', 'part_time_hours', 'cost_center_id'],
                        prevision: ['afp_id', 'isapre_id', 'ccaf_id', 'health_contribution', 'apv_amount'],
                        remuneracion: ['salary', 'salary_type', 'num_dependents'],
                        banco: ['bank_id', 'bank_account_number', 'bank_account_type'],
                        emergencia: ['emergency_contact_name', 'emergency_contact_phone']
                    },
                    hasSectionErrors(section) {
                        const fields = this.sectionFields[section] || [];
                        return fields.some(field => this.errors && this.errors[field] && this.errors[field].length > 0);
                    }
                }
            }
        </script>
    @endpush
</x-layouts.company>
