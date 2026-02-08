<x-adminpanel::layouts.master>
    @section('title', 'Gestión de Usuarios')

    @section('content')
        <style>
            [data-tooltip] {
                position: relative;
                cursor: pointer;
            }

            [data-tooltip]:before {
                content: attr(data-tooltip);
                position: absolute;
                bottom: 125%;
                left: 50%;
                transform: translateX(-50%) scale(0.9);
                padding: 6px 10px;
                background-color: #1e293b;
                color: #fff;
                font-size: 11px;
                border-radius: 6px;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1000;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
                pointer-events: none;
                font-weight: 600;
            }

            [data-tooltip]:after {
                content: '';
                position: absolute;
                bottom: 110%;
                left: 50%;
                transform: translateX(-50%);
                border: 6px solid transparent;
                border-top-color: #1e293b;
                opacity: 0;
                visibility: hidden;
                transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1000;
                pointer-events: none;
            }

            [data-tooltip]:hover:before,
            [data-tooltip]:hover:after {
                opacity: 1;
                visibility: visible;
                transform: translateX(-50%) scale(1);
            }
        </style>
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
                    {{-- Filtro por Empresa (Searchable) --}}
                    <div class="relative w-full md:w-64" x-data="{ show: false }">
                        <button @click="show = !show" type="button"
                            data-tooltip="Haz clic para buscar y filtrar usuarios por empresa"
                            class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm flex items-center justify-between hover:border-blue-400 transition-all shadow-sm">
                            <span class="truncate font-medium text-gray-700">
                                @if ($companyId)
                                    @php $selComp = collect($companies)->firstWhere('id', $companyId); @endphp
                                    {{ $selComp ? $selComp['name'] : 'Filtrar por Empresa' }}
                                @else
                                    Filtrar por Empresa
                                @endif
                            </span>
                            <i class="fas fa-search text-gray-400"></i>
                        </button>

                        <div x-show="show" @click.away="show = false" x-cloak
                            class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden min-w-[250px]">
                            <div class="p-2 border-b border-gray-100 bg-gray-50">
                                <input type="text" x-model="companyFilterSearch" placeholder="Buscar empresa..."
                                    class="w-full px-3 py-1.5 text-xs bg-white border border-gray-200 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            </div>
                            <div class="max-h-60 overflow-y-auto p-1">
                                <a href="{{ route('users.index') }}"
                                    data-tooltip="Haz clic para quitar el filtro y ver todos los usuarios"
                                    class="block px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <i class="fas fa-times-circle mr-1"></i> Ver Todos
                                </a>
                                <template x-for="comp in filteredCompanies" :key="comp.id">
                                    <button
                                        @click="window.location.href = '{{ route('users.index') }}?company_id=' + comp.id"
                                        class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-all flex items-center gap-2">
                                        <i class="fas fa-building text-gray-400"></i>
                                        <span x-text="comp.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Botón Crear usuario --}}
                    <button @click="open = true" data-tooltip="Crear un nuevo usuario en el sistema"
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
                            <th class="px-6 py-4 text-left">Empresa</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="user in users" :key="user.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <button @click="edit(user.id)"
                                            class="font-bold text-gray-800 hover:text-blue-600 transition-colors text-left focus:outline-none"
                                            data-tooltip="Editar Perfil">
                                            <span x-text="user.name"></span>
                                        </button>
                                        <span class="text-[11px] text-gray-500 mt-0.5" x-text="user.email"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="role in user.roles" :key="role.id">
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold uppercase tracking-wider border border-blue-100"
                                                x-text="role.name.replace('-', ' ')"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <template x-if="user.employee && user.employee.company">
                                        <span class="font-medium text-gray-700" x-text="user.employee.company.name"></span>
                                    </template>
                                    <template x-if="!user.employee || !user.employee.company">
                                        <span class="text-gray-400 italic text-xs">Sin empresa</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center">
                                        {{-- Switch solo si NO es super-admin --}}
                                        <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                            <button @click="toggleStatus(user.id)"
                                                :class="user.status ? 'bg-green-100 text-green-700 border-green-200' :
                                                    'bg-gray-100 text-gray-400 border-gray-200'"
                                                class="flex items-center gap-2 px-3 py-1 rounded-full border transition-all text-[11px] font-bold uppercase tracking-wide group/toggle"
                                                :data-tooltip="user.status ? 'Desactivar Usuario' : 'Activar Usuario'">
                                                <i :class="user.status ? 'fas fa-toggle-on text-green-600' :
                                                    'fas fa-toggle-off text-gray-400'"
                                                    class="text-base group-hover/toggle:scale-110 transition-transform"></i>
                                                <span x-text="user.status ? 'Activo' : 'Inactivo'"></span>
                                            </button>
                                        </template>

                                        {{-- Texto fijo si ES super-admin --}}
                                        <template x-if="user.roles.some(r => r.name === 'super-admin')">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-[10px] font-bold uppercase tracking-wider border border-green-100"
                                                data-tooltip="Los administradores maestros siempre están activos">
                                                <i class="fas fa-shield-alt"></i>
                                                Siempre Activo
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 text-xs">
                                        {{-- Botón Vincular Empresa (si NO es super-admin y NO tiene empresa) --}}
                                        <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                            <button @click="openCompanyModal(user)"
                                                class="bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white p-2 rounded-lg transition-all border border-indigo-100"
                                                data-tooltip="Vincular a Empresa">
                                                <i class="fas fa-link"></i>
                                            </button>
                                        </template>

                                        {{-- Botón Editar General (si NO es super-admin) --}}
                                        <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                            <button @click="edit(user.id)"
                                                class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white p-2 rounded-lg transition-all border border-blue-100"
                                                data-tooltip="Editar Perfil">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        </template>

                                        {{-- Botón Eliminar (si NO es super-admin) --}}
                                        <template x-if="!user.roles.some(r => r.name === 'super-admin')">
                                            <button @click="remove(user.id)"
                                                class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white p-2 rounded-lg transition-all border border-red-100"
                                                data-tooltip="Eliminar Usuario">
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
                        <button @click="open = false" data-tooltip="Cerrar ventana"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
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
                            <button type="button" @click="open = false" data-tooltip="Cancelar y cerrar"
                                class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" data-tooltip="Crear el usuario con los datos ingresados"
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
                        <button @click="editModal = false" data-tooltip="Cerrar ventana"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
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
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Empresa</label>
                                    <select x-model="form.company_id"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none">
                                        <option value="">Ninguna / Sin Empresa</option>
                                        <template x-for="comp in companiesList" :key="comp.id">
                                            <option :value="comp.id" x-text="comp.name"
                                                :selected="form.company_id == comp.id"></option>
                                        </template>
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
                            <button type="button" @click="editModal = false" data-tooltip="Descartar cambios y cerrar"
                                class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" data-tooltip="Guardar los cambios realizados en el perfil"
                                class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Guardar Cambios</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Modal Vincular Empresa --}}
            <div x-show="companyModal.open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" x-cloak
                class="fixed inset-0 bg-gray-500 bg-opacity-75 z-[60] flex items-center justify-center p-4">

                <div @click.away="closeCompanyModal()"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all border border-gray-100">

                    <div
                        class="bg-gradient-to-br from-indigo-50 to-blue-50 px-8 py-6 border-b border-gray-200/50 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-black text-indigo-900 tracking-tight">Vincular a Empresa</h3>
                            <p class="text-xs font-semibold text-indigo-600 uppercase tracking-widest mt-0.5 opacity-70"
                                x-text="'Usuario: ' + (companyModal.user ? companyModal.user.name : '')"></p>
                        </div>
                        <button @click="closeCompanyModal()" data-tooltip="Cerrar buscador de empresas"
                            class="text-indigo-400 hover:text-indigo-600 hover:rotate-90 transition-all duration-300 bg-white/50 p-2 rounded-full">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-indigo-400">
                                <i class="fas fa-search text-sm"></i>
                            </div>
                            <input type="text" x-model="companySearch" @input.debounce.300ms="fetchCompanies"
                                placeholder="Buscar empresa por nombre o razón social..."
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-transparent rounded-xl text-sm font-semibold text-indigo-900 placeholder-indigo-300 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all transition-all outline-none">
                        </div>

                        <div class="space-y-2 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                            <template x-if="companies.length === 0 && !companyLoading">
                                <div class="py-12 text-center opacity-40">
                                    <i class="fas fa-building text-5xl mb-3 text-indigo-200"></i>
                                    <p class="text-sm font-bold text-indigo-900 tracking-tight">No se encontraron empresas
                                    </p>
                                </div>
                            </template>

                            <template x-for="comp in companies" :key="comp.id">
                                <button @click="attachCompany(comp)"
                                    :class="{
                                        'bg-green-50 border-green-200 hover:border-green-400 hover:bg-green-100 shadow-sm': companyModal
                                            .currentCompanyId === comp.id,
                                        'bg-white border-gray-100 hover:border-indigo-400 hover:bg-indigo-50 hover:shadow-md': companyModal
                                            .currentCompanyId !== comp.id
                                    }"
                                    class="w-full flex items-center justify-between p-4 border rounded-xl transition-all group">
                                    <div class="flex items-center gap-4">
                                        <div :class="{
                                            'bg-green-600 text-white': companyModal.currentCompanyId === comp.id,
                                            'bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white': companyModal
                                                .currentCompanyId !== comp.id
                                        }"
                                            class="p-3 rounded-lg transition-colors">
                                            <i class="fas fa-building text-lg"></i>
                                        </div>
                                        <div class="text-left">
                                            <span
                                                :class="companyModal.currentCompanyId === comp.id ? 'text-green-900' :
                                                    'text-gray-800'"
                                                class="block font-bold tracking-tight" x-text="comp.name"></span>
                                            <template x-if="companyModal.currentCompanyId === comp.id">
                                                <span
                                                    class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Empresa
                                                    Actual</span>
                                            </template>
                                        </div>
                                    </div>
                                    <template x-if="companyModal.currentCompanyId === comp.id">
                                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                                    </template>
                                    <template x-if="companyModal.currentCompanyId !== comp.id">
                                        <i
                                            class="fas fa-chevron-right text-indigo-300 group-hover:text-indigo-500 transition-transform group-hover:translate-x-1"></i>
                                    </template>
                                </button>
                            </template>

                            <div x-show="companyLoading" class="py-8 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-indigo-500 opacity-50"></i>
                            </div>
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
                            passwordOnly: false,
                            company_id: '',
                        },
                        users: @json($users),
                        companyModal: {
                            open: false,
                            user: null,
                            currentCompanyId: null,
                        },
                        companySearch: '',
                        companyLoading: false,
                        companies: @json($companies),
                        companyMeta: {
                            current_page: 1,
                            last_page: 1
                        },
                        // Fitro de empresas en cabecera
                        companyFilterSearch: '',
                        companiesList: @json($companies),
                        get filteredCompanies() {
                            if (!this.companyFilterSearch) return this.companiesList;
                            return this.companiesList.filter(c =>
                                c.name.toLowerCase().includes(this.companyFilterSearch.toLowerCase())
                            );
                        },

                        openCompanyModal(user) {
                            if (!user || typeof user.id === 'undefined') {
                                return;
                            }
                            this.companyModal.user = {
                                id: user.id,
                                name: user.name ?? ''
                            };

                            // Su empresa actual
                            const currentCompId = user.employee?.company?.id || user.employee?.company_id;
                            this.companyModal.currentCompanyId = currentCompId;

                            this.companyModal.open = true;
                            this.companySearch = '';

                            // Reordenar empresas para poner la actual primero
                            this.companies = [...this.companiesList];
                            if (currentCompId) {
                                const idx = this.companies.findIndex(c => c.id === currentCompId);
                                if (idx > -1) {
                                    const current = this.companies.splice(idx, 1)[0];
                                    this.companies.unshift(current);
                                }
                            }
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
                                this.form.company_id = user.employee?.company_id ?? '';
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
                                    company_id: this.form.company_id,
                                });

                                const index = this.users.findIndex(u => u.id === this.form.id);
                                if (index !== -1) {
                                    this.users[index].name = this.form.name;
                                    this.users[index].email = this.form.email;
                                    this.users[index].roles = [{
                                        name: this.form.role
                                    }];
                                    this.users[index].employee = response.data.user.employee;
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
