<x-layouts.company :company="$company" activeTab="users">
    @section('title', 'Usuarios y Accesos - ' . $company->razon_social)

    <div class="max-w-7xl mx-auto py-6" x-data="usersManager()">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Usuarios y Accesos</h1>
                <p class="text-sm text-gray-500 mt-2">Administradores, contadores y personal de recursos humanos de la
                    empresa.</p>
            </div>
            <div>
                <button @click="openCreateModal()"
                    class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>

        @if (session('success'))
            <div
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500"></i>
                <span class="font-medium text-sm">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-widest text-[10px] font-bold">
                        <tr>
                            <th scope="col" class="px-6 py-4">Usuario</th>
                            <th scope="col" class="px-6 py-4">Rol</th>
                            <th scope="col" class="px-6 py-4">Estado</th>
                            <th scope="col" class="px-6 py-4 flex justify-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <button
                                        @click="openEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->roles->first()?->name ?? '' }}', {{ $user->employee?->is_in_payroll ? 'true' : 'false' }})"
                                        class="flex items-center gap-3 text-left w-full focus:outline-none">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shadow-sm transition-transform group-hover:scale-105">
                                            {{ substr($user->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p
                                                class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                                {{ $user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                        </div>
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    @foreach ($user->roles as $role)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="px-6 py-4">
                                    @if ($user->status)
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-widest bg-green-50 text-green-700 border border-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activo
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-widest bg-gray-50 text-gray-600 border border-gray-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="openEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->roles->first()?->name ?? '' }}', {{ $user->employee?->is_in_payroll ? 'true' : 'false' }})"
                                        class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded-lg transition-colors mr-1"
                                        title="Editar Usuario">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if (Auth::id() !== $user->id)
                                        <form
                                            action="{{ route('companies.users.destroy', ['company' => $company->id, 'user' => $user->id]) }}"
                                            method="POST" class="inline-block"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors"
                                                title="Eliminar Usuario">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div
                                            class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 text-2xl">
                                            <i class="fas fa-user-slash"></i>
                                        </div>
                                        <p>No hay usuarios vinculados a esta empresa.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User Modal (Create & Edit) -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true"
                    @click="showModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <form :action="formAction" method="POST">
                        @csrf
                        <template x-if="isEditing">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-50 sm:mx-0 sm:h-10 sm:w-10 transition-colors"
                                    :class="isEditing ? 'bg-amber-50' : 'bg-blue-50'">
                                    <i class="fas"
                                        :class="isEditing ? 'fa-user-pen text-amber-600' : 'fa-user-plus text-blue-600'"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title"
                                        x-text="isEditing ? 'Editar Usuario' : 'Nuevo Usuario'"></h3>
                                    <div class="mt-4 space-y-4 text-sm w-full">
                                        <div class="space-y-1">
                                            <label
                                                class="font-bold text-slate-700 text-xs uppercase tracking-widest">Rol
                                                del Usuario</label>
                                            <select name="role" x-model="form.role" required
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-medium text-slate-700">
                                                <option value="">Selecciona un rol...</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->name }}">
                                                        {{ ucfirst(str_replace('-', ' ', $role->name)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label
                                                class="font-bold text-slate-700 text-xs uppercase tracking-widest">Nombre
                                                Completo</label>
                                            <input type="text" name="name" x-model="form.name" required
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                                placeholder="Ej. Juan Pérez">
                                        </div>
                                        <div class="space-y-1">
                                            <label
                                                class="font-bold text-slate-700 text-xs uppercase tracking-widest">Correo
                                                Electrónico</label>
                                            <input type="email" name="email" x-model="form.email" required
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                                placeholder="admin@empresa.com">
                                        </div>
                                        <div class="space-y-1">
                                            <label
                                                class="font-bold text-slate-700 text-xs uppercase tracking-widest">Contraseña
                                                <span x-show="isEditing"
                                                    class="text-gray-400 normal-case tracking-normal font-medium">(Opcional,
                                                    dejar en blanco para mantener)</span></label>
                                            <input type="password" name="password" :required="!isEditing"
                                                minlength="6"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                                placeholder="Mínimo 6 caracteres">
                                        </div>
                                        <div class="mt-4 p-4 border rounded-xl flex items-start gap-3 transition-colors"
                                            :class="form.is_in_payroll ? 'bg-blue-50/50 border-blue-100' :
                                                'bg-slate-50 border-slate-100'">
                                            <div class="flex items-center h-5">
                                                <input id="is_in_payroll" name="is_in_payroll" type="checkbox"
                                                    value="1" x-model="form.is_in_payroll"
                                                    class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500">
                                            </div>
                                            <div class="ml-2 text-sm">
                                                <label for="is_in_payroll"
                                                    class="font-bold text-gray-700 cursor-pointer">¿Será parte de la
                                                    nómina de la empresa?</label>
                                                <p class="text-xs font-medium text-gray-500">Marca esta casilla si el
                                                    usuario recibirá pagos o generará liquidación de sueldo como
                                                    empleado. Déjalo desmarcado si es personal externo (Ej. oficina
                                                    contable).</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-gray-50/50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl border-t border-gray-100">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto transition-colors"
                                :class="isEditing ? 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500' :
                                    'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'"
                                x-text="isEditing ? 'Guardar Cambios' : 'Crear Usuario'">
                            </button>
                            <button type="button" @click="showModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function usersManager() {
                return {
                    showModal: false,
                    isEditing: false,
                    companyId: {{ $company->id }},
                    form: {
                        id: null,
                        name: '',
                        email: '',
                        role: '',
                        is_in_payroll: false
                    },
                    get formAction() {
                        if (this.isEditing && this.form.id) {
                            return `/companies/${this.companyId}/users/${this.form.id}`;
                        }
                        return `/companies/${this.companyId}/users`;
                    },
                    openCreateModal() {
                        this.isEditing = false;
                        this.form = {
                            id: null,
                            name: '',
                            email: '',
                            role: '',
                            is_in_payroll: false
                        };
                        this.showModal = true;
                    },
                    openEditModal(id, name, email, role, isInPayroll) {
                        this.isEditing = true;
                        this.form = {
                            id: id,
                            name: name,
                            email: email,
                            role: role,
                            is_in_payroll: isInPayroll
                        };
                        this.showModal = true;
                    }
                }
            }
        </script>
    @endpush
</x-layouts.company>
