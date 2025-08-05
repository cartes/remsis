<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Panel de Administración')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-gray-100 text-gray-800">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-white shadow-md">
            <div class="p-4 font-bold text-xl border-b">Remsis</div>
            <nav class="mt-4">
                <ul class="space-y-2 p-2">
                    <li>
                        <a href="{{ route('users.index') }}"
                            class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                            <i class="fas fa-users mr-2"></i> Usuarios
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('roles.index') }}"
                            class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                            <i class="fas fa-user-tag mr-2"></i> Roles
                        </a>
                    </li>
                    </li>
                    @hasrole('super-admin')
                        <li x-data="{ open: false }" class="mt-4">
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                                <span><i class="fas fa-sliders-h mr-2"></i>Configuraciones</span>
                                <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                            </button>

                            <ul x-show="open" x-transition class="pl-4 mt-2 space-y-1">
                                <li>
                                    <a href="{{ route('settings.index') }}"
                                        class="w-full flex items-center p-2 rounded hover:bg-gray-200 font-bold text-gray-500 uppercase text-xs">
                                        <i class="fas fa-cogs mr-2 text-sm"></i> Entidades Base
                                    </a>
                                </li>
                                {{-- Puedes agregar más subítems aquí si luego quieres separar AFP, Isapres, etc. --}}
                            </ul>
                        </li>
                    @endhasrole
                </ul>
            </nav>
        </aside>

        {{-- Contenido principal --}}
        <main class="flex-1 p-6">
            <h1 class="text-2xl font-semibold mb-4">@yield('title')</h1>
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>

</html>
