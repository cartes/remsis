<x-adminpanel::layouts.master>
    @section('title', 'Gestión de Usuarios')

    @section('content')
        <div x-data="useEditor()" x-init="init()">

            {{-- Toast de éxito --}}
            <div x-show="toast.visible" x-cloak x-transition
                class="fixed top-5 right-5 bg-green-500 text-white px-4 py-3 rounded shadow-lg z-[9999] flex items-center space-x-2">
                <span x-text="toast.message"></span>
                <button @click="toast.visible = false" class="ml-2 text-white font-bold">&times;</button>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Usuarios del sistema</h2>
                <button @click="open = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    + Crear nuevo usuario
                </button>
            </div>

            {{-- Tabla dinámica de usuarios --}}
            <div class="bg-white shadow rounded overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-left">
                        <tr>
                            <th class="px-4 py-2">Nombre</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Rol</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="user in users" :key="user.id">
                            <tr>
                                <td class="px-4 py-2" x-text="user.name"></td>
                                <td class="px-4 py-2" x-text="user.email"></td>
                                <td class="px-4 py-2" x-text="user.roles.map(r => r.name).join(', ')"></td>
                                <td class="px-4 py-2">
                                    <span
                                        :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        class="px-2 py-1 text-xs rounded" x-text="user.is_active ? 'Activo' : 'Inactivo'">
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex space-x-2">
                                        {{-- Botón Editar --}}
                                        <button @click="edit(user.id)" title="Editar"
                                            class="p-3 w-5 h-5 flex items-center justify-center bg-blue-100 hover:bg-blue-200 text-blue-600 hover:text-blue-800 rounded-sm transition"
                                            aria-label="Editar">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>

                                        {{-- Botón Eliminar --}}
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

        </div>

        @push('scripts')
            <script>
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

                        async store() {
                            try {
                                const response = await axios.post("{{ route('users.store') }}", {
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
                            if (!user) return;

                            this.form.id = user.id;
                            this.form.name = user.name;
                            this.form.email = user.email;
                            this.form.role = user.roles[0]?.name || '';
                            this.editModal = true;
                        },

                        async update() {
                            try {
                                const response = await axios.put(`/users/${this.form.id}`, {
                                    name: this.form.name,
                                    email: this.form.email,
                                    role: this.form.role,
                                });

                                const index = this.users.findIndex(u => u.id === this.form.id);
                                if (index !== -1) {
                                    this.users[index] = {
                                        ...this.users[index],
                                        name: this.form.name,
                                        email: this.form.email,
                                        roles: [{
                                            name: this.form.role
                                        }],
                                    };
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
                                await axios.delete(`/users/${id}`);
                                this.users = this.users.filter(u => u.id !== id);
                                this.showToast('Usuario eliminado correctamente.');
                            } catch (error) {
                                console.error('Error al eliminar usuario', error);
                                alert('Error al eliminar usuario.');
                            }
                        }
                    }
                }
            </script>
        @endpush
    @endsection
</x-adminpanel::layouts.master>
