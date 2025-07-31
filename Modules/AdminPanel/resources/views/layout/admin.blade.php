<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Panel de Administración')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js']) {{-- Si usas Vite --}}
</head>

<body class="bg-gray-100 text-gray-800">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-white shadow-md">
            <div class="p-4 font-bold text-xl border-b">Remsis</div>
            <nav class="mt-4">
                <ul class="space-y-2 p-2">
                    <li><a href="{{ route('users.index') }}" class="block p-2 rounded hover:bg-gray-200">Usuarios</a>
                        <li><a href="{{ route('roles.index') }}" class="block p-2 rounded hover:bg-gray-200">Roles</a></li>
                    </li>
                    {{-- Agrega más ítems según el módulo --}}
                </ul>
            </nav>
        </aside>

        {{-- Contenido principal --}}
        <main class="flex-1 p-6">
            <h1 class="text-2xl font-semibold mb-4">@yield('title')</h1>
            @yield('content')
        </main>
    </div>

</body>

</html>
