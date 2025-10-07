<x-adminpanel::layouts.master>
    @section('title', 'Gestión de Usuarios')

    @section('content')
        <div x-data="useEditor()">

            {{-- Toast de éxito --}}
            <div x-show="toast.visible" x-cloak x-transition
                class="fixed top-5 right-5 bg-green-500 text-white px-4 py-3 rounded shadow-lg z-[9999] flex items-center space-x-2">
                <span x-text="toast.message"></span>
                <button @click="toast.visible = false" class="ml-2 text-white font-bold">&times;</button>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Usuarios del sistema</h2>

                <div class="flex items-center gap-3">
                    {{-- Selector de empresas --}}
                    <form method="GET" action="{{ route('users.index') }}" id="companyFilterForm">
                        <select name="company_id" class="border rounded px-3 py-2 text-sm min-w-[200px]"
                            onchange="document.getElementById('companyFilterForm').submit()">
                            <option value="">Todas las empresas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" {{ $companyId == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    {{-- Botón Crear usuario --}}
                    <button @click="open = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        + Crear nuevo usuario
                    </button>
                </div>
            </div>

            {{-- Tabla dinámica de usuarios --}}
            <div class="bg-white shadow rounded overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-left">
                        <tr>
                            <th class="px-4 py-2">Nombre</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Rol</th>
                            <th class="px-4 py-2">Empresa</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="user in users" :key="user.id">
                            <tr>
                                <td class="px-4 py-2" :class="user.status ? 'text-gray-800' : 'text-gray-300'">
                                    <a :href="(user.roles.some(r => r.name === 'employee') && user.employee) ?
                                    '/payroll/employee/' + user.employee.id: null"
                                        :class="{
                                            'text-blue-600 hover:text-blue-800 hover:underline font-medium cursor-pointer transition-colors duration-200': user
                                                .roles.some(r => r.name === 'employee') && user.employee,
                                            'text-inherit cursor-default':
                                                !(user.roles.some(r => r.name === 'employee') && user.employee)
                                        }"
                                        x-text="user.name">
                                    </a>
                                </td>
                                <td class="px-4 py-2" x-text="user.email"
                                    :class="user.status ? 'text-gray-800' : 'text-gray-300'"></td>
                                <td class="px-4 py-2" x-text="user.roles.map(r => r.name).join(', ')"
                                    :class="user.status ? 'text-gray-800' : 'text-gray-300'"></td>
                                <td class="px-4 py-2">
                                    <!-- Solo admin o employee pueden vincular/cambiar empresa -->
                                    <template
                                        x-if="user.roles.some(r => ['admin','employee', 'contador'].includes(r.name))">
                                        <div>
                                            <template x-if="user?.employee?.company">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                                        <span x-text="user.employee.company.name"></span>
                                                    </span>
                                                    <!-- Botón cambiar (opcional) -->
                                                    <button class="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-gray-200"
                                                        @click.prevent="openCompanyModal(user)">Cambiar</button>
                                                </div>
                                            </template>

                                            <template x-if="!user?.employee?.company">
                                                <button class="text-blue-600 underline hover:no-underline"
                                                    @click.prevent="openCompanyModal(user)">
                                                    Vincular a empresa
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Para otros roles, no mostrar acción -->
                                    <template
                                        x-if="!user.roles.some(r => ['admin','employee', 'contador'].includes(r.name))">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>
                                <td class="px-4 py-2">
                                    <!-- Si es super-admin, mostrar texto fijo -->
                                    <template x-if="user.roles.some(r => r.name === 'super-admin')">
                                        <span class="text-xs font-semibold text-green-700 bg-green-100 px-2 py-1 rounded">
                                            Siempre activo
                                        </span>
                                    </template>

                                    <!-- Si NO es super-admin, mostrar switch -->
                                    <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                        <button :title="user.status ? 'Desactivar' : 'Activar'"
                                            @click="toggleStatus(user.id)"
                                            class="p-3 w-5 h-5 flex items-center justify-center rounded-sm transition"
                                            :class="user.status ?
                                                'text-green-600 hover:text-green-800' :
                                                'text-gray-400 hover:text-gray-900'">
                                            <i
                                                :class="[user.status ? 'fas fa-toggle-on' : 'fas fa-toggle-off', 'fa-2x']"></i>
                                        </button>
                                    </template>

                                </td>
                                {{-- Acciones --}}
                                <td class="px-4 py-2">
                                    <div class="flex space-x-2">
                                        {{-- Botón de Remuneraciones solo para employees --}}
                                        <template x-if="user.roles.some(r => r.name === 'employee') && user.employee">
                                            <a :href="'/payroll/employee/' + user.employee.id" title="Remuneraciones"
                                                class="p-3 w-5 h-5 flex items-center justify-center bg-green-100 hover:bg-green-200 text-green-600 hover:text-green-800 rounded-sm transition"
                                                aria-label="Remuneraciones">
                                                <i class="fas fa-dollar-sign text-xs"></i>
                                            </a>
                                        </template>

                                        <!-- Botón Editar -->
                                        <button @click="edit(user.id)" title="Editar"
                                            class="p-3 w-5 h-5 flex items-center justify-center bg-blue-100 hover:bg-blue-200 text-blue-600 hover:text-blue-800 rounded-sm transition"
                                            aria-label="Editar">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>

                                        <!-- Botón Eliminar -->
                                        <button @click="remove(user.id)" title="Eliminar"
                                            class="p-3 w-5 h-5 flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-600 hover:text-red-800 rounded-sm transition"
                                            aria-label="Eliminar">
                                            <i class="fas fa-times text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Modal Crear --}}
            <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <button @click="open = false" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
                    <h2 class="text-xl font-semibold mb-4">Nuevo usuario</h2>

                    <form @submit.prevent="store">
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Nombre</label>
                            <input type="text" x-model="form.name" required
                                class="w-full mt-1 border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Correo electrónico</label>
                            <input type="email" x-model="form.email" required
                                class="w-full mt-1 border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Contraseña</label>
                            <input type="password" x-model="form.password" required
                                class="w-full mt-1 border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Rol</label>
                            <select x-model="form.role" class="w-full mt-1 border-gray-300 rounded px-3 py-2">
                                <option value="">Selecciona un rol</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                Crear usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Modal Editar --}}
            <div x-show="editModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <button @click="editModal = false"
                        class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
                    <h2 class="text-xl font-semibold mb-4">Editar usuario</h2>

                    <form @submit.prevent="update">
                        <div class="mb-4">
                            <label class="block text-sm">Nombre</label>
                            <input type="text" x-model="form.name" class="w-full border px-3 py-2 rounded">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm">Email</label>
                            <input type="email" x-model="form.email" class="w-full border px-3 py-2 rounded">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm">Rol</label>
                            <select x-model="form.role" class="w-full border px-3 py-2 rounded">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Modal Vincular Empresa --}}
            <div x-show="companyModal.open" x-cloak
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative">
                    <button @click="closeCompanyModal"
                        class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
                    <h2 class="text-xl font-semibold mb-4">
                        Vincular empresa a <span class="font-bold" x-text="companyModal.user?.name ?? ''"></span>
                    </h2>

                    <div class="mb-3">
                        <input type="text" x-model.debounce.400ms="companySearch" @input="fetchCompanies(1)"
                            placeholder="Buscar por nombre o RUT..."
                            class="w-full border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">
                    </div>

                    <div class="border rounded divide-y max-h-80 overflow-auto" x-show="companies.length">
                        <template x-for="c in companies" :key="c.id">
                            <button @click="attachCompany(c)"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center justify-between">
                                <div>
                                    <div class="font-medium" x-text="c.name"></div>
                                    <div class="text-xs text-gray-500" x-text="c.rut ?? ''"></div>
                                </div>
                                <span class="text-sm text-blue-600">Seleccionar</span>
                            </button>
                        </template>
                    </div>

                    <div class="py-3 text-center text-sm text-gray-500" x-show="companyLoading">Cargando...</div>
                    <div class="py-3 text-center text-sm text-gray-500" x-show="!companyLoading && !companies.length">Sin
                        resultados</div>

                    <div class="mt-3 flex items-center justify-between">
                        <div class="text-sm">Página <span x-text="companyMeta.current_page"></span> de <span
                                x-text="companyMeta.last_page"></span></div>
                        <div class="space-x-2">
                            <button class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200"
                                :disabled="companyMeta.current_page <= 1"
                                @click="fetchCompanies(companyMeta.current_page - 1)">
                                Anterior
                            </button>
                            <button class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200"
                                :disabled="companyMeta.current_page >= companyMeta.last_page"
                                @click="fetchCompanies(companyMeta.current_page + 1)">
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        @push('scripts')
            <script>
                const routes = {
                    store: "{{ route('users.store') }}",
                    update: "{{ route('users.update', ':id') }}",
                    destroy: "{{ route('users.destroy', ':id') }}",
                    toggleStatus: "{{ route('users.toggleStatus', ':id') }}",
                    companiesIndex: "{{ route('admin.companies.index') }}",
                    attachCompany: "{{ route('users.attach-company', ':id') }}",
                };

                function useEditor() {
                    return {
                        open: false,
                        editModal: false,
                        toast: {
                            visible: false,
                            message: '',
                            timeout: null,
                        },
                        form: {
                            id: null,
                            name: '',
                            email: '',
                            role: '',
                            password: '',
                        },
                        users: @json($users),
                        companyModal: {
                            open: false,
                            user: null
                        },
                        companySearch: '',
                        companyLoading: false,
                        companies: [],
                        companyMeta: {
                            current_page: 1,
                            last_page: 1
                        },

                        openCompanyModal(user) {
                            if (!user || typeof user.id === 'undefined') {
                                console.warn('openCompanyModal: user inválido', user);
                                this.showToast('Usuario inválido');
                                return;
                            }
                            this.companyModal.user = {
                                id: user.id,
                                name: user.name ?? ''
                            };
                            this.companyModal.open = true;
                            this.companySearch = '';
                            this.fetchCompanies(1);
                        },

                        closeCompanyModal() {
                            this.companyModal.open = false;
                            this.companyModal.user = null;
                            this.companySearch = '';
                            this.companies = [];
                            this.companyMeta = {
                                current_page: 1,
                                last_page: 1
                            };
                        },

                        resetForm() {
                            this.form = {
                                id: null,
                                name: '',
                                email: '',
                                role: '',
                                password: '',
                            };
                        },

                        showToast(message) {
                            this.toast.message = message;
                            this.toast.visible = true;
                            if (this.toast.timeout) clearTimeout(this.toast.timeout);
                            this.toast.timeout = setTimeout(() => {
                                this.toast.visible = false;
                            }, 4000);
                        },

                        async toggleStatus(id) {
                            try {
                                const response = await axios.put(routes.toggleStatus.replace(':id', id));
                                const index = this.users.findIndex(u => u.id === id);
                                if (index !== -1) {
                                    this.users[index].status = response.data.status;
                                }
                                this.showToast('Estado actualizado correctamente.');
                            } catch (error) {
                                console.error('Error al cambiar estado', error);
                                alert('Error al cambiar estado del usuario.');
                            }
                        },

                        async store() {
                            try {
                                const response = await axios.post(routes.store, {
                                    name: this.form.name,
                                    email: this.form.email,
                                    password: this.form.password,
                                    role: this.form.role,
                                });

                                this.users.push(response.data.user);
                                this.open = false;
                                this.resetForm();
                                this.showToast('Usuario creado exitosamente.');
                            } catch (error) {
                                console.error('Error al crear el usuario', error);
                                alert('Error al crear usuario.');
                            }
                        },

                        edit(id) {
                            const user = this.users.find(u => u.id === id);
                            if (user) {
                                this.form.id = user.id;
                                this.form.name = user.name;
                                this.form.email = user.email;
                                this.form.role = user.roles.length > 0 ? user.roles[0].name : '';
                                this.editModal = true;
                            }
                        },

                        async update() {
                            try {
                                const response = await axios.put(routes.update.replace(':id', this.form.id), {
                                    name: this.form.name,
                                    email: this.form.email,
                                    role: this.form.role,
                                });

                                const index = this.users.findIndex(u => u.id === this.form.id);
                                if (index !== -1) {
                                    this.users[index].name = this.form.name;
                                    this.users[index].email = this.form.email;
                                    this.users[index].roles = [{
                                        name: this.form.role
                                    }];
                                }

                                this.editModal = false;
                                this.resetForm();
                                this.showToast('Usuario actualizado correctamente.');
                            } catch (error) {
                                console.error('Error al actualizar el usuario', error);
                                alert('Error al actualizar usuario.');
                            }
                        },

                        async remove(id) {
                            if (!confirm('¿Eliminar este usuario?')) return;

                            try {
                                await axios.delete(routes.destroy.replace(':id', id));
                                this.users = this.users.filter(u => u.id !== id);
                                this.showToast('Usuario eliminado correctamente.');
                            } catch (error) {
                                console.error('Error al eliminar usuario', error);
                                alert('Error al eliminar usuario.');
                            }
                        },

                        async fetchCompanies(page = 1) {
                            try {
                                this.companyLoading = true;
                                const {
                                    data
                                } = await axios.get(routes.companiesIndex, {
                                    params: {
                                        search: this.companySearch,
                                        page
                                    }
                                });
                                this.companies = data.data;
                                this.companyMeta = data.meta;
                            } catch (e) {
                                console.error(e);
                                this.showToast('Error cargando empresas');
                            } finally {
                                this.companyLoading = false;
                            }
                        },

                        async attachCompany(company) {
                            // Validaciones ANTES de tocar .id
                            if (!company || typeof company.id === 'undefined') {
                                console.warn('attachCompany: empresa inválida', company);
                                this.showToast('Empresa inválida');
                                return;
                            }
                            if (!this.companyModal || !this.companyModal.user || typeof this.companyModal.user.id ===
                                'undefined') {
                                console.warn('attachCompany: user no seteado', this.companyModal);
                                this.showToast('Usuario no seleccionado');
                                return;
                            }

                            try {
                                const url = routes.attachCompany.replace(':id', this.companyModal.user.id);
                                const res = await axios.post(url, {
                                    company_id: company.id
                                });

                                const idx = this.users.findIndex(u => u.id === this.companyModal.user.id);
                                if (idx !== -1) {
                                    if (!this.users[idx].employee) this.users[idx].employee = {};
                                    this.users[idx].employee.company = {
                                        id: res.data?.company?.id ?? company.id,
                                        name: res.data?.company?.name ?? company.name ?? ''
                                    };
                                }

                                this.showToast('Vinculado a ' + (company.name ?? 'empresa'));
                                this.closeCompanyModal();
                            } catch (e) {
                                console.error('attachCompany error', e);
                                this.showToast('No se pudo vincular');
                            }
                        },

                    }
                }
            </script>
        @endpush
    @endsection
</x-adminpanel::layouts.master>
