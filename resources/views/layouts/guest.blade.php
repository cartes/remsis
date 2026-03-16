<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Acceso</title>

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Inter:wght@100..900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
            
            body { 
                font-family: 'Inter', sans-serif;
            }
            .font-outfit {
                font-family: 'Outfit', sans-serif;
            }
            .glass-panel {
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.4);
            }
            .auth-grid {
                background: 
                    radial-gradient(circle at 0% 0%, rgba(79, 70, 229, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 100% 100%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
            }
        </style>
    </head>
    <body class="antialiased text-slate-900 overflow-x-hidden">
        <div class="min-h-screen flex flex-col justify-center items-center p-4 bg-slate-50 auth-grid relative">
            
            <!-- Floating Orbs for Premium feel -->
            <div class="absolute top-[-10%] left-[-5%] w-[40%] h-[40%] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute bottom-[-10%] right-[-5%] w-[40%] h-[40%] bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>

            <div class="w-full sm:max-w-[440px] z-10">
                <!-- Logo / Brand Header -->
                <div class="flex flex-col items-center mb-8">
                    <a href="/" class="group transition-transform duration-500 hover:scale-110">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-blue-600 rounded-2xl flex items-center justify-center text-white text-3xl font-black shadow-xl shadow-indigo-200 group-hover:shadow-indigo-300 group-hover:rotate-6 transition-all duration-300">
                            R
                        </div>
                    </a>
                    <h1 class="mt-6 text-3xl font-black font-outfit text-slate-800 tracking-tighter uppercase">Remsis</h1>
                    <p class="text-slate-400 text-sm font-bold uppercase tracking-[0.2em] mt-1">Sistema de Remuneraciones</p>
                </div>

                <!-- Main Auth Card -->
                <div class="glass-panel rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.05)] overflow-hidden">
                    <div class="p-8 sm:p-10">
                        {{ $slot }}
                    </div>
                </div>
                
                <!-- Footer Info -->
                <div class="mt-8 text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        &copy; {{ date('Y') }} REMSIS - Gestión Laboral Inteligente
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
