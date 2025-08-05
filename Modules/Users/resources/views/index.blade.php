<x-adminpanel::layouts.master>
    @section('title', 'Gestión de Usuarios')

    @section('content')
        <div x-data="useEditor()" x-init="init()">

            <div x-show="toast.visible" x-cloak x-transition
                class="fixed top-5 right-5 bg-green-500 text-white px-4 py-3 rounded shadow-lg z-[9999] flex items-center space-x-2"
                style="display: none;">
                <span x-text="toast.message"></span>
                <button @click="toast.visible = false" class="ml-2 text-white font-bold">&times;</button>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Usuarios del sistema</h2>
                <button @click="open = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    + Crear nuevo usuario
                </button>
            </div>
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
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-2">{{ $user->name }}</td>
                                <td class="px-4 py-2">{{ $user->email }}</td>
                                <td class="px-4 py-2">
                                    {{ $user->roles->pluck('name')->join(', ') }}
                                </td>
                                <td class="px-4 py-2">
                                    <span
                                        class="px-2 py-1 text-xs rounded 
                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <button @click="edit({{ $user->id }})"
                                        class="text-blue-600 hover:underline">Editar</button>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:underline"
                                            onclick="return confirm('¿Eliminar este usuario?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Modal de Crear Nuevo Usuario --}}
            <div x-show="open" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <button @click="open = false" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>

                    <h2 class="text-xl font-semibold mb-4">Nuevo usuario</h2>

                    {{-- NOTA: El form ya no usa action, porque usas Axios --}}
                    <form @submit.prevent="store">
                        {{-- Nombre --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Nombre</label>
                            <input type="text" name="name" x-model="form.name" required
                                class="w-full mt-1 border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- Email --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Correo electrónico</label>
                            <input type="email" name="email" x-model="form.email" required
                                class="w-full mt-1 border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- Contraseña --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Contraseña</label>
                            <input type="password" name="password" x-model="form.password" required
                                class="w-full mt-1 border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- Rol --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium">Rol</label>
                            <select name="role" x-model="form.role"
                                class="w-full mt-1 border-gray-300 rounded px-3 py-2">
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

            {{-- Modal de edición (AHORA DENTRO del x-data) --}}
            <div x-show="editModal" x-cloak x-transition
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <button @click="editModal = false"
                        class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>

                    <h2 class="text-xl font-semibold mb-4">Editar usuario</h2>

                    <form :action="`/users/${form.id}`" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm">Nombre</label>
                            <input type="text" name="name" x-model="form.name"
                                class="w-full border px-3 py-2 rounded">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm">Email</label>
                            <input type="email" name="email" x-model="form.email"
                                class="w-full border px-3 py-2 rounded">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm">Rol</label>
                            <select name="role" class="w-full border px-3 py-2 rounded">
                                @foreach ($roles as $role)
                                    <option :selected="form.role === '{{ $role->name }}'" value="{{ $role->name }}">
                                        {{ ucfirst($role->name) }}
                                    </option>
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

        </div> {{-- ← este cierra el div x-data="useEditor()" correctamente AHORA --}}

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
                        users: @json($users), // <-- Incluimos los usuarios para buscarlos por ID

                        init() {},

                        async store() {
                            try {
                                const response = await axios.post("{{ route('users.store') }}", {
                                    name: this.form.name,
                                    email: this.form.email,
                                    password: this.form.password,
                                    role: this.form.role,
                                });

                                this.open = false;
                                this.resetForm();
                                this.showToast('Usuario creado exitosamente.');
                                window.location.reload();

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
                            }, 8000);
                        }
                    }
                }
            </script>
        @endpush

    @endsection
</x-adminpanel::layouts.master>
