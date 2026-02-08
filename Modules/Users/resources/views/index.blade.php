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

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Usuarios del Sistema</h2>
                    <p class="text-sm text-gray-500 mt-1">Gestiona los accesos, roles y vinculaciones de usuarios con
                        empresas.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    {{-- Botón Crear usuario --}}
                    <button @click="open = true"
                        class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-sm flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Usuario</span>
                    </button>
                </div>
            </div>

            {{-- Tabla dinámica de usuarios --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Nombre / Email</th>
                            <th class="px-6 py-4 text-left">Rol</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center">
                                {{-- Switch solo si NO es super-admin --}}
                                <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                    <button @click="toggleStatus(user.id)"
                                        :class="user.status ? 'bg-green-100 text-green-700 border-green-200' :
                                            'bg-gray-100 text-gray-400 border-gray-200'"
                                        class="flex items-center gap-2 px-3 py-1 rounded-full border transition-all text-[11px] font-bold uppercase tracking-wide group/toggle">
                                        <i :class="user.status ? 'fas fa-toggle-on text-green-600' :
                                            'fas fa-toggle-off text-gray-400'"
                                            class="text-base group-hover/toggle:scale-110 transition-transform"></i>
                                        <span x-text="user.status ? 'Activo' : 'Inactivo'"></span>
                                    </button>
                                </template>

                                {{-- Texto fijo si ES super-admin --}}
                                <template x-if="user.roles.some(r => r.name === 'super-admin')">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-[10px] font-bold uppercase tracking-wider border border-green-100">
                                        <i class="fas fa-shield-alt"></i>
                                        Siempre Activo
                                    </span>
                                </template>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 text-xs">
                                {{-- Botón Editar General (si NO es super-admin) --}}
                                <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                    <button @click="edit(user.id)"
                                        class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white p-2 rounded-lg transition-all border border-blue-100"
                                        title="Editar Perfil">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                </template>

                                {{-- Botón Solo Clave (si ES super-admin) --}}
                                <template x-if="user.roles.some(r => r.name === 'super-admin')">
                                    <button @click="editPasswordOnly(user.id)"
                                        class="bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white p-2 rounded-lg transition-all border border-orange-100"
                                        title="Cambiar Contraseña">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </template>

                                {{-- Botón Eliminar (Solo si NO es super-admin) --}}
                                <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                    <button @click="remove(user.id)"
                                        class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white p-2 rounded-lg transition-all border border-red-100"
                                        title="Eliminar Usuario">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </template>
                            </div>
                        </td>
                        </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Modal Crear --}}
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak
                class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center p-4">

                <div @click.away="open = false"
                    class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">Crear Nuevo Usuario</h3>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form @submit.prevent="store" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre
                                Completo</label>
                            <input type="text" x-model="form.name" required placeholder="Ej: Juan Pérez"
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Correo
                                Electrónico</label>
                            <input type="email" x-model="form.email" required placeholder="juan@ejemplo.com"
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Contraseña
                                Inicial</label>
                            <input type="password" x-model="form.password" required
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Rol en el
                                Sistema</label>
                            <select x-model="form.role" required
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none">
                                <option value="">Selecciona un rol</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="open = false"
                                class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-user-plus"></i>
                                <span>Crear Usuario</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Modal Editar --}}
            <div x-show="editModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak
                class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center p-4">

                <div @click.away="editModal = false"
                    class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">Modificar Perfil</h3>
                        <button @click="editModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form @submit.prevent="update" class="p-6 space-y-4">
                        <template x-if="!form.passwordOnly">
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre
                                        Completo</label>
                                    <input type="text" x-model="form.name" required
                                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email</label>
                                    <input type="email" x-model="form.email" required readonly
                                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500 cursor-not-allowed">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Rol</label>
                                    <select x-model="form.role" required
                                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">
                                                {{ ucfirst(str_replace('-', ' ', $role->name)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">
                                <span
                                    x-text="form.passwordOnly ? 'Nueva Contraseña' : 'Nueva Contraseña (Opcional)'"></span>
                            </label>
                            <input type="password" x-model="form.password" :required="form.passwordOnly"
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="editModal = false"
                                class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Guardar Cambios</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Modal Vincular Empresa: ELIMINADO de aquí porque se mueve a Nómina --}}


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
                            passwordOnly: false,
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
                                passwordOnly: false,
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
                                this.resetForm();
                                this.form.id = user.id;
                                this.form.name = user.name;
                                this.form.email = user.email;
                                this.form.role = user.roles.length > 0 ? user.roles[0].name : '';
                                this.form.passwordOnly = false;
                                this.editModal = true;
                            }
                        },

                        editPasswordOnly(id) {
                            const user = this.users.find(u => u.id === id);
                            if (user) {
                                this.resetForm();
                                this.form.id = user.id;
                                this.form.name = user.name;
                                this.form.email = user.email;
                                this.form.role = user.roles.length > 0 ? user.roles[0].name : '';
                                this.form.passwordOnly = true;
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
